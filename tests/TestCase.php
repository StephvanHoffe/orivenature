<?php

namespace Tests;

use App\Models\Collection;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Een kleine winkel: Matcha in 50 g en 100 g, verzending NL gratis en DE € 12,95. */
    protected function seedShop(): array
    {
        $product = Product::create(['title' => 'Matcha Essence', 'handle' => 'matcha-essence', 'status' => 'active', 'tax_rate' => 9, 'option_names' => ['Inhoud'], 'published_at' => now()->subDay()]);
        $small = $product->variants()->create(['title' => '50 gram', 'option1' => '50 gram', 'sku' => 'MAT-50', 'price' => 3795, 'track_stock' => true, 'stock' => 5, 'position' => 0]);
        $big = $product->variants()->create(['title' => '100 gram', 'option1' => '100 gram', 'sku' => 'MAT-100', 'price' => 6295, 'track_stock' => false, 'position' => 1]);
        $box = Product::create(['title' => 'Ritual box', 'handle' => 'orive-ritual-box', 'status' => 'active', 'tax_rate' => 21, 'published_at' => now()->subDay()]);
        $boxVariant = $box->variants()->create(['title' => 'Standaard', 'price' => 5495, 'track_stock' => false]);
        $collection = Collection::create(['title' => 'Matcha', 'handle' => 'matcha']);
        $collection->products()->attach($product->id, ['position' => 0]);
        Page::create(['title' => 'Contact', 'handle' => 'contact', 'body' => '<p>Mail ons</p>', 'template' => 'contact']);
        Page::create(['title' => 'Voorwaarden', 'handle' => 'algemene-voorwaarden', 'body' => '<p>Voorwaarden</p>']);
        $nl = ShippingZone::create(['name' => 'NL/BE', 'countries' => ['NL', 'BE']]);
        $nl->rates()->create(['name' => 'Gratis verzending', 'price' => 0]);
        $eu = ShippingZone::create(['name' => 'Europa', 'countries' => ['DE'], 'position' => 1]);
        $eu->rates()->create(['name' => 'Verzending Europa', 'price' => 1295]);
        Discount::create(['title' => 'Welkom', 'code' => 'WELKOM10', 'type' => 'percentage', 'value' => 10, 'is_active' => true]);

        return compact('product', 'small', 'big', 'box', 'boxVariant', 'collection');
    }

    protected function owner(): User
    {
        return User::create(['name' => 'Eigenaar', 'email' => 'eigenaar@example.com', 'password' => 'geheim1234', 'role' => 'owner', 'is_active' => true]);
    }

    protected function checkoutData(array $overrides = []): array
    {
        return array_merge([
            'email' => 'klant@example.com',
            'first_name' => 'Sanne',
            'last_name' => 'de Vries',
            'address1' => 'Stavorenweg 8',
            'zip' => '2803PT',
            'city' => 'Gouda',
            'country_code' => 'NL',
            'shipping_rate_id' => ShippingRate::where('name', 'Gratis verzending')->value('id'),
            'billing_same' => 1,
        ], $overrides);
    }

    /** Bestelt en betaalt via de testbetaling; geeft de bestelling terug. */
    protected function buy(ProductVariant $variant, int $quantity = 1, array $data = [], string $status = 'paid'): Order
    {
        $this->postJson('/cart/add', ['variant_id' => $variant->id, 'quantity' => $quantity])->assertOk();
        $response = $this->post('/checkout', $this->checkoutData($data));
        $order = Order::latest('id')->firstOrFail();
        if ($order->total > 0) {
            $payment = $order->payments()->firstOrFail();
            $response->assertRedirect(route('payments.test', $payment->provider_id));
            $this->followingRedirects()->post(route('payments.test.complete', $payment->provider_id), ['status' => $status])->assertOk();
        }

        return $order->fresh();
    }
}
