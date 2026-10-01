<?php

namespace App\Filament\Widgets;

use App\Models\OrderItem;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class TopProducts extends Widget
{
    protected static ?int $sort = 5;

    protected string $view = 'filament.widgets.top-products';

    protected int|string|array $columnSpan = 1;

    protected function getViewData(): array
    {
        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotNull('orders.paid_at')->where('orders.paid_at', '>=', now()->subDays(29))
            ->select('order_items.title', 'order_items.variant_title', DB::raw('SUM(order_items.quantity) as qty'), DB::raw('SUM(order_items.price * order_items.quantity) as revenue'))
            ->groupBy('order_items.title', 'order_items.variant_title')->orderByDesc('qty')->limit(6)->get();

        return ['rows' => $rows];
    }
}
