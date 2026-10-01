<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyTransaction extends Model
{
    protected $guarded = ['id'];

    public const TYPES = [
        'earn' => 'Verdiend',
        'redeem' => 'Ingewisseld',
        'adjust' => 'Correctie',
        'signup' => 'Welkomstbonus',
        'revert' => 'Teruggedraaid',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
