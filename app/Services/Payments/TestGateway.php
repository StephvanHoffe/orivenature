<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderService;
use Illuminate\Support\Str;

/**
 * Testbetaling zonder echte betaalprovider. Alleen actief als dat in de instellingen
 * aan staat; bedoeld om de winkel te testen voordat Mollie gekoppeld is.
 */
class TestGateway implements PaymentGateway
{
    public function createPayment(Order $order): string
    {
        $payment = $order->payments()->create([
            'provider' => 'test',
            'provider_id' => 'test_'.Str::random(10),
            'amount' => $order->total,
            'status' => 'open',
        ]);

        return route('payments.test', ['payment' => $payment->provider_id]);
    }

    public function complete(Payment $payment, string $status): Payment
    {
        $payment->fill(['status' => $status, 'method' => 'test', 'paid_at' => $status === 'paid' ? now() : null])->save();
        $orders = app(OrderService::class);
        $status === 'paid' ? $orders->markPaid($payment->order, $payment) : $orders->markPaymentFailed($payment->order, $status);

        return $payment;
    }

    public function refresh(Payment $payment): Payment
    {
        return $payment;
    }

    public function refund(Payment $payment, int $amount, ?string $description = null): ?string
    {
        return 'test_refund_'.Str::random(8);
    }
}
