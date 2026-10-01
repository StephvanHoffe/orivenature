<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_automatic' => 'boolean',
        'once_per_customer' => 'boolean',
        'is_active' => 'boolean',
        'target_ids' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public const TYPES = ['percentage' => 'Percentage', 'fixed' => 'Vast bedrag', 'free_shipping' => 'Gratis verzending'];

    public const APPLIES_TO = ['all' => 'Hele bestelling', 'collections' => 'Specifieke collecties', 'products' => 'Specifieke producten'];

    public function isCurrentlyValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return $this->usage_limit === null || $this->usage_count < $this->usage_limit;
    }

    public function summary(): string
    {
        return match ($this->type) {
            'percentage' => $this->value.'% korting',
            'fixed' => money($this->value).' korting',
            'free_shipping' => 'Gratis verzending',
            default => $this->title,
        };
    }
}
