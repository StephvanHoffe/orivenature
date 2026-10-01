<?php

namespace App\Filament\Resources\Inventory\Pages;

use App\Filament\Resources\Inventory\InventoryResource;
use Filament\Resources\Pages\ListRecords;

class ListInventory extends ListRecords
{
    protected static string $resource = InventoryResource::class;

    public function getSubheading(): ?string
    {
        return 'Pas de voorraad direct in de tabel aan. Varianten zonder "Bijhouden" zijn altijd te bestellen.';
    }
}
