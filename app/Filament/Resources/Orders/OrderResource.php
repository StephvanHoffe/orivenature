<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Support\Csv;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Bestellingen';

    protected static ?string $navigationLabel = 'Bestellingen';

    protected static ?string $modelLabel = 'bestelling';

    protected static ?string $pluralModelLabel = 'bestellingen';

    protected static ?string $slug = 'bestellingen';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $open = Order::where('status', 'open')->whereNotNull('paid_at')->where('fulfillment_status', '!=', 'fulfilled')->count();

        return $open ? (string) $open : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Betaald en nog te verzenden';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'email', 'customer.first_name', 'customer.last_name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name.' · '.$record->customerName();
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['Totaal' => money($record->grandTotal()), 'Status' => Order::FINANCIAL_STATUSES[$record->financial_status] ?? $record->financial_status];
    }

    public static function financialColor(?string $state): string
    {
        return match ($state) {
            'paid' => 'success',
            'pending' => 'warning',
            'partially_refunded', 'refunded' => 'gray',
            default => 'danger',
        };
    }

    public static function fulfillmentColor(?string $state): string
    {
        return match ($state) {
            'fulfilled' => 'success',
            'partially_fulfilled' => 'info',
            default => 'warning',
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('customer')->withCount('items'))
            ->defaultSort('placed_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('Bestelling')->formatStateUsing(fn ($state) => '#'.$state)->searchable()->sortable()->weight('bold'),
                TextColumn::make('placed_at')->label('Datum')->dateTime('j M Y, H:i')->sortable(),
                TextColumn::make('customer_name')->label('Klant')->state(fn (Order $record) => $record->customerName())
                    ->description(fn (Order $record) => $record->email)
                    ->searchable(query: fn (Builder $query, string $search) => $query->where(fn ($query) => $query->where('email', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")))),
                TextColumn::make('total')->label('Totaal')->state(fn (Order $record) => $record->grandTotal())->formatStateUsing(fn ($state) => money($state))->alignEnd()->sortable(),
                TextColumn::make('financial_status')->label('Betaling')->badge()
                    ->formatStateUsing(fn ($state) => Order::FINANCIAL_STATUSES[$state] ?? $state)->color(fn ($state) => self::financialColor($state)),
                TextColumn::make('fulfillment_status')->label('Verzending')->badge()
                    ->formatStateUsing(fn ($state) => Order::FULFILLMENT_STATUSES[$state] ?? $state)->color(fn ($state) => self::fulfillmentColor($state)),
                TextColumn::make('items_count')->label('Artikelen')->alignCenter()->toggleable(),
                TextColumn::make('shipping_address.country_code')->label('Land')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('discount_code')->label('Kortingscode')->badge()->color('gray')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('source')->label('Kanaal')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('financial_status')->label('Betaling')->options(Order::FINANCIAL_STATUSES)->multiple(),
                SelectFilter::make('fulfillment_status')->label('Verzending')->options(Order::FULFILLMENT_STATUSES)->multiple(),
                Filter::make('placed_at')->label('Periode')
                    ->schema([DatePicker::make('from')->label('Van'), DatePicker::make('until')->label('Tot en met')])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($query, $d) => $query->whereDate('placed_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($query, $d) => $query->whereDate('placed_at', '<=', $d))),
                SelectFilter::make('discount_code')->label('Kortingscode')
                    ->options(fn () => Order::whereNotNull('discount_code')->distinct()->orderBy('discount_code')->pluck('discount_code', 'discount_code')->all()),
            ])
            ->recordUrl(fn (Order $record) => self::getUrl('view', ['record' => $record]))
            ->recordActions([ViewAction::make()->label('Openen')])
            ->toolbarActions([
                BulkAction::make('export')->label('Exporteren (CSV)')->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn (Collection $records) => self::export($records))
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function export(iterable $orders)
    {
        $rows = [];
        foreach ($orders as $order) {
            $order->loadMissing('items');
            foreach ($order->items as $i => $item) {
                $a = $order->shipping_address ?? [];
                $rows[] = [
                    $order->name, $order->placed_at?->format('Y-m-d H:i'), $order->email, $order->customerName(),
                    Order::FINANCIAL_STATUSES[$order->financial_status] ?? $order->financial_status,
                    Order::FULFILLMENT_STATUSES[$order->fulfillment_status] ?? $order->fulfillment_status,
                    $i === 0 ? money($order->subtotal, false) : '', $i === 0 ? money($order->discount_total, false) : '',
                    $i === 0 ? money($order->shipping_total, false) : '', $i === 0 ? money($order->tax_total, false) : '',
                    $i === 0 ? money($order->grandTotal(), false) : '', $order->discount_code,
                    $item->title.($item->variant_title ? ' - '.$item->variant_title : ''), $item->sku, $item->quantity, money($item->price, false),
                    trim(($a['address1'] ?? '').' '.($a['address2'] ?? '')), $a['zip'] ?? '', $a['city'] ?? '', $a['country_code'] ?? '', $a['phone'] ?? $order->phone,
                ];
            }
        }

        return Csv::download('bestellingen-'.now()->format('Y-m-d').'.csv', [
            'Bestelling', 'Datum', 'E-mail', 'Naam', 'Betaling', 'Verzending', 'Subtotaal', 'Korting', 'Verzendkosten', 'Btw', 'Totaal', 'Kortingscode',
            'Artikel', 'SKU', 'Aantal', 'Prijs', 'Adres', 'Postcode', 'Plaats', 'Land', 'Telefoon',
        ], $rows);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Group::make([
                    Section::make('Artikelen')->schema([
                        ViewEntry::make('items')->hiddenLabel()->view('filament.orders.items'),
                    ]),
                    Section::make('Betaling')->schema([
                        ViewEntry::make('summary')->hiddenLabel()->view('filament.orders.summary'),
                    ]),
                    Section::make('Tijdlijn')->schema([
                        ViewEntry::make('events')->hiddenLabel()->view('filament.orders.timeline'),
                    ])->collapsible(),
                ])->columnSpan(2),
                Group::make([
                    Section::make('Klant')->schema([
                        ViewEntry::make('customer')->hiddenLabel()->view('filament.orders.customer'),
                    ]),
                    Section::make('Bezorgadres')->schema([
                        TextEntry::make('shipping')->hiddenLabel()->state(fn (Order $record) => nl2br(e($record->formattedShippingAddress() ?: '–')))->html()
                            ->copyable()->copyMessage('Adres gekopieerd'),
                        TextEntry::make('shipping_method')->label('Verzendmethode')->placeholder('–'),
                    ]),
                    Section::make('Factuuradres')->schema([
                        TextEntry::make('billing')->hiddenLabel()->state(fn (Order $record) => nl2br(e($record->billing_address ? $record->formattedBillingAddress() : 'Zelfde als bezorgadres')))->html(),
                    ])->collapsible()->collapsed(),
                    Section::make('Notities')->schema([
                        TextEntry::make('customer_note')->label('Opmerking van de klant')->placeholder('Geen')->formatStateUsing(fn ($state) => nl2br(e($state)))->html(),
                        TextEntry::make('note')->label('Interne notitie')->placeholder('Geen')->formatStateUsing(fn ($state) => nl2br(e($state)))->html(),
                        TextEntry::make('tags')->label('Labels')->badge()->placeholder('Geen'),
                    ]),
                    Section::make('Spaarprogramma')->schema([
                        TextEntry::make('points_earned')->label('Verdiende punten')
                            ->state(fn (Order $record) => $record->points_awarded_at ? $record->points_earned.' punten' : ($record->customer_id ? 'Nog niet toegekend' : 'Geen klant gekoppeld')),
                    ])->visible(fn () => (bool) settings('loyalty.enabled')),
                ])->columnSpan(1),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
