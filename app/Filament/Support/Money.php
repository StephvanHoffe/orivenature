<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;

/** Bedragen staan in centen in de database; in het beheer typ je gewoon "37,95". */
class Money
{
    public static function input(string $name): TextInput
    {
        return TextInput::make($name)
            ->prefix('€')
            ->inputMode('decimal')
            ->rule('regex:/^\d+([.,]\d{1,2})?$/')
            ->formatStateUsing(fn ($state) => $state === null || $state === '' ? null : number_format($state / 100, 2, ',', ''))
            ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? null : to_cents($state));
    }
}
