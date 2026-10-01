<?php

namespace App\Filament\Pages\Settings;

use App\Models\Product;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ThemeSettings extends SettingsPage
{
    protected static array $groups = ['store', 'products', 'cart'];

    protected static string|UnitEnum|null $navigationGroup = 'Webshop';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?string $navigationLabel = 'Winkelweergave';

    protected static ?string $title = 'Winkelweergave';

    protected static ?string $slug = 'webshop/weergave';

    protected static ?int $navigationSort = 7;

    public static function icons(): array
    {
        return ['truck' => 'Vrachtwagen', 'clock' => 'Klok', 'leaf' => 'Blad', 'check' => 'Vinkje', 'earth' => 'Wereld', 'star' => 'Ster', 'gift' => 'Cadeau', 'sparkle' => 'Glinstering', 'coin' => 'Munt', 'user' => 'Persoon', 'bag' => 'Tas', 'phone' => 'Telefoon'];
    }

    protected function fields(): array
    {
        return [
            Section::make('Aankondigingsbalk')->columns(2)->schema([
                TextInput::make('store.announcement_1')->label('Tekst 1'),
                TextInput::make('store.announcement_2')->label('Tekst 2'),
                Toggle::make('store.countdown')->label('Afteller tonen ("nog 3u 12m")'),
                TextInput::make('store.cutoff_hour')->label('Besteld voor (uur)')->integer()->minValue(0)->maxValue(23)->suffix(':00'),
            ]),
            Section::make('Menu en WhatsApp')->columns(2)->schema([
                TextInput::make('store.nav_highlight')->label('Menu-item met label')->helperText('Tekst van het menu-item dat een label krijgt'),
                TextInput::make('store.nav_highlight_label')->label('Label'),
                Toggle::make('store.whatsapp_bubble')->label('WhatsApp-knop tonen'),
                TextInput::make('store.whatsapp_tip')->label('Tekst bij de WhatsApp-knop'),
            ]),
            Section::make('Productpagina')->columns(2)->schema([
                TextInput::make('products.badge')->label('Keurmerk-label'),
                TextInput::make('products.bestseller_label')->label('Bestseller-label'),
                TextInput::make('products.save_text')->label('Tekst bij grotere verpakking')->columnSpanFull()
                    ->helperText('[amount] = besparing, [size] = kleinere variant'),
                TextInput::make('products.low_stock_threshold')->label('"Bijna op" vanaf')->integer()->suffix('stuks'),
                Repeater::make('products.perks')->label('Voordelen onder de knop')->columnSpanFull()->grid(2)->reorderable()
                    ->schema([
                        Select::make('icon')->label('Icoon')->options(self::icons())->required(),
                        TextInput::make('text')->label('Tekst')->required(),
                    ]),
            ]),
            Section::make('Winkelwagen')->columns(2)->schema([
                Toggle::make('cart.open_on_add')->label('Winkelwagen openen na toevoegen'),
                TagsInput::make('cart.perks')->label('Voordelen in de winkelwagen'),
                Textarea::make('cart.note')->label('Tekst onder de totalen')->rows(2)->columnSpanFull(),
                Repeater::make('cart.upsells')->label('Aanbevolen in de winkelwagen')->columnSpanFull()->grid(2)->reorderable()
                    ->schema([
                        Select::make('product')->label('Product')->options(fn () => Product::orderBy('title')->pluck('title', 'handle')->all())->required(),
                        TextInput::make('label')->label('Kopje'),
                    ]),
            ]),
        ];
    }
}
