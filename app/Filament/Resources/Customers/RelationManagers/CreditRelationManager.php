<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Models\CreditTransaction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CreditRelationManager extends RelationManager
{
    protected static string $relationship = 'creditTransactions';

    protected static ?string $title = 'Tegoed';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Datum')->dateTime('j M Y H:i'),
                TextColumn::make('type')->label('Soort')->badge()->formatStateUsing(fn ($state) => CreditTransaction::TYPES[$state] ?? $state),
                TextColumn::make('description')->label('Omschrijving')->wrap(),
                TextColumn::make('amount')->label('Bedrag')->alignEnd()->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '−').money(abs($state)))
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                TextColumn::make('user.name')->label('Door')->placeholder('automatisch'),
            ]);
    }
}
