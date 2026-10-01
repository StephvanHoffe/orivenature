<?php

namespace App\Filament\Resources\Checkouts\Pages;

use App\Filament\Resources\Checkouts\CheckoutResource;
use Filament\Resources\Pages\ListRecords;

class ListCheckouts extends ListRecords
{
    protected static string $resource = CheckoutResource::class;

    public function getSubheading(): ?string
    {
        return settings('notifications.abandoned_enabled')
            ? 'Klanten krijgen automatisch na '.settings('notifications.abandoned_delay_hours').' uur een herinnering (instelbaar bij Instellingen > E-mails).'
            : 'Automatische herinneringen staan uit (Instellingen > E-mails).';
    }
}
