<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Notifications\CustomerResetPassword;
use App\Services\Loyalty;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoyaltyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function member(int $points = 0): Customer
    {
        $customer = Customer::create(['email' => 'lid@example.com', 'first_name' => 'Lid', 'password' => 'wachtwoord12']);
        if ($points) {
            Loyalty::addPoints($customer, $points, 'adjust', 'test');
        }

        return $customer->fresh();
    }

    public function test_register_gives_signup_bonus(): void
    {
        $this->post('/account/register', ['first_name' => 'Nieuw', 'email' => 'nieuw@example.com', 'password' => 'wachtwoord12'])
            ->assertRedirect(route('account.dashboard'));

        $this->assertSame(50, Customer::where('email', 'nieuw@example.com')->value('points_balance'));
    }

    public function test_register_with_guest_email_sends_activation_link(): void
    {
        Notification::fake();
        $guest = Customer::create(['email' => 'gast@example.com']);

        $this->post('/account/register', ['first_name' => 'Iemand', 'email' => 'gast@example.com', 'password' => 'wachtwoord12'])
            ->assertRedirect(route('account.login'));

        $this->assertFalse($guest->fresh()->hasAccount());
        $this->assertGuest('customer');
        Notification::assertSentTo($guest, CustomerResetPassword::class);
    }

    public function test_redeem_points_for_credit(): void
    {
        $customer = $this->member(250);

        $this->actingAs($customer, 'customer')->post('/account/loyalty/redeem', ['points' => 250])->assertRedirect();

        $customer->refresh();
        // 100 punten = € 5; alleen hele blokken worden ingewisseld
        $this->assertSame(50, $customer->points_balance);
        $this->assertSame(1000, $customer->credit_balance);
    }

    public function test_cannot_redeem_more_than_balance(): void
    {
        $customer = $this->member(80);

        $this->actingAs($customer, 'customer')->post('/account/loyalty/redeem', ['points' => 100]);

        $this->assertSame(80, $customer->fresh()->points_balance);
        $this->assertSame(0, $customer->fresh()->credit_balance);
    }

    public function test_credit_is_used_at_checkout(): void
    {
        ['big' => $big] = $this->seedShop();
        $customer = $this->member();
        Loyalty::addCredit($customer, 1000, 'adjust', 'test');

        $this->actingAs($customer, 'customer');
        $order = $this->buy($big, 1, ['email' => 'lid@example.com']);

        $this->assertSame(1000, $order->credit_used);
        $this->assertSame(5295, $order->total);
        $this->assertSame(0, $customer->fresh()->credit_balance);
        // punten over het bedrag na tegoed
        $this->assertSame(52, $order->points_earned);
    }

    public function test_full_refund_reverts_points(): void
    {
        ['big' => $big] = $this->seedShop();
        $order = $this->buy($big, 1);
        $this->assertSame(62, $order->customer->points_balance);

        app(OrderService::class)->refund($order, $order->refundableAmount(), 'retour', [], false);

        $this->assertSame('refunded', $order->fresh()->financial_status);
        $this->assertSame(0, $order->customer->fresh()->points_balance);
    }
}
