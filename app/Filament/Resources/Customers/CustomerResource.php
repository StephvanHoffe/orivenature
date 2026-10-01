<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\Customers\RelationManagers\CreditRelationManager;
use App\Filament\Resources\Customers\RelationManagers\LoyaltyRelationManager;
use App\Filament\Resources\Customers\RelationManagers\OrdersRelationManager;
use App\Filament\Support\Csv;
use App\Models\Customer;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Klanten';

    protected static ?string $navigationLabel = 'Klanten';

    protected static ?string $modelLabel = 'klant';

    protected static ?string $pluralModelLabel = 'klanten';

    protected static ?string $slug = 'klanten';

    protected static ?int $navigationSort = 1;

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email', 'phone'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name ?: $record->email;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['E-mail' => $record->email];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Group::make([
                    Section::make('Klantgegevens')->schema([
                        TextInput::make('first_name')->label('Voornaam')->maxLength(100),
                        TextInput::make('last_name')->label('Achternaam')->maxLength(100),
                        TextInput::make('email')->label('E-mailadres')->email()->required()->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn ($state) => strtolower(trim($state))),
                        TextInput::make('phone')->label('Telefoon')->tel(),
                    ])->columns(2),
                    Section::make('Notities')->schema([
                        Textarea::make('note')->label('Interne notitie')->rows(3),
                        TagsInput::make('tags')->label('Labels')->placeholder('bijv. vip, horeca'),
                    ]),
                ])->columnSpan(2),
                Group::make([
                    Section::make('Overzicht')->schema([
                        Text::make(fn (?Customer $record) => $record
                            ? $record->orders()->whereNotNull('paid_at')->count().' bestellingen · '.money((int) $record->orders()->whereNotNull('paid_at')->sum('total')).' besteed'
                            : 'Nieuwe klant'),
                        Text::make(fn (?Customer $record) => $record ? 'Spaarpunten: '.$record->points_balance.' · Tegoed: '.money($record->credit_balance) : '')
                            ->visible(fn (?Customer $record) => (bool) $record),
                        Text::make(fn (?Customer $record) => $record?->hasAccount() ? 'Heeft een account'.($record->last_login_at ? ', laatst ingelogd '.$record->last_login_at->diffForHumans() : '') : 'Geen account (gast)')
                            ->visible(fn (?Customer $record) => (bool) $record),
                    ]),
                    Section::make('Marketing')->schema([
                        Toggle::make('accepts_marketing')->label('Ontvangt e-mailmarketing'),
                    ]),
                    Section::make('Account')->schema([
                        TextInput::make('new_password')->label('Nieuw wachtwoord instellen')->password()->revealable()->minLength(8)
                            ->dehydrated(fn ($state) => filled($state))->helperText('Laat leeg om niets te wijzigen.'),
                    ])->collapsible()->collapsed(),
                ])->columnSpan(1),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount(['orders as paid_orders_count' => fn ($o) => $o->whereNotNull('paid_at')])
                ->withSum(['orders as spent' => fn ($o) => $o->whereNotNull('paid_at')], 'total'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Naam')->state(fn (Customer $record) => $record->name ?: '–')->description(fn (Customer $record) => $record->email)
                    ->searchable(['first_name', 'last_name', 'email']),
                TextColumn::make('city')->label('Plaats')->state(fn (Customer $record) => $record->defaultAddress()?->city)->toggleable(),
                TextColumn::make('paid_orders_count')->label('Bestellingen')->alignCenter()->sortable(),
                TextColumn::make('spent')->label('Besteed')->formatStateUsing(fn ($state) => money((int) $state))->alignEnd()->sortable(),
                TextColumn::make('points_balance')->label('Punten')->alignEnd()->sortable()->visible(fn () => (bool) settings('loyalty.enabled')),
                TextColumn::make('credit_balance')->label('Tegoed')->formatStateUsing(fn ($state) => money($state))->alignEnd()->sortable(),
                IconColumn::make('accepts_marketing')->label('Nieuwsbrief')->boolean()->alignCenter(),
                IconColumn::make('password')->label('Account')->state(fn (Customer $record) => $record->hasAccount())->boolean()->alignCenter()->toggleable(),
                TextColumn::make('created_at')->label('Klant sinds')->date('j M Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('accepts_marketing')->label('Nieuwsbrief'),
                TernaryFilter::make('account')->label('Account')->queries(
                    true: fn ($query) => $query->whereNotNull('password'), false: fn ($query) => $query->whereNull('password'),
                ),
                TernaryFilter::make('returning')->label('Terugkerende klant')->queries(
                    true: fn ($query) => $query->has('orders', '>=', 2), false: fn ($query) => $query->has('orders', '<', 2),
                ),
            ])
            ->recordActions([EditAction::make()->label('Openen')])
            ->toolbarActions([
                BulkAction::make('export')->label('Exporteren (CSV)')->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn (Collection $records) => self::export($records))->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ]);
    }

    public static function export(iterable $customers)
    {
        $rows = (function () use ($customers) {
            foreach ($customers as $c) {
                $a = $c->defaultAddress();
                yield [$c->first_name, $c->last_name, $c->email, $c->phone, $c->accepts_marketing ? 'ja' : 'nee',
                    $a?->address1, $a?->zip, $a?->city, $a?->country_code, $c->orders()->whereNotNull('paid_at')->count(),
                    money((int) $c->orders()->whereNotNull('paid_at')->sum('total'), false), $c->points_balance, money($c->credit_balance, false),
                    implode(', ', $c->tags ?? []), $c->created_at?->format('Y-m-d')];
            }
        })();

        return Csv::download('klanten-'.now()->format('Y-m-d').'.csv', [
            'Voornaam', 'Achternaam', 'E-mail', 'Telefoon', 'Nieuwsbrief', 'Adres', 'Postcode', 'Plaats', 'Land', 'Bestellingen', 'Besteed', 'Punten', 'Tegoed', 'Labels', 'Klant sinds',
        ], $rows);
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
            AddressesRelationManager::class,
            LoyaltyRelationManager::class,
            CreditRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/nieuw'),
            'edit' => EditCustomer::route('/{record}'),
        ];
    }
}
