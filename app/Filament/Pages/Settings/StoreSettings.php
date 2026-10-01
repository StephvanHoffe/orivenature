<?php

namespace App\Filament\Pages\Settings;

use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class StoreSettings extends SettingsPage
{
    protected static array $groups = ['store'];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $navigationLabel = 'Winkelgegevens';

    protected static ?string $title = 'Winkelgegevens';

    protected static ?string $slug = 'instellingen/winkel';

    protected static ?int $navigationSort = 1;

    protected function fields(): array
    {
        return [
            Section::make('Bedrijf')->columns(2)->schema([
                TextInput::make('store.name')->label('Winkelnaam')->required(),
                TextInput::make('store.legal_name')->label('Bedrijfsnaam (voor facturen)'),
                TextInput::make('store.email')->label('E-mailadres klantenservice')->email()->required(),
                TextInput::make('store.phone')->label('Telefoon'),
                TextInput::make('store.whatsapp')->label('WhatsApp-nummer')->helperText('Internationaal zonder + of spaties, bijv. 31612345678'),
                Textarea::make('store.address')->label('Adres')->rows(2),
                TextInput::make('store.kvk')->label('KvK-nummer'),
                TextInput::make('store.vat_number')->label('Btw-nummer'),
            ]),
            Section::make('Logo')->columns(2)->schema([
                FileUpload::make('store.logo')->label('Logo')->image()->disk('public')->directory('branding')->helperText('Laat leeg om het standaardlogo te gebruiken.'),
                FileUpload::make('store.favicon')->label('Favicon')->image()->disk('public')->directory('branding'),
            ]),
            Section::make('Social media')->columns(2)->schema([
                TextInput::make('store.social.instagram')->label('Instagram')->url(),
                TextInput::make('store.social.tiktok')->label('TikTok')->url(),
                TextInput::make('store.social.facebook')->label('Facebook')->url(),
                TextInput::make('store.social.snapchat')->label('Snapchat')->url(),
                TextInput::make('store.reviews_url')->label('Reviews (bijv. Trustpilot)')->url(),
            ]),
        ];
    }
}
