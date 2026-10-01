<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Services\Import\ShopifyCsvImporter;
use App\Services\Import\ShopifyImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    private function csv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, "\xEF\xBB\xBF".$content);

        return $path;
    }

    public function test_customer_csv(): void
    {
        $path = $this->csv("Customer ID,First Name,Last Name,Email,Accepts Email Marketing,Default Address Address1,Default Address City,Default Address Country Code,Default Address Zip,Phone,Tags\n"
            ."'123,Anna,Jansen,Anna@Example.com,yes,Dorpsstraat 1,Utrecht,NL,'1234AB,'+31612345678,\"vip, horeca\"\n"
            .",Geen,Email,,no,,,,,,\n");

        $stats = (new ShopifyCsvImporter)->importCustomers($path);

        $this->assertSame(['created' => 1, 'updated' => 0, 'skipped' => 1], $stats);
        $customer = Customer::where('email', 'anna@example.com')->first();
        $this->assertTrue($customer->accepts_marketing);
        $this->assertSame(['vip', 'horeca'], $customer->tags);
        $this->assertSame('1234AB', $customer->addresses()->first()->zip);
    }

    public function test_order_csv_groups_line_items(): void
    {
        $this->seedShop();
        $path = $this->csv("Name,Email,Financial Status,Paid at,Fulfillment Status,Currency,Subtotal,Shipping,Taxes,Total,Discount Code,Discount Amount,Created at,Lineitem quantity,Lineitem name,Lineitem price,Lineitem sku,Billing Name,Shipping Name,Shipping Address1,Shipping City,Shipping Zip,Shipping Country,Id\n"
            ."#1050,koper@example.com,paid,2026-05-01 10:00:00 +0200,fulfilled,EUR,100.90,0.00,8.33,100.90,,0.00,2026-05-01 09:59:00 +0200,1,Matcha Essence 100 gram,62.95,MAT-100,Koper Een,Koper Een,Laan 2,Gouda,2801AA,NL,555\n"
            ."#1050,koper@example.com,,,,,,,,,,,,1,Matcha Essence 50 gram,37.95,MAT-50,,,,,,,\n");

        $stats = (new ShopifyCsvImporter)->importOrders($path, true);

        $this->assertSame(1, $stats['created']);
        $order = Order::where('number', 1050)->first();
        $this->assertSame(10090, $order->total);
        $this->assertCount(2, $order->items);
        $this->assertNotNull($order->items->firstWhere('sku', 'MAT-100')->variant_id);
        $this->assertSame(100, $order->customer->points_balance);
        // nieuwe bestellingen gaan verder na het hoogste geïmporteerde nummer
        $this->assertSame(1051, Order::nextNumber());

        $this->assertSame(1, (new ShopifyCsvImporter)->importOrders($path)['skipped']);
    }

    public function test_page_extraction_from_shopify_sections(): void
    {
        $html = '<html><head><title>Over ons – Orivé</title></head><body><main><nav class="breadcrumbs">Home</nav>'
            .'<div class="h2">Puur uit <em>Azië</em></div><img src="https://cdn.shopify.com/a.jpg" alt="foto">'
            .'<details><summary>Vraag?</summary><div>Antwoord.</div></details><script>x()</script><p>Tekst <strong>vet</strong></p></main></body></html>';

        [$title, $body] = (new ShopifyImporter)->extractPage($html);

        $this->assertSame('Over ons', $title);
        $this->assertStringContainsString('<h2>Puur uit <em>Azië</em></h2>', $body);
        $this->assertStringContainsString('<h3>Vraag?</h3><p>Antwoord.</p>', $body);
        $this->assertStringContainsString('<strong>vet</strong>', $body);
        $this->assertStringNotContainsString('Home', $body);
        $this->assertStringNotContainsString('x()', $body);
    }
}
