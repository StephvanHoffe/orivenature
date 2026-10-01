<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGateway
{
    /** Maakt een betaling aan en geeft de URL terug waar de klant naartoe moet. */
    public function createPayment(Order $order): string;

    /** Haalt de actuele status op en verwerkt die in de bestelling. */
    public function refresh(Payment $payment): Payment;

    /** Betaalt (een deel) terug. Geeft de id van de terugbetaling bij de provider terug. */
    public function refund(Payment $payment, int $amount, ?string $description = null): ?string;
}
