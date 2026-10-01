<?php

namespace App\Filament\Pages\Settings;

use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use UnitEnum;

class SeoSettings extends SettingsPage
{
    protected static array $groups = ['seo'];

    protected static string|UnitEnum|null $navigationGroup = 'Webshop';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'Zoekmachines (SEO)';

    protected static ?string $title = 'Zoekmachines (SEO)';

    protected static ?string $slug = 'webshop/seo';

    protected static ?int $navigationSort = 8;

    protected function fields(): array
    {
        return [
            Section::make('Startpagina')->schema([
                TextInput::make('seo.title')->label('Titel')->maxLength(70)->helperText('Maximaal ± 60 tekens zichtbaar in Google.'),
                Textarea::make('seo.description')->label('Omschrijving')->rows(3)->maxLength(320),
                FileUpload::make('seo.og_image')->label('Afbeelding bij delen (social media)')->image()->disk('public')->directory('branding'),
            ]),
            Section::make('Google')->schema([
                Text::make(new HtmlString('<div class="ob-help">Sitemap voor Google Search Console: <code>'.e(url('/sitemap.xml')).'</code><br>
                    Elke product-, collectie-, blog- en pagina heeft eigen SEO-velden. Oude Shopify-adressen worden automatisch doorverwezen (Webshop &gt; Doorverwijzingen).</div>')),
            ]),
        ];
    }
}
