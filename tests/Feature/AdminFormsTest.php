<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings\HomepageSettings;
use App\Filament\Pages\Settings\LoyaltySettings;
use App\Filament\Pages\Settings\PaymentSettings;
use App\Filament\Resources\Discounts\Pages\CreateDiscount;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Mail\OrderShipped;
use App\Models\Discount;
use App\Models\Product;
use App\Models\User;
use App\Support\SettingDefaults;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AdminFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->owner());
    }

    public function test_create_product_with_variants(): void
    {
        $undo = Repeater::fake();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'title' => 'Hojicha Essence',
                'handle' => 'hojicha-essence',
                'status' => 'active',
                'tax_rate' => '9',
                'option_names' => ['Inhoud'],
                'variants' => [
                    ['title' => '50 gram', 'price' => '24,95', 'sku' => 'HOJ-50', 'track_stock' => true, 'stock' => 12],
                    ['title' => '100 gram', 'price' => '39.95', 'compare_at_price' => '44,90', 'sku' => 'HOJ-100'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        $undo();

        $product = Product::where('handle', 'hojicha-essence')->firstOrFail();
        $this->assertSame([2495, 3995], $product->variants->pluck('price')->all());
        $this->assertSame(4490, $product->variants[1]->compare_at_price);
        $this->assertSame('50 gram', $product->variants[0]->option1);
        $this->assertSame(['Inhoud'], $product->option_names);
        $this->get('/products/hojicha-essence')->assertOk()->assertSee('€24,95');
    }

    public function test_changing_handle_creates_redirect(): void
    {
        ['product' => $product] = $this->seedShop();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['handle' => 'matcha-ceremonieel'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('redirects', ['from_path' => '/products/matcha-essence', 'to_path' => '/products/matcha-ceremonieel']);
        $this->get('/products/matcha-essence')->assertRedirect('/products/matcha-ceremonieel');
    }

    public function test_fixed_amount_discount_is_stored_in_cents(): void
    {
        Livewire::test(CreateDiscount::class)
            ->fillForm(['is_automatic' => false, 'code' => 'zomer5', 'title' => 'Zomeractie', 'type' => 'fixed', 'value' => '5,00', 'applies_to' => 'all', 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $discount = Discount::where('code', 'ZOMER5')->firstOrFail();
        $this->assertSame(500, $discount->value);
    }

    public function test_payment_settings_are_saved(): void
    {
        Livewire::test(PaymentSettings::class)
            ->fillForm(['payments' => ['mollie_key' => 'test_abcdefghijklmnopqrstuvwxyz12', 'test_gateway' => false]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('test_abcdefghijklmnopqrstuvwxyz12', settings('payments.mollie_key'));
        $this->assertFalse(settings('payments.test_gateway'));
    }

    public function test_loyalty_value_is_converted(): void
    {
        Livewire::test(LoyaltySettings::class)
            ->assertSet('data.loyalty.redeem_value', '5,00')
            ->fillForm(['loyalty' => ['redeem_value' => '7,50', 'redeem_points' => 100]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(750, settings('loyalty.redeem_value'));
    }

    public function test_homepage_sections_keep_their_order(): void
    {
        $this->seedShop();
        $component = Livewire::test(HomepageSettings::class);
        $sections = array_values($component->get('data.homepage.sections'));
        $this->assertSame('hero', $sections[0]['type']);

        $component->call('save')->assertHasNoFormErrors();

        $saved = settings('homepage.sections');
        $this->assertTrue(array_is_list($saved));
        $this->assertSame(array_column(SettingDefaults::homepageSections(), 'type'), array_column($saved, 'type'));
        $this->get('/')->assertOk();
    }

    public function test_fulfill_action_on_order_page(): void
    {
        Mail::fake();
        ['small' => $small] = $this->seedShop();
        auth()->logout();
        $order = $this->buy($small, 1);
        $this->actingAs(User::first());

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('fulfill', ['company' => 'PostNL', 'number' => '3STEST', 'notify' => true])
            ->assertHasNoActionErrors();

        $this->assertSame('fulfilled', $order->fresh()->fulfillment_status);
        Mail::assertSent(OrderShipped::class);
    }
}
