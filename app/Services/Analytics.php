<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StorefrontEvent;
use App\Models\Visit;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Eenvoudige, privacyvriendelijke statistieken: geen cookies, bezoekers worden per dag
 * herkend aan een hash van IP + browser + datum (die elke dag verandert).
 */
class Analytics
{
    public static function visitor(?Request $request = null): string
    {
        $request ??= request();

        return hash('sha256', $request->ip().'|'.$request->userAgent().'|'.now()->toDateString().'|'.config('app.key'));
    }

    public static function trackVisit(Request $request): void
    {
        $ua = strtolower((string) $request->userAgent());
        if ($ua === '' || preg_match('/bot|crawl|spider|slurp|preview|headless|monitor/', $ua)) {
            return;
        }
        try {
            Visit::insertOrIgnore([
                'date' => now()->toDateString(),
                'visitor' => self::visitor($request),
                'landing_path' => substr('/'.ltrim($request->path(), '/'), 0, 255),
                'referrer' => substr((string) parse_url((string) $request->headers->get('referer'), PHP_URL_HOST), 0, 255) ?: null,
                'utm_source' => substr((string) $request->query('utm_source'), 0, 100) ?: null,
                'device' => str_contains($ua, 'mobile') ? 'mobiel' : 'desktop',
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Statistieken mogen de winkel nooit breken
        }
    }

    public static function event(string $type, ?int $productId = null, ?int $value = null): void
    {
        try {
            StorefrontEvent::create([
                'date' => now()->toDateString(),
                'visitor' => app()->runningInConsole() ? null : self::visitor(),
                'type' => $type,
                'product_id' => $productId,
                'value' => $value,
            ]);
        } catch (\Throwable) {
        }
    }

    /** Kerncijfers voor een periode, plus dezelfde periode ervoor ter vergelijking. */
    public static function summary(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $orders = Order::whereNotNull('paid_at')->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()]);
        $revenue = (int) (clone $orders)->sum(DB::raw('total + gift_card_used + credit_used - refunded_total'));
        $count = (int) (clone $orders)->count();
        $visits = (int) Visit::whereBetween('date', [$from->toDateString(), $to->toDateString()])->count();
        $events = StorefrontEvent::whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->select('type', DB::raw('count(distinct coalesce(visitor, id)) as c'))->groupBy('type')->pluck('c', 'type');

        return [
            'revenue' => $revenue,
            'orders' => $count,
            'aov' => $count ? intdiv($revenue, $count) : 0,
            'visits' => $visits,
            'add_to_cart' => (int) ($events['add_to_cart'] ?? 0),
            'begin_checkout' => (int) ($events['begin_checkout'] ?? 0),
            'conversion' => $visits ? round($count / $visits * 100, 2) : 0.0,
        ];
    }

    /** Omzet en bestellingen per dag. */
    public static function daily(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = Order::whereNotNull('paid_at')->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])
            ->selectRaw('DATE(paid_at) as d, SUM(total + gift_card_used + credit_used - refunded_total) as revenue, COUNT(*) as orders')
            ->groupBy('d')->get()->keyBy('d');
        $visits = Visit::whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('date as d, COUNT(*) as c')->groupBy('d')->pluck('c', 'd');
        $days = [];
        for ($day = $from; $day <= $to; $day = $day->addDay()) {
            $key = $day->toDateString();
            $days[$key] = [
                'revenue' => (int) ($rows[$key]->revenue ?? 0),
                'orders' => (int) ($rows[$key]->orders ?? 0),
                'visits' => (int) ($visits[$key] ?? 0),
            ];
        }

        return $days;
    }
}
