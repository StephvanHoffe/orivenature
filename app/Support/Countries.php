<?php

namespace App\Support;

class Countries
{
    public const LIST = [
        'NL' => 'Nederland', 'BE' => 'België', 'DE' => 'Duitsland', 'FR' => 'Frankrijk', 'LU' => 'Luxemburg',
        'AT' => 'Oostenrijk', 'DK' => 'Denemarken', 'ES' => 'Spanje', 'IT' => 'Italië', 'PT' => 'Portugal',
        'IE' => 'Ierland', 'SE' => 'Zweden', 'FI' => 'Finland', 'PL' => 'Polen', 'CZ' => 'Tsjechië',
        'SK' => 'Slowakije', 'SI' => 'Slovenië', 'HU' => 'Hongarije', 'HR' => 'Kroatië', 'GR' => 'Griekenland',
        'RO' => 'Roemenië', 'BG' => 'Bulgarije', 'EE' => 'Estland', 'LV' => 'Letland', 'LT' => 'Litouwen',
        'MT' => 'Malta', 'CY' => 'Cyprus', 'GB' => 'Verenigd Koninkrijk', 'CH' => 'Zwitserland', 'NO' => 'Noorwegen',
    ];

    public static function name(?string $code): string
    {
        return self::LIST[strtoupper((string) $code)] ?? (string) $code;
    }

    public static function formatAddress(array $a): string
    {
        $lines = array_filter([
            trim(($a['first_name'] ?? '').' '.($a['last_name'] ?? '')),
            $a['company'] ?? null,
            trim(($a['address1'] ?? '').' '.($a['address2'] ?? '')),
            trim(($a['zip'] ?? '').' '.($a['city'] ?? '')),
            self::name($a['country_code'] ?? null),
        ]);

        return implode("\n", $lines);
    }
}
