<?php

namespace App\Services\Payments;

use RuntimeException;

class Payments
{
    public static function gateway(?string $provider = null): PaymentGateway
    {
        $key = (string) settings('payments.mollie_key');
        $provider ??= $key !== '' ? 'mollie' : 'test';

        return match ($provider) {
            'mollie' => new MollieGateway($key),
            'test' => self::testAllowed() ? new TestGateway : throw new RuntimeException(__('Er is nog geen betaalprovider ingesteld.')),
            default => throw new RuntimeException("Onbekende betaalprovider {$provider}"),
        };
    }

    public static function testAllowed(): bool
    {
        return (bool) settings('payments.test_gateway');
    }

    public static function isConfigured(): bool
    {
        return (string) settings('payments.mollie_key') !== '' || self::testAllowed();
    }
}
