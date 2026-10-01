<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['option_names'] = array_values(array_filter($data['option_names'] ?? [])) ?: null;
        $data['published_at'] ??= now();

        return $data;
    }
}
