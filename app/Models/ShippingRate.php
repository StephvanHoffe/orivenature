<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function appliesTo(int $subtotal): bool
    {
        return $this->is_active
            && ($this->min_subtotal === null || $subtotal >= $this->min_subtotal)
            && ($this->max_subtotal === null || $subtotal <= $this->max_subtotal);
    }
}
