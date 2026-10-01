<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\GiftCard;
use App\Models\Order;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Support\Collection;

/**
 * Berekent alle bedragen van een winkelwagen. Alle prijzen zijn inclusief btw.
 * Volgorde: subtotaal → korting → verzending → cadeaubon → shoptegoed.
 */
class Pricing
{
    /**
     * @param  Collection<int, CartLine>  $lines
     */
    public static function calculate(
        Collection $lines,
        ?string $discountCode = null,
        ?string $countryCode = null,
        ?int $shippingRateId = null,
        ?string $giftCardCode = null,
        ?Customer $customer = null,
        bool $useCredit = false,
        ?string $email = null,
    ): PricingResult {
        $result = new PricingResult;
        $result->lines = $lines;
        $result->subtotal = (int) $lines->sum(fn (CartLine $l) => $l->total());
        $lines->each(fn (CartLine $l) => $l->discountAllocated = 0);

        // Korting: code heeft voorrang, anders de beste automatische korting
        $discount = null;
        if ($discountCode) {
            $discount = Discount::where('code', strtoupper(trim($discountCode)))->where('is_automatic', false)->first();
            $error = $discount ? self::discountError($discount, $lines, $result->subtotal, $email ?? $customer?->email) : __('Deze kortingscode bestaat niet.');
            if ($error) {
                $result->discountError = $error;
                $discount = null;
            }
        }
        if (! $discount) {
            $discount = Discount::where('is_automatic', true)->where('is_active', true)->get()
                ->filter(fn (Discount $d) => ! self::discountError($d, $lines, $result->subtotal, $email ?? $customer?->email))
                ->sortByDesc(fn (Discount $d) => self::discountAmount($d, $lines))
                ->first();
        }
        if ($discount) {
            $result->discount = $discount;
            $result->discountTotal = self::applyDiscount($discount, $lines);
            $result->freeShipping = $discount->type === 'free_shipping';
        }

        $afterDiscount = $result->subtotal - $result->discountTotal;

        // Verzending
        $result->countryCode = strtoupper($countryCode ?: 'NL');
        $zone = ShippingZone::forCountry($result->countryCode);
        $result->shippingAvailable = $zone !== null;
        if ($zone) {
            $rates = $zone->rates->filter(fn (ShippingRate $r) => $r->appliesTo($afterDiscount))->values();
            $result->shippingRates = $rates;
            $rate = $rates->firstWhere('id', $shippingRateId) ?? $rates->sortBy('price')->first();
            if ($rate) {
                $result->shippingRate = $rate;
                $result->shippingTotal = $result->freeShipping ? 0 : $rate->price;
            }
        }

        $total = $afterDiscount + $result->shippingTotal;

        // Cadeaubon
        if ($giftCardCode) {
            $card = GiftCard::where('code', GiftCard::normalize($giftCardCode))->first();
            if (! $card || ! $card->isUsable()) {
                $result->giftCardError = __('Deze cadeaubon is ongeldig of heeft geen saldo meer.');
            } else {
                $result->giftCard = $card;
                $result->giftCardUsed = min($card->balance, $total);
                $total -= $result->giftCardUsed;
            }
        }

        // Shoptegoed (uit het spaarprogramma)
        $result->creditAvailable = max(0, (int) ($customer?->credit_balance ?? 0));
        if ($useCredit && $result->creditAvailable > 0) {
            $result->creditUsed = min($result->creditAvailable, $total);
            $total -= $result->creditUsed;
        }

        $result->total = max(0, $total);
        $result->taxTotal = self::tax($lines, $result->shippingTotal);

        return $result;
    }

    public static function discountError(Discount $discount, Collection $lines, int $subtotal, ?string $email): ?string
    {
        if (! $discount->isCurrentlyValid()) {
            return __('Deze kortingscode is niet (meer) geldig.');
        }
        if ($discount->min_subtotal && $subtotal < $discount->min_subtotal) {
            return __('Deze code geldt vanaf :amount.', ['amount' => money($discount->min_subtotal)]);
        }
        if (self::eligibleLines($discount, $lines)->isEmpty()) {
            return __('Deze code geldt niet voor de producten in je winkelwagen.');
        }
        if ($discount->once_per_customer && $email) {
            $used = Order::where('discount_id', $discount->id)
                ->where('email', $email)
                ->whereIn('financial_status', ['paid', 'partially_refunded', 'pending'])
                ->exists();
            if ($used) {
                return __('Je hebt deze code al eens gebruikt.');
            }
        }

        return null;
    }

    public static function eligibleLines(Discount $discount, Collection $lines): Collection
    {
        $ids = array_map('intval', (array) $discount->target_ids);

        return match ($discount->applies_to) {
            'products' => $lines->filter(fn (CartLine $l) => in_array($l->variant->product_id, $ids, true)),
            'collections' => $lines->filter(fn (CartLine $l) => $l->product()->collections->pluck('id')->intersect($ids)->isNotEmpty()),
            default => $lines,
        };
    }

    public static function discountAmount(Discount $discount, Collection $lines): int
    {
        $eligible = (int) self::eligibleLines($discount, $lines)->sum(fn (CartLine $l) => $l->total());

        return match ($discount->type) {
            'percentage' => (int) round($eligible * min(100, $discount->value) / 100),
            'fixed' => min($discount->value, $eligible),
            default => 0,
        };
    }

    /** Verdeelt de korting naar rato over de regels (nodig voor btw en terugbetalingen). */
    private static function applyDiscount(Discount $discount, Collection $lines): int
    {
        $amount = self::discountAmount($discount, $lines);
        if ($amount <= 0) {
            return 0;
        }
        $eligible = self::eligibleLines($discount, $lines)->values();
        $base = (int) $eligible->sum(fn (CartLine $l) => $l->total());
        $left = $amount;
        foreach ($eligible as $i => $line) {
            $share = $i === $eligible->count() - 1 ? $left : (int) floor($amount * $line->total() / max(1, $base));
            $line->discountAllocated = $share;
            $left -= $share;
        }

        return $amount;
    }

    /** Btw zit in de prijs: btw = bedrag × tarief / (100 + tarief). Verzending volgt de verhouding van de regels. */
    public static function tax(Collection $lines, int $shipping): int
    {
        $tax = 0.0;
        $base = 0;
        foreach ($lines as $line) {
            $net = $line->totalAfterDiscount();
            $rate = $line->taxRate();
            $tax += $net * $rate / (100 + $rate);
            $base += $net;
        }
        if ($shipping > 0 && $base > 0) {
            foreach ($lines as $line) {
                $rate = $line->taxRate();
                $part = $shipping * $line->totalAfterDiscount() / $base;
                $tax += $part * $rate / (100 + $rate);
            }
        }

        return (int) round($tax);
    }
}
