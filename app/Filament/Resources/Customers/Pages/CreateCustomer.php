<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (filled($data['new_password'] ?? null)) {
            $data['password'] = $data['new_password'];
        }
        unset($data['new_password']);
        if (! empty($data['accepts_marketing'])) {
            $data['marketing_consent_at'] = now();
        }

        return $data;
    }
}
