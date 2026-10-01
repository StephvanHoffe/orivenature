<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle'),
            'to_ship' => Tab::make('Te verzenden')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'open')->whereNotNull('paid_at')->where('fulfillment_status', '!=', 'fulfilled'))
                ->badge(fn () => Order::where('status', 'open')->whereNotNull('paid_at')->where('fulfillment_status', '!=', 'fulfilled')->count() ?: null),
            'unpaid' => Tab::make('Onbetaald')->modifyQueryUsing(fn (Builder $query) => $query->where('financial_status', 'pending')),
            'shipped' => Tab::make('Verzonden')->modifyQueryUsing(fn (Builder $query) => $query->where('fulfillment_status', 'fulfilled')),
            'refunded' => Tab::make('Terugbetaald')->modifyQueryUsing(fn (Builder $query) => $query->whereIn('financial_status', ['refunded', 'partially_refunded'])),
            'cancelled' => Tab::make('Geannuleerd')->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'all';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportAll')->label('Alles exporteren')->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->action(fn () => OrderResource::export($this->getFilteredTableQuery()->with('items')->cursor())),
        ];
    }
}
