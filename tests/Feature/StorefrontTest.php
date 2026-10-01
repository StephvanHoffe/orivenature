<?php

namespace Tests\Feature;

use App\Mail\ContactReceived;
use App\Models\ContactMessage;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_pages_render(): void
    {
        $this->seedShop();

        foreach (['/', '/collections', '/collections/all', '/collections/matcha', '/products/matcha-essence', '/pages/contact', '/blogs/news', '/search?q=matcha', '/cart', '/account/login', '/account/register'] as $url) {
            $this->assertSame(200, $this->get($url)->status(), $url);
        }
        $this->get('/products/matcha-essence')->assertSee('Matcha Essence')->assertSee('€37,95');
    }

    public function test_feeds_and_sitemap(): void
    {
        $this->seedShop();

        $this->get('/sitemap.xml')->assertOk()->assertSee('/products/matcha-essence');
        $this->get('/feeds/meta-catalog.xml')->assertOk()->assertSee('MAT-100');
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap');
    }

    public function test_draft_products_are_hidden(): void
    {
        ['product' => $product] = $this->seedShop();
        $product->update(['status' => 'draft']);

        $this->get('/products/matcha-essence')->assertNotFound();
    }

    public function test_old_shopify_urls_redirect(): void
    {
        Redirect::create(['from_path' => '/products/matcha-essence-50-gram', 'to_path' => '/products/matcha-essence']);

        $this->get('/products/matcha-essence-50-gram')->assertRedirect('/products/matcha-essence')->assertStatus(301);
        $this->assertSame(1, Redirect::first()->hits);
        $this->get('/bestaat-niet')->assertNotFound();
    }

    public function test_contact_form_is_stored_and_mailed(): void
    {
        Mail::fake();
        $this->seedShop();

        $this->from('/pages/contact')->post('/contact', ['name' => 'Piet', 'email' => 'piet@example.com', 'message' => 'Hallo!'])
            ->assertRedirect('/pages/contact');

        $this->assertDatabaseHas('contact_messages', ['email' => 'piet@example.com', 'type' => 'contact']);
        Mail::assertSent(ContactReceived::class);
    }

    public function test_contact_form_honeypot_blocks_spam(): void
    {
        $this->post('/contact', ['name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'spam', 'website' => 'http://spam'])->assertRedirect();

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_newsletter_signup(): void
    {
        Mail::fake();

        $this->postJson('/newsletter', ['email' => 'Fan@Example.com'])->assertOk();

        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'fan@example.com']);
    }

    public function test_discount_link_applies_code(): void
    {
        $this->seedShop();

        $this->get('/discount/welkom10?redirect=/products/matcha-essence')->assertRedirect('/products/matcha-essence');
        $this->assertSame('WELKOM10', session('cart.discount_code'));
        $this->get('/discount/WELKOM10?redirect=https://evil.example')->assertRedirect('/');
    }
}
