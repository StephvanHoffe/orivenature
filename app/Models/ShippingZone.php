<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['countries' => 'array'];

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class)->orderBy('position')->orderBy('price');
    }

    public static function forCountry(string $countryCode): ?self
    {
        return static::orderBy('position')->get()
            ->first(fn (self $zone) => in_array(strtoupper($countryCode), $zone->countries ?? [], true));
    }

    public static function allCountries(): array
    {
        return static::all()->pluck('countries')->flatten()->unique()->values()->all();
    }
}
