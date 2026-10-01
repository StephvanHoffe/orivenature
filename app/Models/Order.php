<?php

namespace App\Models;

use App\Support\Countries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'tags' => 'array',
        'accepts_marketing' => 'boolean',
        'placed_at' => 'datetime',
        'paid_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'points_awarded_at' => 'datetime',
    ];

    public const FINANCIAL_STATUSES = [
        'pending' => 'In afwachting',
        'paid' => 'Betaald',
        'partially_refunded' => 'Deels terugbetaald',
        'refunded' => 'Terugbetaald',
        'failed' => 'Mislukt',
        'expired' => 'Verlopen',
        'cancelled' => 'Geannuleerd',
    ];

    public const FULFILLMENT_STATUSES = [
        'unfulfilled' => 'Niet verzonden',
        'partially_fulfilled' => 'Deels verzonden',
        'fulfilled' => 'Verzonden',
    ];

    public const STATUSES = ['open' => 'Open', 'cancelled' => 'Geannuleerd', 'archived' => 'Gearchiveerd'];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->token ??= Str::random(48);
            $order->number ??= static::nextNumber();
        });
    }

    public static function nextNumber(): int
    {
        $start = (int) settings('orders.start_number', 1001);

        return max($start, ((int) static::max('number')) + 1);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->latest()->latest('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class)->latest();
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(Fulfillment::class)->latest();
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function shippingRate(): BelongsTo
    {
        return $this->belongsTo(ShippingRate::class);
    }

    /** Status zoals de klant hem ziet: [tekst, toon] met toon wait, busy, done of off. */
    public function customerStatus(): array
    {
        return match (true) {
            $this->status === 'cancelled' => [__('Geannuleerd'), 'off'],
            $this->financial_status === 'refunded' => [__('Terugbetaald'), 'off'],
            ! $this->paid_at => [__('Wacht op betaling'), 'wait'],
            $this->fulfillment_status === 'fulfilled' => [__('Verzonden'), 'done'],
            $this->fulfillment_status === 'partially_fulfilled' => [__('Deels verzonden'), 'busy'],
            default => [__('Wordt ingepakt'), 'busy'],
        };
    }

    /** Nog niet afgerond: wacht op betaling of verzending. */
    public function isInProgress(): bool
    {
        return $this->status !== 'cancelled' && $this->financial_status !== 'refunded' && $this->fulfillment_status !== 'fulfilled';
    }

    /**
     * Stappen voor de statusweergave.
     *
     * @return array<int, array{label: string, date: ?Carbon, state: string, text: ?string}>
     */
    public function timeline(): array
    {
        $shippedAt = $this->fulfillments->min('shipped_at');
        $steps = [['label' => __('Besteld'), 'date' => $this->placed_at, 'state' => 'done', 'text' => null]];
        if ($this->status === 'cancelled' && ! $this->paid_at) {
            $steps[] = ['label' => __('Geannuleerd'), 'date' => $this->cancelled_at, 'state' => 'off',
                'text' => $this->cancel_reason === 'payment' ? __('De betaling is niet gelukt of afgebroken.') : $this->cancel_reason];

            return $steps;
        }
        $steps[] = ['label' => __('Betaald'), 'date' => $this->paid_at, 'state' => $this->paid_at ? 'done' : 'current',
            'text' => $this->paid_at ? $this->paymentMethodLabel() : __('We wachten nog op je betaling.')];
        $steps[] = ['label' => __('Ingepakt'), 'date' => $shippedAt, 'state' => $shippedAt ? 'done' : ($this->paid_at ? 'current' : 'todo'),
            'text' => ! $shippedAt && $this->paid_at ? __('We maken je pakket klaar.') : null];
        $steps[] = ['label' => $this->fulfillment_status === 'partially_fulfilled' ? __('Deels verzonden') : __('Verzonden'), 'date' => $shippedAt,
            'state' => $shippedAt ? 'done' : 'todo',
            'text' => $shippedAt ? __('Je pakket is onderweg.') : null];
        if ($this->status === 'cancelled') {
            $steps[] = ['label' => __('Geannuleerd'), 'date' => $this->cancelled_at, 'state' => 'off', 'text' => $this->cancel_reason];
        } elseif ($this->refunded_total > 0) {
            $steps[] = ['label' => $this->financial_status === 'refunded' ? __('Terugbetaald') : __('Deels terugbetaald'), 'date' => $this->refunds->max('created_at'),
                'state' => 'done', 'text' => money($this->refunded_total)];
        }

        return $steps;
    }

    public function paymentMethodLabel(): ?string
    {
        $method = $this->payments->firstWhere('status', 'paid')?->method;
        if ($method) {
            return __('Betaald met :method', ['method' => self::PAYMENT_METHODS[$method] ?? ucfirst($method)]);
        }
        if ($this->total === 0 && ($this->credit_used || $this->gift_card_used)) {
            return __('Betaald met tegoed of cadeaubon');
        }

        return null;
    }

    public const PAYMENT_METHODS = [
        'ideal' => 'iDEAL | Wero', 'creditcard' => 'creditcard', 'klarna' => 'Klarna', 'klarnapaylater' => 'Klarna', 'klarnasliceit' => 'Klarna',
        'paypal' => 'PayPal', 'applepay' => 'Apple Pay', 'bancontact' => 'Bancontact', 'banktransfer' => 'bankoverschrijving', 'test' => 'testbetaling',
    ];

    /** Link om een openstaande betaling af te ronden (zolang de betaling nog open staat). */
    public function checkoutUrl(): ?string
    {
        if ($this->paid_at || $this->status === 'cancelled') {
            return null;
        }
        $payment = $this->payments->firstWhere('status', 'open');

        return $payment?->data['checkout_url'] ?? null;
    }

    public function getNameAttribute(): string
    {
        return '#'.$this->number;
    }

    public function customerName(): string
    {
        $a = $this->shipping_address ?? $this->billing_address ?? [];

        return trim(($a['first_name'] ?? '').' '.($a['last_name'] ?? '')) ?: ($this->customer?->name ?? $this->email);
    }

    public function isPaid(): bool
    {
        return in_array($this->financial_status, ['paid', 'partially_refunded', 'refunded'], true);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /** Totaal dat de klant heeft betaald, inclusief cadeaubon en shoptegoed. */
    public function grandTotal(): int
    {
        return $this->total + $this->gift_card_used + $this->credit_used;
    }

    public function refundableAmount(): int
    {
        return $this->isPaid() ? max(0, $this->grandTotal() - $this->refunded_total) : 0;
    }

    public function statusUrl(): string
    {
        return url('/orders/'.$this->token);
    }

    public function formattedShippingAddress(): string
    {
        return $this->shipping_address ? Countries::formatAddress($this->shipping_address) : '';
    }

    public function formattedBillingAddress(): string
    {
        return $this->billing_address ? Countries::formatAddress($this->billing_address) : $this->formattedShippingAddress();
    }

    /** Btw-specificatie per tarief (prijzen zijn inclusief btw). */
    public function taxBreakdown(): array
    {
        $rates = [];
        foreach ($this->items as $item) {
            $rate = (string) (float) $item->tax_rate;
            $rates[$rate] = ($rates[$rate] ?? 0) + $item->total;
        }
        $result = [];
        foreach ($rates as $rate => $gross) {
            $result[$rate] = (int) round($gross - $gross / (1 + ((float) $rate) / 100));
        }

        return $result;
    }

    public function log(string $type, string $message, array $data = [], ?int $userId = null): OrderEvent
    {
        return $this->events()->create([
            'type' => $type,
            'message' => $message,
            'data' => $data ?: null,
            'user_id' => $userId ?? auth('web')->id(),
        ]);
    }
}
