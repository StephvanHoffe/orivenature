<?php

namespace App\Filament\Pages;

use App\Services\Import\ShopifyCsvImporter;
use App\Services\Import\ShopifyImporter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use UnitEnum;

class ImportShopify extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Instellingen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownOnSquareStack;

    protected static ?string $navigationLabel = 'Overzetten uit Shopify';

    protected static ?string $title = 'Overzetten uit Shopify';

    protected static ?string $slug = 'instellingen/shopify-import';

    protected static ?int $navigationSort = 30;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->canManageSettings();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('1. Winkelgegevens ophalen')->description('Producten, collecties, blog, pagina\'s en foto\'s worden opgehaald uit je openbare Shopify-winkel.')->schema([
                Text::make(new HtmlString('<div class="ob-help">Bestaande items worden bijgewerkt (op basis van de URL), er wordt niets verwijderd. Dit kan een paar minuten duren door het downloaden van de foto\'s.</div>')),
                Actions::make([$this->storeImportAction()]),
            ]),
            Section::make('2. Klanten en bestellingen')->description('Upload de CSV-exports uit Shopify.')->schema([
                Text::make(new HtmlString('<div class="ob-help"><ol>
                    <li>Shopify-beheer &gt; <strong>Klanten</strong> &gt; Exporteren &gt; Alle klanten &gt; <em>CSV voor Excel, Numbers of andere spreadsheetprogramma\'s</em>.</li>
                    <li>Shopify-beheer &gt; <strong>Bestellingen</strong> &gt; Exporteren &gt; Alle bestellingen &gt; zelfde CSV-formaat.</li>
                    <li>Upload eerst de klanten, daarna de bestellingen. Bestellingen die al bestaan (zelfde nummer) worden overgeslagen.</li>
                </ol></div>')),
                Actions::make([$this->customersAction(), $this->ordersAction()]),
            ]),
        ]);
    }

    private function storeImportAction(): Action
    {
        return Action::make('importStore')->label('Gegevens ophalen')->icon(Heroicon::OutlinedCloudArrowDown)
            ->schema([
                TextInput::make('url')->label('Adres van de Shopify-winkel')->url()->required()->default('https://www.orivenature.com'),
                CheckboxList::make('parts')->label('Onderdelen')->required()->columns(2)
                    ->options(['products' => 'Producten', 'collections' => 'Collecties', 'blog' => 'Blog', 'pages' => "Pagina's", 'media' => "Foto's en video startpagina", 'defaults' => "Menu's, verzending en welkomstkorting"])
                    ->default(['products', 'collections', 'blog', 'pages', 'media', 'defaults']),
            ])
            ->action(function (array $data) {
                @set_time_limit(600);
                $log = [];
                $importer = new ShopifyImporter($data['url'], function ($m) use (&$log) {
                    $log[] = $m;
                });
                try {
                    foreach (['products' => 'importProducts', 'collections' => 'importCollections', 'blog' => 'importBlog', 'pages' => 'importPages', 'media' => 'importHomeMedia', 'defaults' => 'setupStoreDefaults'] as $part => $method) {
                        if (in_array($part, $data['parts'], true)) {
                            $importer->{$method}();
                        }
                    }
                    Notification::make()->success()->title('Import klaar')->body(implode("\n", array_slice($log, -12)))->persistent()->send();
                } catch (\Throwable $e) {
                    report($e);
                    Notification::make()->danger()->title('Import gestopt')->body($e->getMessage())->persistent()->send();
                }
            });
    }

    private function upload(): FileUpload
    {
        return FileUpload::make('file')->label('CSV-bestand')->disk('local')->directory('imports')->required()
            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel']);
    }

    private function customersAction(): Action
    {
        return Action::make('customers')->label('Klanten importeren')->icon(Heroicon::OutlinedUsers)->color('gray')
            ->schema([$this->upload()])
            ->action(function (array $data) {
                $stats = (new ShopifyCsvImporter)->importCustomers(Storage::disk('local')->path($data['file']));
                Storage::disk('local')->delete($data['file']);
                Notification::make()->success()->title('Klanten geïmporteerd')
                    ->body("{$stats['created']} nieuw, {$stats['updated']} bijgewerkt, {$stats['skipped']} overgeslagen.")->persistent()->send();
            });
    }

    private function ordersAction(): Action
    {
        return Action::make('orders')->label('Bestellingen importeren')->icon(Heroicon::OutlinedShoppingBag)->color('gray')
            ->schema([
                $this->upload(),
                Toggle::make('award_points')->label('Spaarpunten toekennen voor betaalde bestellingen')
                    ->helperText('Bestaande klanten starten dan meteen met punten uit hun eerdere aankopen.'),
            ])
            ->action(function (array $data) {
                @set_time_limit(600);
                $stats = (new ShopifyCsvImporter)->importOrders(Storage::disk('local')->path($data['file']), (bool) ($data['award_points'] ?? false));
                Storage::disk('local')->delete($data['file']);
                Notification::make()->success()->title('Bestellingen geïmporteerd')
                    ->body("{$stats['created']} bestellingen ({$stats['items']} regels) geïmporteerd, {$stats['skipped']} overgeslagen.")->persistent()->send();
            });
    }
}
