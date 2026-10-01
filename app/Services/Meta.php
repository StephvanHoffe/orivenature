<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

/**
 * Meta (Facebook/Instagram): pixel in de browser + Conversions API vanaf de server.
 * Beide sturen hetzelfde event_id, zodat Meta dubbele aankopen samenvoegt.
 */
class Meta
{
    public static function pixelId(): ?string
    {
        $id = trim((string) settings('meta.pixel_id'));

        return $id !== '' ? $id : null;
    }

    public static function purchaseEventId(Order $order): string
    {
        return 'purchase-'.$order->id;
    }

    public static function purchase(Order $order): void
    {
        $pixel = self::pixelId();
        $token = trim((string) settings('meta.capi_token'));
        if (! $pixel || $token === '') {
            return;
        }
        $order->loadMissing('items');
        $address = $order->shipping_address ?? [];
        $hash = fn (?string $v) => $v ? hash('sha256', strtolower(trim($v))) : null;
        $event = [
            'event_name' => 'Purchase',
            'event_time' => ($order->paid_at ?? now())->timestamp,
            'event_id' => self::purchaseEventId($order),
            'action_source' => 'website',
            'event_source_url' => url('/checkout'),
            'user_data' => array_filter([
                'em' => [$hash($order->email)],
                'ph' => $order->phone ? [$hash(preg_replace('/\D/', '', $order->phone))] : null,
                'fn' => [$hash($address['first_name'] ?? null)],
                'ln' => [$hash($address['last_name'] ?? null)],
                'ct' => [$hash($address['city'] ?? null)],
                'zp' => [$hash(str_replace(' ', '', (string) ($address['zip'] ?? '')))],
                'country' => [$hash($address['country_code'] ?? null)],
                'external_id' => $order->customer_id ? [$hash((string) $order->customer_id)] : null,
                'client_ip_address' => $order->ip,
            ]),
            'custom_data' => [
                'currency' => 'EUR',
                'value' => round($order->grandTotal() / 100, 2),
                'order_id' => (string) $order->number,
                'content_type' => 'product',
                'content_ids' => $order->items->pluck('variant_id')->filter()->map(fn ($id) => (string) $id)->values()->all(),
                'contents' => $order->items->map(fn ($i) => ['id' => (string) $i->variant_id, 'quantity' => $i->quantity, 'item_price' => round($i->price / 100, 2)])->values()->all(),
                'num_items' => (int) $order->items->sum('quantity'),
            ],
        ];
        $payload = ['data' => [$event]];
        if ($code = settings('meta.test_event_code')) {
            $payload['test_event_code'] = $code;
        }
        try {
            $version = settings('meta.graph_version', 'v23.0');
            $response = Http::timeout(6)->post("https://graph.facebook.com/{$version}/{$pixel}/events?access_token={$token}", $payload);
            if ($response->failed()) {
                $order->log('meta', __('Meta Conversions API gaf een fout: :error', ['error' => $response->json('error.message', $response->status())]));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Stuurt een test-event om de koppeling te controleren. Geeft [gelukt, melding] terug. */
    public static function sendTestEvent(): array
    {
        $pixel = self::pixelId();
        $token = trim((string) settings('meta.capi_token'));
        if (! $pixel || $token === '') {
            return [false, 'Vul eerst de pixel-ID en de toegangstoken in.'];
        }
        $payload = ['data' => [[
            'event_name' => 'PageView',
            'event_time' => now()->timestamp,
            'event_id' => 'test-'.now()->timestamp,
            'action_source' => 'website',
            'event_source_url' => url('/'),
            'user_data' => ['client_ip_address' => request()->ip(), 'client_user_agent' => (string) request()->userAgent()],
        ]]];
        if ($code = settings('meta.test_event_code')) {
            $payload['test_event_code'] = $code;
        }
        try {
            $version = settings('meta.graph_version', 'v23.0');
            $response = Http::timeout(8)->post("https://graph.facebook.com/{$version}/{$pixel}/events?access_token={$token}", $payload);
        } catch (\Throwable $e) {
            return [false, $e->getMessage()];
        }
        if ($response->failed()) {
            return [false, (string) $response->json('error.message', 'HTTP '.$response->status())];
        }

        return [true, $code
            ? 'Gelukt! Je ziet het event nu in Evenementenbeheer > Test-evenementen.'
            : 'Gelukt! Meta heeft het event ontvangen.'];
    }
}
