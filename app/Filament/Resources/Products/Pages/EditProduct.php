<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Models\Redirect;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/** @property Product $record */
class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    private ?string $oldHandle = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['option_names'] = array_values(array_filter($data['option_names'] ?? [])) ?: null;
        $this->oldHandle = $this->record->handle;

        return $data;
    }

    protected function afterSave(): void
    {
        // Oude URL blijft werken
        if ($this->oldHandle && $this->oldHandle !== $this->record->handle) {
            Redirect::updateOrCreate(['from_path' => '/products/'.$this->oldHandle], ['to_path' => '/products/'.$this->record->handle]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label('Bekijk in winkel')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn () => $this->record->url(), shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }
}
