<?php

namespace App\Services\Import;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\Loyalty;
use App\Support\Countries;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Leest de CSV-exports van Shopify (Klanten > Exporteren en Bestellingen > Exporteren).
 * Werkt met zowel het oude als het nieuwe exportformaat; kolommen worden op naam gezocht.
 */
class ShopifyCsvImporter
{
    /** @return array{created:int, updated:int, skipped:int} */
    public function importCustomers(string $path): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        foreach ($this->rows($path) as $row) {
            $email = strtolower(trim((string) $this->col($row, ['Email', 'E-mail'])));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $stats['skipped']++;

                continue;
            }
            DB::transaction(function () use ($row, $email, &$stats) {
                $customer = Customer::firstOrNew(['email' => $email]);
                $stats[$customer->exists ? 'updated' : 'created']++;
                $marketing = $this->bool($this->col($row, ['Accepts Email Marketing', 'Accepts Marketing']));
                $customer->fill(array_filter([
                    'first_name' => $this->col($row, ['First Name']),
                    'last_name' => $this->col($row, ['Last Name']),
                    'phone' => $this->col($row, ['Phone', 'Default Address Phone']),
                    'note' => $this->col($row, ['Note']),
                    'tags' => $this->tags($this->col($row, ['Tags'])),
                    'shopify_id' => $this->id($this->col($row, ['Customer ID'])),
                ], fn ($v) => $v !== null && $v !== []));
                if ($marketing && ! $customer->accepts_marketing) {
                    $customer->accepts_marketing = true;
                    $customer->marketing_consent_at = now();
                }
                $customer->save();

                $address1 = $this->col($row, ['Default Address Address1', 'Address1']);
                $city = $this->col($row, ['Default Address City', 'City']);
                if ($address1 && $city && ! $customer->addresses()->where('address1', $address1)->exists()) {
                    $customer->addresses()->create([
                        'first_name' => $customer->first_name,
                        'last_name' => $customer->last_name,
                        'company' => $this->col($row, ['Default Address Company', 'Company']),
                        'address1' => $address1,
                        'address2' => $this->col($row, ['Default Address Address2', 'Address2']),
                        'zip' => (string) $this->col($row, ['Default Address Zip', 'Zip']),
                        'city' => $city,
                        'province' => $this->col($row, ['Default Address Province Code', 'Province Code', 'Province']),
                        'country_code' => strtoupper((string) ($this->col($row, ['Default Address Country Code', 'Country Code']) ?: 'NL')),
                        'phone' => $this->col($row, ['Default Address Phone', 'Phone']),
                        'is_default' => ! $customer->addresses()->exists(),
                    ]);
                }
            });
        }

        return $stats;
    }

    /**
     * Shopify zet elke bestelregel op een eigen rij; de eerste rij van een bestelling bevat de totalen.
     *
     * @return array{created:int, skipped:int, items:int}
     */
    public function importOrders(string $path, bool $awardPoints = false): array
    {
        $stats = ['created' => 0, 'skipped' => 0, 'items' => 0];
        $orders = [];
        foreach ($this->rows($path) as $row) {
            $name = trim((string) $this->col($row, ['Name']));
            if ($name === '') {
                continue;
            }
            $orders[$name][] = $row;
        }

        foreach ($orders as $name => $rows) {
            $number = (int) preg_replace('/\D/', '', $name);
            $first = $rows[0];
            $shopifyId = $this->id($this->col($first, ['Id']));
            if (! $number || Order::where('number', $number)->orWhere(fn ($q) => $shopifyId ? $q->where('shopify_id', $shopifyId) : $q->whereRaw('0 = 1'))->exists()) {
                $stats['skipped']++;

                continue;
            }

            DB::transaction(function () use ($rows, $first, $number, $shopifyId, $awardPoints, &$stats) {
                $email = strtolower(trim((string) $this->col($first, ['Email'])));
                $customer = null;
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    [$firstName, $lastName] = $this->splitName($this->col($first, ['Billing Name', 'Shipping Name']));
                    $customer = Customer::firstOrCreate(['email' => $email], ['first_name' => $firstName, 'last_name' => $lastName]);
                }

                $financial = match (strtolower((string) $this->col($first, ['Financial Status']))) {
                    'paid' => 'paid',
                    'refunded' => 'refunded',
                    'partially_refunded' => 'partially_refunded',
                    'voided' => 'cancelled',
                    'expired' => 'expired',
                    default => 'pending',
                };
                $fulfillment = match (strtolower((string) $this->col($first, ['Fulfillment Status']))) {
                    'fulfilled' => 'fulfilled',
                    'partial' => 'partially_fulfilled',
                    default => 'unfulfilled',
                };
                $cancelledAt = $this->date($this->col($first, ['Cancelled at']));
                $placedAt = $this->date($this->col($first, ['Created at'])) ?? now();

                $order = Order::create([
                    'number' => $number,
                    'customer_id' => $customer?->id,
                    'email' => $email ?: 'onbekend@orivenature.com',
                    'phone' => $this->col($first, ['Phone', 'Shipping Phone', 'Billing Phone']),
                    'status' => $cancelledAt ? 'cancelled' : 'open',
                    'financial_status' => $financial,
                    'fulfillment_status' => $fulfillment,
                    'currency' => $this->col($first, ['Currency']) ?: 'EUR',
                    'subtotal' => to_cents($this->col($first, ['Subtotal'])),
                    'discount_total' => to_cents($this->col($first, ['Discount Amount'])),
                    'shipping_total' => to_cents($this->col($first, ['Shipping'])),
                    'tax_total' => to_cents($this->col($first, ['Taxes'])),
                    'total' => to_cents($this->col($first, ['Total'])),
                    'refunded_total' => to_cents($this->col($first, ['Refunded Amount'])),
                    'discount_code' => $this->col($first, ['Discount Code']),
                    'shipping_method' => $this->col($first, ['Shipping Method']),
                    'shipping_address' => $this->address($first, 'Shipping'),
                    'billing_address' => $this->address($first, 'Billing'),
                    'customer_note' => $this->col($first, ['Notes']),
                    'tags' => $this->tags($this->col($first, ['Tags'])),
                    'accepts_marketing' => $this->bool($this->col($first, ['Accepts Marketing'])),
                    'source' => 'shopify',
                    'placed_at' => $placedAt,
                    'paid_at' => $this->date($this->col($first, ['Paid at'])) ?? (in_array($financial, ['paid', 'refunded', 'partially_refunded'], true) ? $placedAt : null),
                    'fulfilled_at' => $this->date($this->col($first, ['Fulfilled at'])),
                    'cancelled_at' => $cancelledAt,
                    'shopify_id' => $shopifyId,
                    'created_at' => $placedAt,
                ]);

                foreach ($rows as $row) {
                    $title = (string) $this->col($row, ['Lineitem name']);
                    if ($title === '') {
                        continue;
                    }
                    $quantity = max(1, (int) $this->col($row, ['Lineitem quantity']));
                    $price = to_cents($this->col($row, ['Lineitem price']));
                    $sku = $this->col($row, ['Lineitem sku']);
                    $variant = $sku ? ProductVariant::with('product')->where('sku', $sku)->first() : null;
                    $order->items()->create([
                        'product_id' => $variant?->product_id,
                        'variant_id' => $variant?->id,
                        'title' => $variant?->product->title ?? $title,
                        'variant_title' => $variant && ! $variant->product->hasOnlyDefaultVariant() ? $variant->title : null,
                        'sku' => $sku,
                        'image' => $variant?->imageUrl() ? ($variant->image?->path ?? $variant->product->images()->value('path')) : null,
                        'price' => $price,
                        'quantity' => $quantity,
                        'tax_rate' => $variant?->product->tax_rate ?? 9,
                        'discount_allocated' => to_cents($this->col($row, ['Lineitem discount'])),
                        'total' => $price * $quantity - to_cents($this->col($row, ['Lineitem discount'])),
                        'fulfilled_quantity' => strtolower((string) $this->col($row, ['Lineitem fulfillment status'])) === 'fulfilled' ? $quantity : 0,
                    ]);
                    $stats['items']++;
                }
                $order->log('import', __('Geïmporteerd uit Shopify (:name).', ['name' => '#'.$number]));

                if ($awardPoints && $order->financial_status === 'paid' && ! $order->cancelled_at) {
                    Loyalty::award($order);
                }
                $stats['created']++;
            });
        }

        // Nieuwe bestellingen gaan verder na het hoogste Shopify-nummer
        $max = (int) Order::max('number');
        if ($max >= (int) settings('orders.start_number', 1001)) {
            settings()->set('orders.start_number', $max + 1);
        }

        return $stats;
    }

    /** @return \Generator<int, array<string, string>> */
    private function rows(string $path): \Generator
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new \RuntimeException("Kan {$path} niet openen");
        }
        $first = fgets($handle);
        $delimiter = substr_count((string) $first, ';') > substr_count((string) $first, ',') ? ';' : ',';
        rewind($handle);
        $header = fgetcsv($handle, null, $delimiter, '"', '');
        if (! $header) {
            return;
        }
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        while (($data = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            if ($data === [null]) {
                continue;
            }
            yield array_combine($header, array_pad(array_slice($data, 0, count($header)), count($header), ''));
        }
        fclose($handle);
    }

    private function col(array $row, array $names): ?string
    {
        foreach ($names as $name) {
            $value = $row[strtolower($name)] ?? null;
            if ($value !== null && trim($value) !== '') {
                // Shopify zet een apostrof voor nummers (bijv. telefoonnummers en postcodes)
                return ltrim(trim($value), "'");
            }
        }

        return null;
    }

    private function bool(?string $value): bool
    {
        return in_array(strtolower((string) $value), ['yes', 'true', '1', 'ja', 'subscribed'], true);
    }

    private function id(?string $value): ?int
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits !== '' ? (int) $digits : null;
    }

    private function tags(?string $value): ?array
    {
        $tags = array_values(array_filter(array_map('trim', explode(',', (string) $value))));

        return $tags ?: null;
    }

    private function date(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }
        try {
            return Carbon::parse($value)->setTimezone(config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }

    private function splitName(?string $name): array
    {
        $name = trim((string) $name);
        if ($name === '') {
            return [null, null];
        }
        $parts = explode(' ', $name, 2);

        return [$parts[0], $parts[1] ?? null];
    }

    private function address(array $row, string $prefix): ?array
    {
        $address1 = $this->col($row, ["{$prefix} Address1", "{$prefix} Street"]);
        if (! $address1) {
            return null;
        }
        [$first, $last] = $this->splitName($this->col($row, ["{$prefix} Name"]));
        $country = strtoupper((string) $this->col($row, ["{$prefix} Country"]));
        if (strlen($country) !== 2) {
            $country = array_search($country, array_map('strtoupper', Countries::LIST), true) ?: 'NL';
        }

        return Arr::whereNotNull([
            'first_name' => $first,
            'last_name' => $last,
            'company' => $this->col($row, ["{$prefix} Company"]),
            'address1' => $address1,
            'address2' => $this->col($row, ["{$prefix} Address2"]),
            'zip' => $this->col($row, ["{$prefix} Zip"]),
            'city' => $this->col($row, ["{$prefix} City"]),
            'country_code' => $country,
            'phone' => $this->col($row, ["{$prefix} Phone"]),
        ]);
    }
}
