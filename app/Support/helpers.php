<?php

use App\Support\Settings;

if (! function_exists('money')) {
    /** Bedrag in centen als "€37,95". */
    function money(?int $cents, bool $symbol = true): string
    {
        $value = number_format(($cents ?? 0) / 100, 2, ',', '.');

        return $symbol ? '€'.$value : $value;
    }
}

if (! function_exists('settings')) {
    function settings(?string $key = null, mixed $default = null): mixed
    {
        $settings = app(Settings::class);

        return $key === null ? $settings : $settings->get($key, $default);
    }
}

if (! function_exists('to_cents')) {
    /** "37,95" of "37.95" of 37.95 → 3795 */
    function to_cents(mixed $value): int
    {
        if (is_int($value)) {
            return $value * 100;
        }
        $value = str_replace([' ', '€'], '', (string) $value);
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
        }

        return (int) round(((float) str_replace(',', '.', $value)) * 100);
    }
}
