<?php

namespace Tests\Feature;

use App\Mail\OrderShipped;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/beheer')->assertRedirect('/beheer/login');
    }

    public function test_owner_can_open_every_admin_page(): void
    {
        Mail::fake();
        ['small' => $small] = $this->seedShop();
        $order = $this->buy($small, 1);
        $this->actingAs($this->owner());

        $pages = ['/beheer', '/beheer/bestellingen', '/beheer/bestellingen/'.$order->id, '/beheer/verlaten-winkelwagens',
            '/beheer/producten', '/beheer/producten/nieuw', '/beheer/producten/'.$small->product_id, '/beheer/collecties', '/beheer/voorraad', '/beheer/cadeaubonnen',
            '/beheer/klanten', '/beheer/klanten/'.$order->customer_id, '/beheer/nieuwsbrief', '/beheer/berichten',
            '/beheer/kortingen', '/beheer/kortingen/nieuw', '/beheer/marketing/spaarprogramma', '/beheer/marketing/meta',
            '/beheer/webshop/startpagina', '/beheer/paginas', '/beheer/blog', '/beheer/navigatie', '/beheer/doorverwijzingen', '/beheer/webshop/weergave', '/beheer/webshop/seo',
            '/beheer/instellingen/winkel', '/beheer/instellingen/betalingen', '/beheer/verzending', '/beheer/instellingen/afrekenen', '/beheer/instellingen/e-mails',
            '/beheer/gebruikers', '/beheer/instellingen/shopify-import',
            '/beheer/documenten/factuur/'.$order->id, '/beheer/documenten/pakbon/'.$order->id];
        foreach ($pages as $page) {
            $this->get($page)->assertOk();
        }
    }

    public function test_staff_cannot_open_settings(): void
    {
        $staff = User::create(['name' => 'Medewerker', 'email' => 'm@example.com', 'password' => 'geheim1234', 'role' => 'staff', 'is_active' => true]);
        $this->actingAs($staff);

        $this->get('/beheer/bestellingen')->assertOk();
        $this->get('/beheer/instellingen/betalingen')->assertForbidden();
        $this->get('/beheer/gebruikers')->assertForbidden();
    }

    public function test_inactive_user_is_blocked(): void
    {
        $user = User::create(['name' => 'Oud', 'email' => 'oud@example.com', 'password' => 'geheim1234', 'role' => 'admin', 'is_active' => false]);

        $this->actingAs($user)->get('/beheer')->assertForbidden();
    }

    public function test_fulfill_sends_tracking_mail(): void
    {
        Mail::fake();
        ['small' => $small] = $this->seedShop();
        $order = $this->buy($small, 1);

        app(OrderService::class)->fulfill($order, [], 'PostNL', '3SABC123', null, true);

        $order->refresh();
        $this->assertSame('fulfilled', $order->fulfillment_status);
        $this->assertStringContainsString('3SABC123', (string) $order->fulfillments()->first()->trackingLink());
        Mail::assertSent(OrderShipped::class);
    }

    public function test_cancel_unpaid_order_restocks(): void
    {
        Mail::fake();
        ['small' => $small] = $this->seedShop();
        $this->post('/cart/add', ['variant_id' => $small->id, 'quantity' => 2]);
        $this->post('/checkout', $this->checkoutData());
        $order = Order::latest('id')->first();
        $this->assertSame(3, $small->fresh()->stock);

        app(OrderService::class)->cancel($order, 'test');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(5, $small->fresh()->stock);
    }
}
