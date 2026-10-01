<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Bestellingen';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('placed_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('Bestelling')->formatStateUsing(fn ($state) => '#'.$state)->weight('bold'),
                TextColumn::make('placed_at')->label('Datum')->dateTime('j M Y H:i'),
                TextColumn::make('total')->label('Totaal')->state(fn (Order $record) => money($record->grandTotal()))->alignEnd(),
                TextColumn::make('financial_status')->label('Betaling')->badge()
                    ->formatStateUsing(fn ($state) => Order::FINANCIAL_STATUSES[$state] ?? $state)->color(fn ($state) => OrderResource::financialColor($state)),
                TextColumn::make('fulfillment_status')->label('Verzending')->badge()
                    ->formatStateUsing(fn ($state) => Order::FULFILLMENT_STATUSES[$state] ?? $state)->color(fn ($state) => OrderResource::fulfillmentColor($state)),
                TextColumn::make('points_earned')->label('Punten')->alignEnd(),
            ])
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]));
    }
}
