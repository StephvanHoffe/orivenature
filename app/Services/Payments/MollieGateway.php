<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderService;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Http\Data\Address;
use Mollie\Api\Http\Data\DataCollection;
use Mollie\Api\Http\Data\Money;
use Mollie\Api\Http\Data\OrderLine;
use Mollie\Api\Http\Requests\CreatePaymentRefundRequest;
use Mollie\Api\Http\Requests\CreatePaymentRequest;
use Mollie\Api\Http\Requests\GetPaymentRequest;
use Mollie\Api\MollieApiClient;

class MollieGateway implements PaymentGateway
{
    private MollieApiClient $client;

    public function __construct(string $apiKey)
    {
        $this->client = new MollieApiClient;
        $this->client->setApiKey($apiKey);
    }

    private static function money(int $cents): Money
    {
        return new Money(currency: 'EUR', value: number_format($cents / 100, 2, '.', ''));
    }

    public function createPayment(Order $order): string
    {
        $order->loadMissing('items');
        $params = [
            'description' => __('Bestelling :number', ['number' => $order->name]),
            'amount' => self::money($order->total),
            'redirectUrl' => route('checkout.return', $order->token),
            'webhookUrl' => app()->isLocal() ? null : route('webhooks.mollie'),
            'locale' => 'nl_NL',
            'metadata' => ['order_id' => $order->id, 'order_number' => $order->number],
        ];

        try {
            // Met orderregels en adressen kan de klant ook met Klarna betalen
            $mollie = $this->client->send(new CreatePaymentRequest(...$params + [
                'lines' => $this->lines($order),
                'billingAddress' => $this->address($order, $order->billing_address ?? $order->shipping_address),
                'shippingAddress' => $this->address($order, $order->shipping_address),
            ]));
        } catch (ApiException $e) {
            report($e);
            $mollie = $this->client->send(new CreatePaymentRequest(...$params));
        }

        $order->payments()->create([
            'provider' => 'mollie',
            'provider_id' => $mollie->id,
            'amount' => $order->total,
            'status' => self::status($mollie->status),
            'data' => ['mode' => $mollie->mode ?? null],
        ]);

        return $mollie->getCheckoutUrl();
    }

    public function refresh(Payment $payment): Payment
    {
        $mollie = $this->client->send(new GetPaymentRequest(id: $payment->provider_id));
        $status = self::status($mollie->status);
        $payment->fill([
            'status' => $status,
            'method' => $mollie->method ?? $payment->method,
            'paid_at' => $mollie->isPaid() ? ($payment->paid_at ?? now()) : $payment->paid_at,
        ])->save();

        $orders = app(OrderService::class);
        if ($mollie->isPaid()) {
            $orders->markPaid($payment->order, $payment);
        } elseif ($mollie->isFailed() || $mollie->isExpired() || $mollie->isCanceled()) {
            $orders->markPaymentFailed($payment->order, $status);
        }

        return $payment;
    }

    public function refund(Payment $payment, int $amount, ?string $description = null): ?string
    {
        $refund = $this->client->send(new CreatePaymentRefundRequest(
            paymentId: $payment->provider_id,
            amount: self::money($amount),
            description: $description ?? __('Terugbetaling bestelling :number', ['number' => $payment->order->name]),
        ));

        return $refund->id;
    }

    private static function status(mixed $status): string
    {
        return $status instanceof \BackedEnum ? $status->value : (string) $status;
    }

    private function lines(Order $order): DataCollection
    {
        $lines = [];
        foreach ($order->items as $item) {
            $lines[] = new OrderLine(
                description: $item->variant_title ? "{$item->title} – {$item->variant_title}" : $item->title,
                quantity: $item->quantity,
                unitPrice: self::money($item->price),
                totalAmount: self::money($item->total),
                type: 'physical',
                discountAmount: $item->discount_allocated > 0 ? self::money($item->discount_allocated) : null,
                sku: $item->sku,
            );
        }
        if ($order->shipping_total > 0) {
            $lines[] = new OrderLine(description: $order->shipping_method ?: __('Verzending'), quantity: 1, unitPrice: self::money($order->shipping_total), totalAmount: self::money($order->shipping_total), type: 'shipping_fee');
        }
        if ($order->gift_card_used > 0) {
            $lines[] = new OrderLine(description: __('Cadeaubon'), quantity: 1, unitPrice: self::money(-$order->gift_card_used), totalAmount: self::money(-$order->gift_card_used), type: 'gift_card');
        }
        if ($order->credit_used > 0) {
            $lines[] = new OrderLine(description: __('Shoptegoed'), quantity: 1, unitPrice: self::money(-$order->credit_used), totalAmount: self::money(-$order->credit_used), type: 'store_credit');
        }

        return DataCollection::collect($lines);
    }

    private function address(Order $order, ?array $a): ?Address
    {
        if (! $a) {
            return null;
        }

        return new Address(
            givenName: $a['first_name'] ?? null,
            familyName: $a['last_name'] ?? null,
            organizationName: $a['company'] ?? null,
            streetAndNumber: $a['address1'] ?? null,
            streetAdditional: $a['address2'] ?? null,
            postalCode: $a['zip'] ?? null,
            email: $order->email,
            phone: $a['phone'] ?? $order->phone,
            city: $a['city'] ?? null,
            country: $a['country_code'] ?? null,
        );
    }
}
