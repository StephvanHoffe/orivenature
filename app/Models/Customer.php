<?php

namespace App\Models;

use App\Notifications\CustomerResetPassword;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use Notifiable;

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'accepts_marketing' => 'boolean',
        'marketing_consent_at' => 'datetime',
        'last_login_at' => 'datetime',
        'tags' => 'array',
        'password' => 'hashed',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)->orderByDesc('is_default')->orderBy('id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest('placed_at');
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class)->latest();
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class)->latest();
    }

    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name) ?: $this->email;
    }

    public function defaultAddress(): ?CustomerAddress
    {
        return $this->addresses->first();
    }

    public function hasAccount(): bool
    {
        return $this->password !== null;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomerResetPassword($token));
    }
}
