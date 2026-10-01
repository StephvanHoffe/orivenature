<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GiftCard extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean', 'expires_at' => 'datetime'];

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(implode('-', str_split(Str::random(16), 4)));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public static function normalize(string $code): string
    {
        return strtoupper(trim(preg_replace('/\s+/', '', $code)));
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(GiftCardTransaction::class)->latest();
    }

    public function isUsable(): bool
    {
        return $this->is_active && $this->balance > 0 && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function maskedCode(): string
    {
        return '•••• '.substr($this->code, -4);
    }
}
