<?php

namespace App\Filament\Resources\Collections;

use App\Filament\Resources\Collections\Pages\CreateCollection;
use App\Filament\Resources\Collections\Pages\EditCollection;
use App\Filament\Resources\Collections\Pages\ListCollections;
use App\Filament\Resources\Collections\RelationManagers\ProductsRelationManager;
use App\Models\Collection;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Producten';

    protected static ?string $navigationLabel = 'Collecties';

    protected static ?string $modelLabel = 'collectie';

    protected static ?string $pluralModelLabel = 'collecties';

    protected static ?string $slug = 'collecties';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Group::make([
                    Section::make()->schema([
                        TextInput::make('title')->label('Titel')->required()->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set, ?string $state, string $operation) => $operation === 'create' && blank($get('handle')) ? $set('handle', Str::slug((string) $state)) : null),
                        RichEditor::make('description')->label('Omschrijving')
                            ->toolbarButtons([['bold', 'italic', 'link'], ['h2', 'h3', 'bulletList'], ['undo', 'redo']]),
                    ]),
                    Section::make('Zoekmachines (SEO)')->schema([
                        TextInput::make('handle')->label('URL')->prefix(url('/collections').'/')->required()->unique(ignoreRecord: true)->rule('regex:/^[a-z0-9-]+$/'),
                        TextInput::make('seo_title')->label('Paginatitel')->maxLength(70),
                        Textarea::make('seo_description')->label('Meta-omschrijving')->rows(2)->maxLength(320),
                    ])->collapsible()->collapsed(),
                ])->columnSpan(2),
                Group::make([
                    Section::make('Zichtbaarheid')->schema([
                        Toggle::make('is_visible')->label('Zichtbaar in de winkel')->default(true),
                        Select::make('sort_order')->label('Sortering producten')->options(Collection::SORT_ORDERS)->default('manual')->required(),
                    ]),
                    Section::make('Afbeelding')->schema([
                        FileUpload::make('image')->hiddenLabel()->image()->disk('public')->directory('collections')->imageEditor(),
                    ]),
                ])->columnSpan(1),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('products'))
            ->columns([
                TextColumn::make('title')->label('Collectie')->searchable()->sortable()->weight('bold')->description(fn (Collection $record) => $record->url()),
                TextColumn::make('products_count')->label('Producten')->alignCenter(),
                TextColumn::make('sort_order')->label('Sortering')->formatStateUsing(fn ($state) => Collection::SORT_ORDERS[$state] ?? $state)->toggleable(),
                IconColumn::make('is_visible')->label('Zichtbaar')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('view')->label('Bekijk')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')->url(fn (Collection $record) => url($record->url()), shouldOpenInNewTab: true),
            ]);
    }

    public static function getRelations(): array
    {
        return [ProductsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollections::route('/'),
            'create' => CreateCollection::route('/nieuw'),
            'edit' => EditCollection::route('/{record}'),
        ];
    }
}
