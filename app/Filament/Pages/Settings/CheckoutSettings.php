<?php

namespace App\Filament\Pages\Settings;

use App\Models\Page;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class CheckoutSettings extends SettingsPage
{
    protected static array $groups = ['checkout', 'orders'];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $navigationLabel = 'Afrekenen';

    protected static ?string $title = 'Afrekenen en bestellingen';

    protected static ?string $slug = 'instellingen/afrekenen';

    protected static ?int $navigationSort = 4;

    protected function fields(): array
    {
        $pages = fn () => Page::orderBy('title')->pluck('title', 'handle')->all();

        return [
            Section::make('Afrekenpagina')->columns(2)->schema([
                Toggle::make('checkout.require_phone')->label('Telefoonnummer verplicht'),
                Toggle::make('checkout.newsletter_default')->label('Nieuwsbrief standaard aangevinkt')
                    ->helperText('Volgens de AVG moet de klant zelf kiezen; laat dit bij voorkeur uit.'),
                Toggle::make('checkout.order_note')->label('Opmerkingenveld tonen'),
                Select::make('checkout.terms_page')->label('Pagina algemene voorwaarden')->options($pages),
                Select::make('checkout.privacy_page')->label('Pagina privacybeleid')->options($pages),
            ]),
            Section::make('Bestelnummers')->schema([
                TextInput::make('orders.start_number')->label('Volgende bestelnummer begint bij')->integer()->minValue(1)
                    ->helperText('Wordt automatisch opgehoogd na het importeren van je Shopify-bestellingen.'),
            ]),
        ];
    }
}
