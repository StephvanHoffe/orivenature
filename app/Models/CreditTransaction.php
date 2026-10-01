<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditTransaction extends Model
{
    protected $guarded = ['id'];

    public const TYPES = [
        'loyalty' => 'Punten ingewisseld',
        'order' => 'Gebruikt bij bestelling',
        'refund' => 'Terugbetaling',
        'adjust' => 'Correctie',
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
