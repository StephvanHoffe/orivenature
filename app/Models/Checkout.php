<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checkout extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['cart' => 'array', 'reminder_sent_at' => 'datetime', 'completed_at' => 'datetime'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function recoveryUrl(): string
    {
        return url('/cart/recover/'.$this->token);
    }
}
