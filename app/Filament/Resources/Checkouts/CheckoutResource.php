<?php

namespace App\Filament\Resources\Checkouts;

use App\Filament\Resources\Checkouts\Pages\ListCheckouts;
use App\Mail\AbandonedCheckout;
use App\Models\Checkout;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

class CheckoutResource extends Resource
{
    protected static ?string $model = Checkout::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = 'Bestellingen';

    protected static ?string $navigationLabel = 'Verlaten winkelwagens';

    protected static ?string $modelLabel = 'verlaten winkelwagen';

    protected static ?string $pluralModelLabel = 'verlaten winkelwagens';

    protected static ?string $slug = 'verlaten-winkelwagens';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNull('completed_at')->whereNotNull('email')->where('updated_at', '<=', now()->subMinutes(30));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('updated_at')->label('Laatst actief')->since()->sortable(),
                TextColumn::make('email')->label('Klant')->searchable()->description(fn (Checkout $record) => $record->customer?->name),
                TextColumn::make('items')->label('Inhoud')->state(fn (Checkout $record) => collect($record->cart)->map(fn ($l) => $l['quantity'].'× '.$l['title'].(! empty($l['variant_title']) ? ' ('.$l['variant_title'].')' : ''))->implode(', '))->wrap(),
                TextColumn::make('subtotal')->label('Waarde')->formatStateUsing(fn ($state) => money($state))->alignEnd()->sortable(),
                TextColumn::make('reminder_sent_at')->label('Herinnering')->badge()
                    ->state(fn (Checkout $record) => $record->reminder_sent_at ? 'Verstuurd '.$record->reminder_sent_at->format('j-n H:i') : 'Nog niet')
                    ->color(fn (Checkout $record) => $record->reminder_sent_at ? 'success' : 'gray'),
            ])
            ->filters([
                TernaryFilter::make('reminder_sent_at')->label('Herinnering verstuurd')->nullable(),
            ])
            ->recordActions([
                Action::make('remind')->label('Herinnering sturen')->icon(Heroicon::OutlinedEnvelope)
                    ->requiresConfirmation()->modalDescription(fn (Checkout $record) => 'Er gaat een e-mail met een link naar de winkelwagen naar '.$record->email.'.')
                    ->action(function (Checkout $record) {
                        Mail::to($record->email)->send(new AbandonedCheckout($record));
                        $record->forceFill(['reminder_sent_at' => now()])->save();
                        Notification::make()->success()->title('Herinnering verstuurd')->send();
                    }),
                Action::make('link')->label('Link')->icon(Heroicon::OutlinedLink)->color('gray')->url(fn (Checkout $record) => $record->recoveryUrl(), shouldOpenInNewTab: true),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCheckouts::route('/')];
    }
}
