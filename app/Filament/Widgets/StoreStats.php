<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\Analytics;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Laatste 30 dagen';

    protected function getStats(): array
    {
        $to = CarbonImmutable::today();
        $from = $to->subDays(29);
        $now = Analytics::summary($from, $to);
        $prev = Analytics::summary($from->subDays(30), $from->subDay());
        $daily = collect(Analytics::daily($from, $to));
        $change = function (float $current, float $previous): array {
            if ($previous <= 0) {
                return [null, 'gray'];
            }
            $pct = round(($current - $previous) / $previous * 100);

            return [($pct >= 0 ? '+' : '').$pct.'% t.o.v. vorige 30 dagen', $pct >= 0 ? 'success' : 'danger'];
        };
        [$revText, $revColor] = $change($now['revenue'], $prev['revenue']);
        [$ordText, $ordColor] = $change($now['orders'], $prev['orders']);
        [$visText, $visColor] = $change($now['visits'], $prev['visits']);
        $toShip = Order::where('status', 'open')->whereNotNull('paid_at')->where('fulfillment_status', '!=', 'fulfilled')->count();

        return [
            Stat::make('Omzet', money($now['revenue']))->description($revText ?? 'incl. btw, na terugbetalingen')->color($revColor)
                ->chart($daily->pluck('revenue')->values()->all()),
            Stat::make('Bestellingen', (string) $now['orders'])->description($ordText ?? 'gem. '.money($now['aov']).' per bestelling')->color($ordColor)
                ->chart($daily->pluck('orders')->values()->all()),
            Stat::make('Bezoekers', number_format($now['visits'], 0, ',', '.'))->description($visText ?? 'unieke bezoekers per dag opgeteld')->color($visColor)
                ->chart($daily->pluck('visits')->values()->all()),
            Stat::make('Conversie', number_format($now['conversion'], 2, ',', '.').'%')->description('bezoekers die bestellen')->color('gray'),
            Stat::make('Gem. orderwaarde', money($now['aov']))->color('gray'),
            Stat::make('Te verzenden', (string) $toShip)->description($toShip ? 'betaalde bestellingen klaarzetten' : 'alles is verzonden')
                ->color($toShip ? 'warning' : 'success')->url(OrderResource::getUrl('index', ['activeTab' => 'to_ship'])),
        ];
    }
}
