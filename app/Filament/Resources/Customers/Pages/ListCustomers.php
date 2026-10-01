<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Services\Import\ShopifyCsvImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')->label('Importeren')->icon(Heroicon::OutlinedArrowUpTray)->color('gray')
                ->modalDescription('Upload de klanten-export uit Shopify (Klanten > Exporteren > CSV). Bestaande klanten worden bijgewerkt op basis van het e-mailadres.')
                ->schema([
                    FileUpload::make('file')->label('CSV-bestand')->disk('local')->directory('imports')->required()
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel']),
                ])
                ->action(function (array $data) {
                    $stats = (new ShopifyCsvImporter)->importCustomers(Storage::disk('local')->path($data['file']));
                    Storage::disk('local')->delete($data['file']);
                    Notification::make()->success()->title('Import klaar')
                        ->body("{$stats['created']} nieuw, {$stats['updated']} bijgewerkt, {$stats['skipped']} overgeslagen.")->send();
                }),
            Action::make('export')->label('Exporteren')->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->action(fn () => CustomerResource::export($this->getFilteredTableQuery()->cursor())),
            CreateAction::make()->label('Klant toevoegen'),
        ];
    }
}
