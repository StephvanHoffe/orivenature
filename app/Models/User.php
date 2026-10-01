<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    public const ROLES = [
        'owner' => 'Eigenaar',
        'admin' => 'Beheerder',
        'staff' => 'Medewerker',
    ];

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /** Eigenaar en beheerder mogen instellingen wijzigen; medewerkers alleen de dagelijkse zaken. */
    public function canManageSettings(): bool
    {
        return in_array($this->role, ['owner', 'admin'], true);
    }
}
