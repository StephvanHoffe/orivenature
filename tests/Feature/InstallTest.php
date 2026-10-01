<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallTest extends TestCase
{
    use RefreshDatabase;

    public function test_installer_is_disabled_without_token(): void
    {
        config(['app.install_token' => null]);

        $this->get('/install')->assertNotFound();
    }

    public function test_installer_creates_owner_once(): void
    {
        config(['app.install_token' => 'geheim-123']);

        $this->get('/install')->assertOk()->assertSee('Webshop installeren');
        $this->post('/install', ['token' => 'fout', 'name' => 'Steph', 'email' => 'steph@example.com', 'password' => 'langwachtwoord'])->assertStatus(422)->assertSee('installatiecode klopt niet');
        $this->post('/install', ['token' => 'geheim-123', 'name' => 'Steph', 'email' => 'steph@example.com', 'password' => 'langwachtwoord'])->assertOk()->assertSee('Klaar!');

        $this->assertSame('owner', User::where('email', 'steph@example.com')->value('role'));
        $this->assertDatabaseHas('menus', ['handle' => 'main-menu']);
        $this->get('/install')->assertNotFound();
    }
}
