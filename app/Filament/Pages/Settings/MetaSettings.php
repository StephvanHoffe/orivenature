<?php

namespace App\Filament\Pages\Settings;

use App\Services\Meta;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use UnitEnum;

class MetaSettings extends SettingsPage
{
    protected static array $groups = ['meta'];

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $navigationLabel = 'Meta (Facebook & Instagram)';

    protected static ?string $title = 'Meta: Facebook & Instagram';

    protected static ?string $slug = 'marketing/meta';

    protected static ?int $navigationSort = 3;

    protected function fields(): array
    {
        $feed = url('/feeds/meta-catalog.xml');

        return [
            Section::make('Pixel en Conversions API')->description('Meet bezoeken, toevoegingen aan de winkelwagen en aankopen, zodat Meta je advertenties kan optimaliseren.')->columns(2)->schema([
                Text::make(new HtmlString('<div class="ob-help"><ol>
                    <li>Ga in <a href="https://business.facebook.com/events_manager2" target="_blank">Meta Evenementenbeheer</a> naar je dataset (pixel) en kopieer de <strong>Dataset-ID</strong>.</li>
                    <li>Kies <strong>Instellingen &gt; Conversions API &gt; Toegangstoken genereren</strong> en plak de token hieronder.</li>
                    <li>Klik op <strong>Test-event sturen</strong>. Gebruik eventueel een testcode uit het tabblad Test-evenementen.</li>
                </ol></div>'))->columnSpanFull(),
                TextInput::make('meta.pixel_id')->label('Pixel- / dataset-ID')->rule('nullable')->rule('regex:/^\d{6,20}$/'),
                TextInput::make('meta.capi_token')->label('Conversions API-toegangstoken')->password()->revealable(),
                TextInput::make('meta.test_event_code')->label('Testcode (optioneel)')->placeholder('TEST12345')
                    ->helperText('Alleen invullen tijdens het testen, daarna leegmaken.'),
                TextInput::make('meta.domain_verification')->label('Domeinverificatie-code')
                    ->helperText('Uit Bedrijfsinstellingen > Merkveiligheid > Domeinen (meta-tag methode).'),
            ]),
            Section::make('Productcatalogus')->description('Voor dynamische advertenties en shoppen op Instagram.')->schema([
                Toggle::make('meta.catalog_enabled')->label('Catalogusfeed aanzetten'),
                Text::make(new HtmlString('<div class="ob-help">Feed-URL: <code>'.e($feed).'</code><br>
                    Voeg in <a href="https://business.facebook.com/commerce" target="_blank">Commercebeheer</a> een gegevensbron toe met <strong>Geplande feed</strong> en deze URL (bijv. dagelijks bijwerken).
                    De feed bevat alle actieve producten met prijs, voorraad en foto\'s.</div>')),
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')->label('Test-event sturen')->icon(Heroicon::OutlinedPaperAirplane)->color('gray')
                ->action(function () {
                    [$ok, $message] = Meta::sendTestEvent();
                    Notification::make()->{$ok ? 'success' : 'danger'}()->title($ok ? 'Koppeling werkt' : 'Niet gelukt')->body($message)->persistent()->send();
                }),
            Action::make('feed')->label('Feed bekijken')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/feeds/meta-catalog.xml'), shouldOpenInNewTab: true),
        ];
    }
}
