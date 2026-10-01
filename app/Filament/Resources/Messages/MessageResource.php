<?php

namespace App\Filament\Resources\Messages;

use App\Filament\Resources\Messages\Pages\ListMessages;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class MessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'Klanten';

    protected static ?string $navigationLabel = 'Berichten';

    protected static ?string $modelLabel = 'bericht';

    protected static ?string $pluralModelLabel = 'berichten';

    protected static ?string $slug = 'berichten';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $open = ContactMessage::whereNull('handled_at')->count();

        return $open ? (string) $open : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('type')->label('Soort')->formatStateUsing(fn ($state) => ContactMessage::TYPES[$state] ?? $state),
            TextEntry::make('created_at')->label('Ontvangen')->dateTime('j M Y H:i'),
            TextEntry::make('name')->label('Naam'),
            TextEntry::make('email')->label('E-mail')->copyable(),
            TextEntry::make('phone')->label('Telefoon')->placeholder('–'),
            TextEntry::make('company')->label('Bedrijf')->placeholder('–'),
            TextEntry::make('data')->label('Extra')->state(fn (ContactMessage $record) => collect($record->extraFields())->map(fn ($v, $k) => ucfirst($k).': '.$v)->implode(' · ') ?: '–')->columnSpanFull(),
            TextEntry::make('message')->label('Bericht')->formatStateUsing(fn ($state) => nl2br(e($state)))->html()->placeholder('–')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Ontvangen')->since()->sortable(),
                TextColumn::make('type')->label('Soort')->badge()->formatStateUsing(fn ($state) => ContactMessage::TYPES[$state] ?? $state),
                TextColumn::make('name')->label('Van')->description(fn (ContactMessage $record) => $record->email)->searchable(['name', 'email', 'company']),
                TextColumn::make('message')->label('Bericht')->limit(80)->wrap()
                    ->description(fn (ContactMessage $record) => ! empty($record->data['bestelling']) ? $record->data['bestelling'].' · '.($record->data['onderwerp'] ?? '') : null, 'above'),
                TextColumn::make('handled_at')->label('Status')->badge()->state(fn (ContactMessage $record) => $record->handled_at ? 'Afgehandeld' : 'Nieuw')
                    ->color(fn ($state) => $state === 'Nieuw' ? 'warning' : 'success'),
            ])
            ->filters([
                SelectFilter::make('type')->label('Soort')->options(ContactMessage::TYPES),
                TernaryFilter::make('handled_at')->label('Afgehandeld')->nullable(),
            ])
            ->recordActions([
                ViewAction::make()->label('Lezen')->modalFooterActions(fn (ContactMessage $record) => [
                    Action::make('reply')->label('Beantwoorden')->icon(Heroicon::OutlinedEnvelope)->url('mailto:'.$record->email.'?subject='.rawurlencode('Re: je bericht aan '.settings('store.name'))),
                ]),
                Action::make('order')->label('Bestelling')->icon(Heroicon::OutlinedShoppingBag)->color('gray')
                    ->visible(fn (ContactMessage $record) => ! empty($record->data['order_id']))
                    ->url(fn (ContactMessage $record) => OrderResource::getUrl('view', ['record' => $record->data['order_id']])),
                Action::make('handled')->label(fn (ContactMessage $record) => $record->handled_at ? 'Heropenen' : 'Afgehandeld')->icon(Heroicon::OutlinedCheck)->color('gray')
                    ->action(fn (ContactMessage $record) => $record->update(['handled_at' => $record->handled_at ? null : now()])),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListMessages::route('/')];
    }
}
