<?php

namespace Tests\Feature;

use App\Mail\AccountNotice;
use App\Mail\ContactReceived;
use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Services\Cart;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private array $shop;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->shop = $this->seedShop();
        $this->customer = Customer::create(['email' => 'klant@example.com', 'first_name' => 'Sanne', 'last_name' => 'de Vries', 'password' => 'wachtwoord12']);
        $this->actingAs($this->customer, 'customer');
    }

    private function paidOrder(): Order
    {
        return $this->buy($this->shop['big'], 1);
    }

    public function test_guests_go_to_login(): void
    {
        auth('customer')->logout();

        foreach (['/account', '/account/orders', '/account/gegevens', '/account/spaarpunten', '/account/addresses'] as $url) {
            $this->get($url)->assertRedirect(route('account.login'));
        }
    }

    public function test_all_account_pages_render(): void
    {
        $order = $this->paidOrder();

        foreach (['/account', '/account/orders', '/account/orders?filter=lopend', '/account/orders?filter=afgerond', '/account/orders/'.$order->number, '/account/spaarpunten', '/account/addresses', '/account/gegevens'] as $url) {
            $this->assertSame(200, $this->get($url)->status(), $url);
        }
    }

    public function test_dashboard_shows_current_order_and_points(): void
    {
        $order = $this->paidOrder();

        $this->get('/account')->assertOk()
            ->assertSee('Lopende bestelling')
            ->assertSee('Bestelling #'.$order->number)
            ->assertSee('Wordt ingepakt')
            ->assertSee('62');
    }

    public function test_order_detail_shows_tracking_after_shipping(): void
    {
        $order = $this->paidOrder();
        app(OrderService::class)->fulfill($order, [], 'PostNL', '3SORIVE123', null, false);

        $this->get('/account/orders/'.$order->number)->assertOk()
            ->assertSee('Verzonden')
            ->assertSee('3SORIVE123')
            ->assertSee('jouw.postnl.nl/track-and-trace/3SORIVE123', false)
            ->assertSee('Factuur (PDF)');
        $this->get('/account/orders?filter=afgerond')->assertSee('#'.$order->number);
        $this->get('/account/orders?filter=lopend')->assertDontSee('#'.$order->number);
    }

    public function test_cannot_see_orders_of_someone_else(): void
    {
        auth('customer')->logout();
        $other = $this->buy($this->shop['big'], 1, ['email' => 'ander@example.com']);
        $this->actingAs($this->customer, 'customer');

        $this->get('/account/orders/'.$other->number)->assertNotFound();
        $this->get('/account/orders/'.$other->number.'/factuur')->assertNotFound();
        $this->post('/account/orders/'.$other->number.'/opnieuw')->assertNotFound();
    }

    public function test_invoice_download_only_for_paid_orders(): void
    {
        $order = $this->paidOrder();
        $this->get('/account/orders/'.$order->number.'/factuur')->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->post('/cart/add', ['variant_id' => $this->shop['big']->id]);
        $this->post('/checkout', $this->checkoutData());
        $unpaid = Order::latest('id')->first();
        $this->get('/account/orders/'.$unpaid->number.'/factuur')->assertNotFound();
    }

    public function test_reorder_puts_items_back_in_cart(): void
    {
        $order = $this->paidOrder();
        $this->shop['box']->update(['status' => 'draft']);

        $this->post('/account/orders/'.$order->number.'/opnieuw')->assertRedirect(route('cart'));

        $this->assertSame(1, app(Cart::class)->count());
        $this->assertTrue(app(Cart::class)->contains($this->shop['product']->id));
    }

    public function test_pending_order_can_resume_payment(): void
    {
        $this->post('/cart/add', ['variant_id' => $this->shop['big']->id]);
        $this->post('/checkout', $this->checkoutData());
        $order = Order::latest('id')->first();
        $payment = $order->payments()->first();

        $this->get('/account')->assertSee('Betaling afronden');
        $this->get('/account/orders/'.$order->number.'/betalen')->assertRedirect(route('payments.test', $payment->provider_id));

        $this->post(route('payments.test.complete', $payment->provider_id), ['status' => 'paid']);
        $this->get('/account/orders/'.$order->number.'/betalen')->assertRedirect(route('account.orders.show', $order->number));
    }

    public function test_question_about_order_reaches_the_shop(): void
    {
        $order = $this->paidOrder();

        $this->post('/account/orders/'.$order->number.'/vraag', ['subject' => 'beschadigd', 'message' => 'Het zakje was open.'])
            ->assertRedirect(route('account.orders.show', $order->number));

        $message = ContactMessage::where('type', 'order')->firstOrFail();
        $this->assertSame($order->id, $message->data['order_id']);
        $this->assertSame('Product beschadigd', $message->data['onderwerp']);
        $this->assertTrue($order->events()->where('type', 'customer_message')->exists());
        Mail::assertSent(ContactReceived::class);
    }

    public function test_update_details(): void
    {
        $this->put('/account/gegevens', ['first_name' => 'Sanna', 'last_name' => 'Jansen', 'phone' => '0612345678'])->assertRedirect(route('account.profile'));

        $this->assertSame('Sanna', $this->customer->fresh()->first_name);
        $this->assertSame('0612345678', $this->customer->fresh()->phone);
    }

    public function test_change_email_needs_password_and_notifies_old_address(): void
    {
        $this->from('/account/gegevens')->put('/account/gegevens/e-mail', ['email' => 'nieuw@example.com', 'current_password' => 'fout'])
            ->assertRedirect('/account/gegevens')->assertSessionHasErrorsIn('email', 'current_password');
        $this->assertSame('klant@example.com', $this->customer->fresh()->email);

        Customer::create(['email' => 'bezet@example.com']);
        $this->from('/account/gegevens')->put('/account/gegevens/e-mail', ['email' => 'bezet@example.com', 'current_password' => 'wachtwoord12'])
            ->assertSessionHasErrorsIn('email', 'email');

        $this->put('/account/gegevens/e-mail', ['email' => 'Nieuw@Example.com', 'current_password' => 'wachtwoord12'])->assertSessionHasNoErrors();
        $this->assertSame('nieuw@example.com', $this->customer->fresh()->email);
        Mail::assertSent(AccountNotice::class, fn ($mail) => $mail->hasTo('klant@example.com'));
    }

    public function test_change_password(): void
    {
        $this->from('/account/gegevens')->put('/account/gegevens/wachtwoord', ['current_password' => 'wachtwoord12', 'password' => 'nieuwwachtwoord', 'password_confirmation' => 'anders123'])
            ->assertSessionHasErrorsIn('password', 'password');

        $this->put('/account/gegevens/wachtwoord', ['current_password' => 'wachtwoord12', 'password' => 'nieuwwachtwoord', 'password_confirmation' => 'nieuwwachtwoord'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nieuwwachtwoord', $this->customer->fresh()->password));
        Mail::assertSent(AccountNotice::class);
    }

    public function test_newsletter_toggle(): void
    {
        $this->put('/account/gegevens/nieuwsbrief', ['accepts_marketing' => '1']);
        $this->assertTrue($this->customer->fresh()->accepts_marketing);
        $this->assertNull(NewsletterSubscriber::where('email', 'klant@example.com')->value('unsubscribed_at'));

        $this->put('/account/gegevens/nieuwsbrief', ['accepts_marketing' => '0']);
        $this->assertFalse($this->customer->fresh()->accepts_marketing);
        $this->assertNotNull(NewsletterSubscriber::where('email', 'klant@example.com')->value('unsubscribed_at'));
    }

    public function test_export_my_data(): void
    {
        $order = $this->paidOrder();

        $response = $this->get('/account/gegevens/download')->assertOk();
        $data = json_decode($response->streamedContent(), true);

        $this->assertSame('klant@example.com', $data['gegevens']['email']);
        $this->assertSame('#'.$order->number, $data['bestellingen'][0]['nummer']);
        $this->assertSame(62, $data['spaarpunten']['saldo']);
    }

    public function test_delete_account_keeps_orders(): void
    {
        $order = $this->paidOrder();

        $this->from('/account/gegevens')->delete('/account/gegevens', ['current_password' => 'fout', 'confirm' => '1'])->assertSessionHasErrorsIn('delete');
        $this->assertNotNull($this->customer->fresh());

        $this->delete('/account/gegevens', ['current_password' => 'wachtwoord12', 'confirm' => '1'])->assertRedirect(route('home'));

        $this->assertNull(Customer::find($this->customer->id));
        $this->assertGuest('customer');
        $this->assertNotNull($order->fresh());
        $this->assertNull($order->fresh()->customer_id);
        $this->assertSame('klant@example.com', $order->fresh()->email);
    }

    public function test_addresses(): void
    {
        $address = ['first_name' => 'Sanne', 'last_name' => 'de Vries', 'address1' => 'Laan 1', 'zip' => '1234AB', 'city' => 'Gouda', 'country_code' => 'NL'];
        $this->post('/account/addresses', $address)->assertRedirect(route('account.addresses'));
        $this->post('/account/addresses', array_merge($address, ['address1' => 'Straat 2']));
        [$first, $second] = $this->customer->addresses()->orderBy('id')->get();
        $this->assertTrue($first->is_default);

        $this->post('/account/addresses/'.$second->id.'/standaard');
        $this->assertTrue($second->fresh()->is_default);
        $this->assertFalse($first->fresh()->is_default);

        $this->delete('/account/addresses/'.$second->id);
        $this->assertTrue($first->fresh()->is_default);

        $other = Customer::create(['email' => 'x@example.com'])->addresses()->create($address);
        $this->delete('/account/addresses/'.$other->id)->assertNotFound();
    }

    public function test_public_order_page_shows_timeline(): void
    {
        $order = $this->paidOrder();

        $this->get($order->statusUrl())->assertOk()->assertSee('Ingepakt')->assertSee('Bekijk in mijn account');
    }
}
