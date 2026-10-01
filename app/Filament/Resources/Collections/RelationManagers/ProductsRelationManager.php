<?php

namespace App\Filament\Resources\Collections\RelationManagers;

use App\Models\Product;
use App\Support\Media;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Producten in deze collectie';

    protected static ?string $modelLabel = 'product';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('position')
            ->defaultSort('collection_product.position')
            ->columns([
                ImageColumn::make('image')->label('')->state(fn (Product $record) => $record->featuredImage() ? Media::url($record->featuredImage()->path, 120) : null)->imageSize(40),
                TextColumn::make('title')->label('Product')->weight('bold'),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn ($state) => Product::STATUSES[$state] ?? $state),
            ])
            ->headerActions([AttachAction::make()->label('Product toevoegen')->preloadRecordSelect()->multiple()])
            ->recordActions([DetachAction::make()->label('Verwijderen uit collectie')])
            ->toolbarActions([DetachBulkAction::make()]);
    }
}
