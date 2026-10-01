<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Models\LoyaltyTransaction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LoyaltyRelationManager extends RelationManager
{
    protected static string $relationship = 'loyaltyTransactions';

    protected static ?string $title = 'Spaarpunten';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) settings('loyalty.enabled') || $ownerRecord->loyaltyTransactions()->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Datum')->dateTime('j M Y H:i'),
                TextColumn::make('type')->label('Soort')->badge()->formatStateUsing(fn ($state) => LoyaltyTransaction::TYPES[$state] ?? $state)
                    ->color(fn ($state) => in_array($state, ['earn', 'signup'], true) ? 'success' : ($state === 'redeem' ? 'info' : 'gray')),
                TextColumn::make('description')->label('Omschrijving')->wrap(),
                TextColumn::make('points')->label('Punten')->alignEnd()->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '').$state)
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                TextColumn::make('user.name')->label('Door')->placeholder('automatisch'),
            ]);
    }
}
