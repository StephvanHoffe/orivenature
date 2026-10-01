<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Winkelwagen in de sessie: ['items' => [variant_id => aantal], 'discount_code' => ..., 'gift_card' => ..., 'use_credit' => bool]
 */
class Cart
{
    private ?Collection $lines = null;

    public function __construct(private Session $session) {}

    public function raw(): array
    {
        return array_replace(['items' => [], 'discount_code' => null, 'gift_card' => null, 'use_credit' => true], (array) $this->session->get('cart', []));
    }

    private function save(array $cart): void
    {
        $this->session->put('cart', $cart);
        $this->lines = null;
    }

    /** @return Collection<int, CartLine> */
    public function lines(): Collection
    {
        if ($this->lines !== null) {
            return $this->lines;
        }
        $items = $this->raw()['items'];
        if (! $items) {
            return $this->lines = collect();
        }
        $variants = ProductVariant::with(['product.images', 'product.variants', 'product.collections:id', 'image'])
            ->whereIn('id', array_keys($items))->get()->keyBy('id');

        $lines = collect();
        foreach ($items as $variantId => $quantity) {
            $variant = $variants->get($variantId);
            if (! $variant || $variant->product->status !== 'active') {
                continue;
            }
            $max = $variant->availableQuantity();
            $quantity = $max === null ? $quantity : min($quantity, $max);
            if ($quantity > 0) {
                $lines->push(new CartLine($variant, (int) $quantity));
            }
        }

        return $this->lines = $lines;
    }

    public function add(int $variantId, int $quantity = 1): void
    {
        $variant = ProductVariant::with('product')->findOrFail($variantId);
        if ($variant->product->status !== 'active' || ! $variant->isAvailable()) {
            throw ValidationException::withMessages(['id' => __('Dit product is helaas uitverkocht.')]);
        }
        $cart = $this->raw();
        $new = ($cart['items'][$variantId] ?? 0) + max(1, $quantity);
        $max = $variant->availableQuantity();
        if ($max !== null && $new > $max) {
            if (($cart['items'][$variantId] ?? 0) >= $max) {
                throw ValidationException::withMessages(['id' => __('Er zijn er nog maar :count op voorraad.', ['count' => $max])]);
            }
            $new = $max;
        }
        $cart['items'][$variantId] = min(99, $new);
        $this->save($cart);
    }

    public function set(int $variantId, int $quantity): void
    {
        $cart = $this->raw();
        if ($quantity <= 0) {
            unset($cart['items'][$variantId]);
        } else {
            $variant = ProductVariant::find($variantId);
            $max = $variant?->availableQuantity();
            $cart['items'][$variantId] = min(99, $max === null ? $quantity : min($quantity, $max));
        }
        $this->save($cart);
    }

    public function setMeta(string $key, mixed $value): void
    {
        $cart = $this->raw();
        $cart[$key] = $value;
        $this->save($cart);
    }

    public function meta(string $key): mixed
    {
        return $this->raw()[$key] ?? null;
    }

    /** Herstelt een opgeslagen winkelwagen (bijv. vanuit de herinneringsmail). */
    public function restore(array $items): void
    {
        $cart = $this->raw();
        $cart['items'] = collect($items)->mapWithKeys(fn ($i) => [(int) $i['variant_id'] => (int) $i['quantity']])->all();
        $this->save($cart);
    }

    public function clear(): void
    {
        $this->session->forget('cart');
        $this->lines = null;
    }

    public function count(): int
    {
        return (int) $this->lines()->sum('quantity');
    }

    public function subtotal(): int
    {
        return (int) $this->lines()->sum(fn (CartLine $l) => $l->total());
    }

    public function isEmpty(): bool
    {
        return $this->lines()->isEmpty();
    }

    public function contains(int $productId): bool
    {
        return $this->lines()->contains(fn (CartLine $l) => $l->variant->product_id === $productId);
    }
}
