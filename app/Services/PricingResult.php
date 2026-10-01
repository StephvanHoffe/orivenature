<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\GiftCard;
use App\Models\ShippingRate;
use Illuminate\Support\Collection;

class PricingResult
{
    /** @var Collection<int, CartLine> */
    public Collection $lines;

    public int $subtotal = 0;

    public ?Discount $discount = null;

    public ?string $discountError = null;

    public int $discountTotal = 0;

    public bool $freeShipping = false;

    public string $countryCode = 'NL';

    public bool $shippingAvailable = true;

    /** @var Collection<int, ShippingRate> */
    public Collection $shippingRates;

    public ?ShippingRate $shippingRate = null;

    public int $shippingTotal = 0;

    public ?GiftCard $giftCard = null;

    public ?string $giftCardError = null;

    public int $giftCardUsed = 0;

    public int $creditAvailable = 0;

    public int $creditUsed = 0;

    public int $taxTotal = 0;

    public int $total = 0;

    public function __construct()
    {
        $this->lines = collect();
        $this->shippingRates = collect();
    }

    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discountTotal,
            'discount_title' => $this->discount?->code ?: $this->discount?->title,
            'discount_error' => $this->discountError,
            'shipping_total' => $this->shippingTotal,
            'shipping_rate_id' => $this->shippingRate?->id,
            'shipping_available' => $this->shippingAvailable,
            'free_shipping' => $this->freeShipping,
            'shipping_rates' => $this->shippingRates->map(fn (ShippingRate $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'description' => $r->description,
                'price' => $this->freeShipping ? 0 : $r->price,
                'price_formatted' => $this->freeShipping || $r->price === 0 ? __('Gratis') : money($r->price),
            ])->values()->all(),
            'gift_card_used' => $this->giftCardUsed,
            'gift_card_error' => $this->giftCardError,
            'credit_available' => $this->creditAvailable,
            'credit_used' => $this->creditUsed,
            'tax_total' => $this->taxTotal,
            'total' => $this->total,
            'total_formatted' => money($this->total),
        ];
    }
}
