<?php

namespace App\Filament\Resources\Subscribers;

use App\Filament\Resources\Subscribers\Pages\ListSubscribers;
use App\Filament\Support\Csv;
use App\Models\NewsletterSubscriber;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class SubscriberResource extends Resource
{
    protected static ?string $model = NewsletterSubscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Klanten';

    protected static ?string $navigationLabel = 'Nieuwsbrief';

    protected static ?string $modelLabel = 'abonnee';

    protected static ?string $pluralModelLabel = 'nieuwsbriefabonnees';

    protected static ?string $slug = 'nieuwsbrief';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')->label('E-mailadres')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('first_name')->label('Voornaam'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('subscribed_at', 'desc')
            ->columns([
                TextColumn::make('email')->label('E-mailadres')->searchable()->copyable(),
                TextColumn::make('first_name')->label('Naam')->placeholder('–'),
                TextColumn::make('source')->label('Bron')->badge()->color('gray'),
                TextColumn::make('subscribed_at')->label('Aangemeld')->date('j M Y')->sortable(),
                TextColumn::make('unsubscribed_at')->label('Status')->badge()->state(fn (NewsletterSubscriber $record) => $record->unsubscribed_at ? 'Afgemeld' : 'Actief')
                    ->color(fn ($state) => $state === 'Actief' ? 'success' : 'gray'),
            ])
            ->filters([TernaryFilter::make('active')->label('Actief')->queries(true: fn ($query) => $query->whereNull('unsubscribed_at'), false: fn ($query) => $query->whereNotNull('unsubscribed_at'))])
            ->recordActions([
                Action::make('toggle')->label(fn (NewsletterSubscriber $record) => $record->unsubscribed_at ? 'Weer aanmelden' : 'Afmelden')->color('gray')
                    ->action(fn (NewsletterSubscriber $record) => $record->update(['unsubscribed_at' => $record->unsubscribed_at ? null : now()])),
            ])
            ->toolbarActions([
                BulkAction::make('export')->label('Exporteren (CSV)')->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn (Collection $records) => self::export($records))->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ]);
    }

    public static function export(iterable $rows)
    {
        $data = (function () use ($rows) {
            foreach ($rows as $s) {
                yield [$s->email, $s->first_name, $s->source, $s->subscribed_at?->format('Y-m-d'), $s->unsubscribed_at ? 'afgemeld' : 'actief'];
            }
        })();

        return Csv::download('nieuwsbrief-'.now()->format('Y-m-d').'.csv', ['E-mail', 'Voornaam', 'Bron', 'Aangemeld', 'Status'], $data);
    }

    public static function getPages(): array
    {
        return ['index' => ListSubscribers::route('/')];
    }
}
