<?php

namespace App\Models;

use App\Support\Countries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_default' => 'boolean'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function toOrderAddress(): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'company' => $this->company,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'zip' => $this->zip,
            'city' => $this->city,
            'country_code' => $this->country_code,
            'phone' => $this->phone,
        ];
    }

    public function formatted(): string
    {
        return Countries::formatAddress($this->toOrderAddress());
    }
}
