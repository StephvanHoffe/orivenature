<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Product;
use App\Services\Analytics;
use App\Services\Cart;
use App\Services\Pricing;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private Cart $cart) {}

    public function show()
    {
        $pricing = Pricing::calculate($this->cart->lines(), $this->cart->meta('discount_code'));

        return view('shop.cart', ['lines' => $this->cart->lines(), 'pricing' => $pricing]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);
        $this->cart->add((int) $data['variant_id'], (int) ($data['quantity'] ?? 1));
        Analytics::event('add_to_cart', $this->cart->lines()->first(fn ($l) => $l->variant->id === (int) $data['variant_id'])?->variant->product_id);

        return $this->respond($request);
    }

    public function change(Request $request)
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);
        $this->cart->set((int) $data['variant_id'], (int) $data['quantity']);

        return $this->respond($request);
    }

    public function update(Request $request)
    {
        foreach ((array) $request->input('quantities', []) as $variantId => $quantity) {
            $this->cart->set((int) $variantId, max(0, (int) $quantity));
        }

        return $request->has('checkout') ? redirect()->route('checkout') : redirect()->route('cart');
    }

    public function drawer(Request $request)
    {
        return $this->respond($request);
    }

    /** Deelbare kortingslink, bijv. /discount/WELKOM10?redirect=/products/matcha-essence */
    public function discount(Request $request, string $code)
    {
        $discount = Discount::where('code', strtoupper($code))->first();
        if ($discount && $discount->isCurrentlyValid()) {
            $this->cart->setMeta('discount_code', $discount->code);
            session()->flash('status', __('Kortingscode :code wordt automatisch toegepast bij het afrekenen.', ['code' => $discount->code]));
        }
        $to = (string) $request->query('redirect', '/');

        return redirect(str_starts_with($to, '/') && ! str_starts_with($to, '//') ? $to : '/');
    }

    public function recover(string $token)
    {
        $checkout = Checkout::where('token', $token)->whereNull('completed_at')->firstOrFail();
        $this->cart->restore($checkout->cart);
        session(['checkout_token' => $checkout->token, 'checkout_email' => $checkout->email]);

        return redirect()->route('checkout');
    }

    private function respond(Request $request)
    {
        if (! $request->expectsJson()) {
            return redirect()->route('cart');
        }

        return response()->json([
            'count' => $this->cart->count(),
            'drawer' => view('shop.partials.cart-drawer', $this->drawerData())->render(),
        ]);
    }

    public function drawerData(): array
    {
        $lines = $this->cart->lines();
        $upsells = collect((array) settings('cart.upsells'))->map(function ($u) {
            $product = Product::active()->with(['variants', 'images'])->where('handle', $u['product'] ?? null)->first();

            return $product && $product->isAvailable() && ! $this->cart->contains($product->id) ? ['product' => $product, 'label' => $u['label'] ?? null] : null;
        })->filter()->values();

        return [
            'lines' => $lines,
            'count' => $this->cart->count(),
            'subtotal' => $this->cart->subtotal(),
            'upsells' => $lines->isEmpty() ? collect() : $upsells,
        ];
    }
}
