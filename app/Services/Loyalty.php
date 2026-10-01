<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Orivé spaarprogramma: punten bij bestellingen, in te wisselen voor shoptegoed.
 * Saldo's worden altijd herberekend uit de transacties, zodat ze nooit uit de pas lopen.
 */
class Loyalty
{
    public static function enabled(): bool
    {
        return (bool) settings('loyalty.enabled');
    }

    public static function pointsFor(int $cents): int
    {
        if (! self::enabled()) {
            return 0;
        }

        return (int) floor(max(0, $cents) / 100 * (float) settings('loyalty.points_per_euro', 1));
    }

    /** Punten over het productbedrag na korting, zonder het deel dat met tegoed is betaald. */
    public static function pointsForOrder(Order $order): int
    {
        return self::pointsFor($order->subtotal - $order->discount_total - $order->credit_used);
    }

    public static function earnText(int $cents): ?string
    {
        $points = self::pointsFor($cents);
        if ($points <= 0) {
            return null;
        }

        return str_replace('[points]', (string) $points, (string) settings('loyalty.earn_text'));
    }

    public static function rateText(): string
    {
        return __(':points punten = :value shoptegoed', [
            'points' => settings('loyalty.redeem_points'),
            'value' => money((int) settings('loyalty.redeem_value')),
        ]);
    }

    /** Hoeveel tegoed (centen) hoort bij een aantal punten, afgerond op hele blokken. */
    public static function creditFor(int $points): int
    {
        $block = max(1, (int) settings('loyalty.redeem_points', 100));

        return intdiv($points, $block) * (int) settings('loyalty.redeem_value', 500);
    }

    public static function redeemablePoints(Customer $customer): int
    {
        $block = max(1, (int) settings('loyalty.redeem_points', 100));

        return intdiv(max(0, $customer->points_balance), $block) * $block;
    }

    public static function award(Order $order): void
    {
        if (! self::enabled() || ! $order->customer_id || $order->points_awarded_at) {
            return;
        }
        $points = self::pointsForOrder($order);
        DB::transaction(function () use ($order, $points) {
            $order->forceFill(['points_earned' => $points, 'points_awarded_at' => now()])->save();
            if ($points > 0) {
                self::addPoints($order->customer, $points, 'earn', __('Bestelling :number', ['number' => $order->name]), $order->id);
                $order->log('loyalty', __(':points spaarpunten toegekend aan de klant.', ['points' => $points]));
            }
        });
    }

    public static function revert(Order $order, ?string $reason = null): void
    {
        if (! $order->customer_id || ! $order->points_awarded_at || $order->points_earned <= 0) {
            return;
        }
        DB::transaction(function () use ($order, $reason) {
            self::addPoints($order->customer, -$order->points_earned, 'revert', $reason ?? __('Bestelling :number geannuleerd of terugbetaald', ['number' => $order->name]), $order->id);
            $order->log('loyalty', __(':points spaarpunten teruggedraaid.', ['points' => $order->points_earned]));
            $order->forceFill(['points_earned' => 0])->save();
        });
    }

    public static function signupBonus(Customer $customer): void
    {
        $bonus = (int) settings('loyalty.signup_bonus', 0);
        if (! self::enabled() || $bonus <= 0 || $customer->loyaltyTransactions()->where('type', 'signup')->exists()) {
            return;
        }
        self::addPoints($customer, $bonus, 'signup', __('Welkom bij het spaarprogramma'));
    }

    /** Wisselt punten in voor shoptegoed. Geeft het tegoed in centen terug. */
    public static function redeem(Customer $customer, int $points): int
    {
        $min = (int) settings('loyalty.min_redeem', 100);

        return DB::transaction(function () use ($customer, $points, $min) {
            $customer = Customer::lockForUpdate()->findOrFail($customer->id);
            $block = max(1, (int) settings('loyalty.redeem_points', 100));
            $points = intdiv($points, $block) * $block;
            if (! self::enabled() || $points < max($min, $block) || $points > $customer->points_balance) {
                throw ValidationException::withMessages(['points' => __('Je hebt niet genoeg punten om in te wisselen.')]);
            }
            $credit = self::creditFor($points);
            self::addPoints($customer, -$points, 'redeem', __('Ingewisseld voor :value shoptegoed', ['value' => money($credit)]));
            self::addCredit($customer, $credit, 'loyalty', __(':points punten ingewisseld', ['points' => $points]));

            return $credit;
        });
    }

    public static function addPoints(Customer $customer, int $points, string $type, ?string $description = null, ?int $orderId = null, ?int $userId = null): LoyaltyTransaction
    {
        $transaction = $customer->loyaltyTransactions()->create([
            'points' => $points,
            'type' => $type,
            'description' => $description,
            'order_id' => $orderId,
            'user_id' => $userId,
        ]);
        $customer->forceFill(['points_balance' => (int) $customer->loyaltyTransactions()->sum('points')])->save();

        return $transaction;
    }

    public static function addCredit(Customer $customer, int $amount, string $type, ?string $description = null, ?int $orderId = null, ?int $userId = null): CreditTransaction
    {
        $transaction = $customer->creditTransactions()->create([
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'order_id' => $orderId,
            'user_id' => $userId,
        ]);
        $customer->forceFill(['credit_balance' => (int) $customer->creditTransactions()->sum('amount')])->save();

        return $transaction;
    }
}
