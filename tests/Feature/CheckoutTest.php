<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmation;
use App\Models\Customer;
use App\Models\Discount;
use App\Services\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_cart_add_and_change(): void
    {
        ['small' => $small] = $this->seedShop();

        $this->postJson('/cart/add', ['variant_id' => $small->id, 'quantity' => 2])->assertOk()->assertJsonPath('count', 2);
        $this->postJson('/cart/change', ['variant_id' => $small->id, 'quantity' => 1])->assertOk()->assertJsonPath('count', 1);
    }

    public function test_cannot_add_more_than_stock(): void
    {
        ['small' => $small] = $this->seedShop();

        // Er gaan er maximaal 5 in; daarna volgt een melding
        $this->postJson('/cart/add', ['variant_id' => $small->id, 'quantity' => 6])->assertOk()->assertJsonPath('count', 5);
        $this->postJson('/cart/add', ['variant_id' => $small->id])->assertStatus(422);
    }

    public function test_full_purchase_with_test_payment(): void
    {
        ['small' => $small] = $this->seedShop();

        $order = $this->buy($small, 2);

        $this->assertSame('paid', $order->financial_status);
        $this->assertSame(7590, $order->total);
        // 9% btw zit in de prijs: 7590 - 7590 / 1.09
        $this->assertSame(627, $order->tax_total);
        $this->assertSame(3, $small->fresh()->stock);
        $this->assertSame(75, $order->points_earned);
        $this->assertSame(75, $order->customer->points_balance);
        Mail::assertSent(OrderConfirmation::class);
        $this->get($order->statusUrl())->assertOk()->assertSee('#'.$order->number);
    }

    public function test_failed_payment_releases_stock_and_restores_cart(): void
    {
        ['small' => $small] = $this->seedShop();

        $order = $this->buy($small, 2, [], 'failed');

        $this->assertSame('cancelled', $order->status);
        $this->assertSame(5, $small->fresh()->stock);
        $this->assertSame(0, (int) $order->customer->points_balance);
        $this->assertSame(2, app(Cart::class)->count());
    }

    public function test_discount_code_and_shipping_abroad(): void
    {
        ['big' => $big] = $this->seedShop();
        $this->post('/cart/add', ['variant_id' => $big->id]);

        $quote = $this->postJson('/checkout/quote', ['discount_code' => 'welkom10', 'country_code' => 'DE'])->assertOk()->json('pricing');

        $this->assertSame(630, $quote['discount_total']);
        $this->assertSame(1295, $quote['shipping_total']);
        $this->assertSame(6295 - 630 + 1295, $quote['total']);
    }

    public function test_invalid_discount_code_is_rejected(): void
    {
        ['big' => $big] = $this->seedShop();
        Discount::where('code', 'WELKOM10')->update(['ends_at' => now()->subDay()]);
        $this->post('/cart/add', ['variant_id' => $big->id]);

        $quote = $this->postJson('/checkout/quote', ['discount_code' => 'WELKOM10'])->json('pricing');

        $this->assertNotNull($quote['discount_error']);
        $this->assertSame(0, $quote['discount_total']);
    }

    public function test_discount_usage_counted_and_once_per_customer(): void
    {
        ['big' => $big] = $this->seedShop();
        Discount::where('code', 'WELKOM10')->update(['once_per_customer' => true]);
        $this->get('/discount/WELKOM10');
        $order = $this->buy($big, 1);

        $this->assertSame(630, $order->discount_total);
        $this->assertSame(1, Discount::where('code', 'WELKOM10')->value('usage_count'));

        $this->post('/cart/add', ['variant_id' => $big->id]);
        $quote = $this->postJson('/checkout/quote', ['discount_code' => 'WELKOM10', 'email' => 'klant@example.com'])->json('pricing');
        $this->assertNotNull($quote['discount_error']);
    }

    public function test_checkout_creates_account_for_new_email_only(): void
    {
        ['big' => $big] = $this->seedShop();

        $this->buy($big, 1, ['password' => 'supergeheim1']);
        $customer = Customer::where('email', 'klant@example.com')->first();
        $this->assertTrue($customer->hasAccount());
        // welkomstbonus + punten voor de bestelling
        $this->assertSame(50 + 62, $customer->points_balance);
    }

    public function test_checkout_cannot_take_over_existing_guest(): void
    {
        ['big' => $big] = $this->seedShop();
        Customer::create(['email' => 'klant@example.com', 'first_name' => 'Echte']);

        $this->buy($big, 1, ['password' => 'kaper123456']);

        $this->assertFalse(Customer::where('email', 'klant@example.com')->first()->hasAccount());
        $this->assertGuest('customer');
    }

    public function test_mixed_tax_rates(): void
    {
        ['big' => $big, 'boxVariant' => $box] = $this->seedShop();
        $this->post('/cart/add', ['variant_id' => $big->id]);

        $order = $this->buy($box, 1);

        // 6295 bij 9% + 5495 bij 21%
        $expected = (int) round(6295 - 6295 / 1.09) + (int) round(5495 - 5495 / 1.21);
        $this->assertEqualsWithDelta($expected, $order->tax_total, 1);
        $this->assertSame(11790, $order->total);
    }
}
