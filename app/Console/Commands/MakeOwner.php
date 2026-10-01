<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MakeOwner extends Command
{
    protected $signature = 'orive:eigenaar {email} {--name=Eigenaar} {--password= : Laat leeg om er een te laten maken}';

    protected $description = 'Maakt een eigenaar-account voor /beheer (of zet een nieuw wachtwoord)';

    public function handle(): int
    {
        $password = $this->option('password') ?: Str::password(16, symbols: false);
        $user = User::updateOrCreate(['email' => strtolower($this->argument('email'))], [
            'name' => $this->option('name'),
            'password' => Hash::make($password),
            'role' => 'owner',
            'is_active' => true,
        ]);
        $this->info("Account {$user->email} is klaar. Log in op ".url('/beheer'));
        if (! $this->option('password')) {
            $this->warn("Wachtwoord: {$password}  (wijzig dit na het inloggen)");
        }

        return self::SUCCESS;
    }
}
