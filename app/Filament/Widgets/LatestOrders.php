<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Recente bestellingen';

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 3];

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->with('customer')->latest('placed_at')->limit(8))
            ->paginated(false)
            ->columns([
                TextColumn::make('number')->label('Bestelling')->formatStateUsing(fn ($state) => '#'.$state)->weight('bold'),
                TextColumn::make('placed_at')->label('Datum')->since(),
                TextColumn::make('customer_name')->label('Klant')->state(fn (Order $record) => $record->customerName()),
                TextColumn::make('total')->label('Totaal')->state(fn (Order $record) => money($record->grandTotal()))->alignEnd(),
                TextColumn::make('financial_status')->label('Betaling')->badge()
                    ->formatStateUsing(fn ($state) => Order::FINANCIAL_STATUSES[$state] ?? $state)->color(fn ($state) => OrderResource::financialColor($state)),
                TextColumn::make('fulfillment_status')->label('Verzending')->badge()
                    ->formatStateUsing(fn ($state) => Order::FULFILLMENT_STATUSES[$state] ?? $state)->color(fn ($state) => OrderResource::fulfillmentColor($state)),
            ])
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]));
    }
}
