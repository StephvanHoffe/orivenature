<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\Pages\ListInventory;
use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductVariant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class InventoryResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Producten';

    protected static ?string $navigationLabel = 'Voorraad';

    protected static ?string $modelLabel = 'variant';

    protected static ?string $pluralModelLabel = 'voorraad';

    protected static ?string $slug = 'voorraad';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $low = ProductVariant::where('track_stock', true)->where('stock', '<=', (int) settings('products.low_stock_threshold', 10))->count();

        return $low ? (string) $low : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('product')->whereHas('product'))
            ->defaultSort('product_id')
            ->columns([
                TextColumn::make('product.title')->label('Product')->weight('bold')->searchable()
                    ->description(fn (ProductVariant $record) => $record->product->hasOnlyDefaultVariant() ? null : $record->title)
                    ->url(fn (ProductVariant $record) => ProductResource::getUrl('edit', ['record' => $record->product_id])),
                TextColumn::make('sku')->label('SKU')->searchable()->placeholder('–'),
                ToggleColumn::make('track_stock')->label('Bijhouden'),
                TextInputColumn::make('stock')->label('Op voorraad')->type('number')->rules(['integer'])->width('8rem')
                    ->disabled(fn (ProductVariant $record) => ! $record->track_stock),
                ToggleColumn::make('allow_backorder')->label('Doorverkopen bij 0')->disabled(fn (ProductVariant $record) => ! $record->track_stock),
                TextColumn::make('status')->label('')->badge()->state(fn (ProductVariant $record) => ! $record->track_stock ? null
                    : ($record->stock <= 0 ? 'Uitverkocht' : ($record->stock <= (int) settings('products.low_stock_threshold', 10) ? 'Bijna op' : null)))
                    ->color(fn ($state) => $state === 'Uitverkocht' ? 'danger' : 'warning'),
            ])
            ->filters([
                TernaryFilter::make('track_stock')->label('Voorraad bijgehouden'),
                TernaryFilter::make('low')->label('Bijna op of uitverkocht')
                    ->queries(true: fn ($query) => $query->where('track_stock', true)->where('stock', '<=', (int) settings('products.low_stock_threshold', 10)), false: fn ($query) => $query),
            ])
            ->recordActions([
                Action::make('adjust')->label('Ontvangen')->icon(Heroicon::OutlinedPlusCircle)->color('gray')
                    ->visible(fn (ProductVariant $record) => $record->track_stock)
                    ->schema([TextInput::make('quantity')->label('Aantal ontvangen (of negatief om af te boeken)')->integer()->required()])
                    ->action(fn (ProductVariant $record, array $data) => $record->increment('stock', (int) $data['quantity'])),
            ])
            ->toolbarActions([
                BulkAction::make('track')->label('Voorraad bijhouden aanzetten')->icon(Heroicon::OutlinedCheck)
                    ->action(fn (Collection $records) => $records->each->update(['track_stock' => true]))->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListInventory::route('/')];
    }
}
