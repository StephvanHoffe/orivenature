<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\StorefrontEvent;
use App\Models\Visit;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class Funnel extends Widget
{
    protected static ?int $sort = 3;

    protected string $view = 'filament.widgets.funnel';

    protected int|string|array $columnSpan = 1;

    protected function getViewData(): array
    {
        $from = now()->subDays(29)->toDateString();
        $visits = Visit::where('date', '>=', $from)->count();
        $events = StorefrontEvent::where('date', '>=', $from)->select('type', DB::raw('count(distinct coalesce(visitor, id)) as c'))->groupBy('type')->pluck('c', 'type');
        $orders = Order::whereNotNull('paid_at')->where('paid_at', '>=', $from)->count();
        $steps = [
            'Bezoekers' => $visits,
            'Product bekeken' => (int) ($events['view_product'] ?? 0),
            'In winkelwagen' => (int) ($events['add_to_cart'] ?? 0),
            'Naar de kassa' => (int) ($events['begin_checkout'] ?? 0),
            'Besteld' => $orders,
        ];

        return ['steps' => $steps, 'max' => max(1, ...array_values($steps))];
    }
}
