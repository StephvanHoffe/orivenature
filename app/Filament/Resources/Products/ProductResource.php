<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Support\Money;
use App\Models\Product;
use App\Support\Media;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Producten';

    protected static ?string $navigationLabel = 'Producten';

    protected static ?string $modelLabel = 'product';

    protected static ?string $pluralModelLabel = 'producten';

    protected static ?string $slug = 'producten';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 1;

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'handle', 'variants.sku'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Group::make([
                    Section::make()->schema([
                        TextInput::make('title')->label('Titel')->required()->maxLength(255)->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                                if ($operation === 'create' && blank($get('handle'))) {
                                    $set('handle', Str::slug((string) $state));
                                }
                            }),
                        Textarea::make('short_description')->label('Korte omschrijving')->rows(2)
                            ->helperText('Wordt getoond op productkaarten en in de snelle weergave.'),
                        RichEditor::make('description')->label('Beschrijving')
                            ->fileAttachmentsDisk('public')->fileAttachmentsDirectory('products/beschrijving')
                            ->toolbarButtons([['bold', 'italic', 'underline', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'blockquote'], ['attachFiles', 'table'], ['undo', 'redo']]),
                    ]),
                    Section::make('Media')->description('Sleep om de volgorde te wijzigen. De eerste foto is de hoofdfoto.')->schema([
                        Repeater::make('images')->hiddenLabel()->relationship()->orderColumn('position')->reorderableWithDragAndDrop()
                            ->grid(3)->addActionLabel('Foto toevoegen')->collapsible(false)->defaultItems(0)
                            ->schema([
                                FileUpload::make('path')->hiddenLabel()->image()->disk('public')->directory('products')->imageEditor()->required()
                                    ->maxSize(8192)->imagePreviewHeight('160'),
                                TextInput::make('alt')->hiddenLabel()->placeholder('Alt-tekst (voor Google en schermlezers)'),
                            ]),
                    ]),
                    Section::make('Varianten')->description('Bijvoorbeeld 50 gram en 100 gram. Een product zonder keuzes heeft één variant.')->schema([
                        TextInput::make('option_names.0')->label('Naam van de keuze')->placeholder('bijv. Inhoud')->maxWidth('sm'),
                        Repeater::make('variants')->hiddenLabel()->relationship()->orderColumn('position')->reorderableWithDragAndDrop()
                            ->addActionLabel('Variant toevoegen')->minItems(1)->defaultItems(1)
                            ->itemLabel(fn (array $state) => trim(($state['title'] ?? '') ?: 'Nieuwe variant').self::priceLabel($state['price'] ?? null))
                            ->collapsible()->cloneable()
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data) => self::variantData($data))
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data) => self::variantData($data))
                            ->schema([
                                Grid::make(4)->schema([
                                    TextInput::make('title')->label('Naam')->placeholder('bijv. 50 gram')->default('Standaard')->required()->columnSpan(2),
                                    Money::input('price')->label('Prijs')->required(),
                                    Money::input('compare_at_price')->label('Van-prijs')->helperText('Doorgestreept getoond'),
                                    TextInput::make('sku')->label('SKU'),
                                    TextInput::make('barcode')->label('Barcode (EAN)'),
                                    Money::input('cost')->label('Inkoopprijs')->helperText('Alleen voor jezelf'),
                                    TextInput::make('weight_grams')->label('Gewicht')->numeric()->suffix('gram'),
                                    Toggle::make('track_stock')->label('Voorraad bijhouden')->live()->inline(false),
                                    TextInput::make('stock')->label('Voorraad')->integer()->default(0)->visible(fn (Get $get) => (bool) $get('track_stock')),
                                    Toggle::make('allow_backorder')->label('Doorverkopen bij 0')->inline(false)->visible(fn (Get $get) => (bool) $get('track_stock')),
                                    Toggle::make('is_bestseller')->label('Bestseller-label')->inline(false),
                                    Select::make('image_id')->label('Foto')->columnSpan(2)
                                        ->options(fn (?Model $record) => $record?->product?->images()->pluck('path', 'id')->map(fn ($p) => basename($p))->all() ?? [])
                                        ->placeholder('Hoofdfoto van het product')->helperText('Na opslaan kun je hier een foto kiezen'),
                                ]),
                            ]),
                    ]),
                    Section::make('Zoekmachines (SEO)')->schema([
                        TextInput::make('handle')->label('URL')->prefix(url('/products').'/')->required()->unique(ignoreRecord: true)
                            ->rule('regex:/^[a-z0-9-]+$/')->helperText('Alleen kleine letters, cijfers en streepjes. Wijzig je dit, maak dan een doorverwijzing aan.'),
                        TextInput::make('seo_title')->label('Paginatitel')->maxLength(70)->placeholder(fn (Get $get) => $get('title')),
                        Textarea::make('seo_description')->label('Meta-omschrijving')->rows(2)->maxLength(320),
                    ])->collapsible()->collapsed(),
                ])->columnSpan(2),
                Group::make([
                    Section::make('Status')->schema([
                        Select::make('status')->hiddenLabel()->options(Product::STATUSES)->default('active')->required()->native(false),
                        DateTimePicker::make('published_at')->label('Gepubliceerd vanaf')->native(false)->seconds(false),
                    ]),
                    Section::make('Organisatie')->schema([
                        Select::make('collections')->label('Collecties')->relationship('collections', 'title')->multiple()->preload(),
                        TextInput::make('product_type')->label('Producttype')->datalist(fn () => Product::whereNotNull('product_type')->distinct()->pluck('product_type')->all()),
                        TextInput::make('vendor')->label('Merk')->default(fn () => settings('store.name')),
                        TagsInput::make('tags')->label('Labels'),
                        Select::make('tax_rate')->label('Btw-tarief')->options(['9' => '9% (voedingsmiddelen)', '21' => '21% (overig)', '0' => '0%'])->default('9')->required()
                            ->formatStateUsing(fn ($state) => $state === null ? '9' : (string) (int) $state),
                    ]),
                    Section::make('Weergave in de winkel')->schema([
                        TextInput::make('chip')->label('Label op de kaart')->placeholder('bijv. nieuw of ceremonieel · Japan'),
                        Toggle::make('chip_highlight')->label('Label laten opvallen'),
                        ColorPicker::make('tone')->label('Achtergrondkleur'),
                    ])->collapsible(),
                ])->columnSpan(1),
            ]),
        ]);
    }

    private static function priceLabel(mixed $price): string
    {
        if ($price === null || $price === '') {
            return '';
        }

        // Bij het laden staat de prijs nog in centen, na het typen als "37,95"
        return ' · '.(is_int($price) || ctype_digit((string) $price) ? money((int) $price) : '€'.$price);
    }

    /** De keuze (option1) is gelijk aan de naam van de variant; lege getallen worden 0. */
    public static function variantData(array $data): array
    {
        $data['option1'] = $data['title'] ?? null;
        foreach (['weight_grams', 'stock'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] === null) {
                $data[$key] = 0;
            }
        }

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['images', 'variants', 'collections']))
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                ImageColumn::make('image')->label('')->state(fn (Product $record) => $record->featuredImage() ? Media::url($record->featuredImage()->path, 120) : null)
                    ->imageSize(44)->extraImgAttributes(['style' => 'object-fit:contain;border-radius:10px;background:#f4f2ea']),
                TextColumn::make('title')->label('Product')->searchable()->sortable()->weight('bold')
                    ->description(fn (Product $record) => $record->variants->count() > 1 ? $record->variants->count().' varianten' : null),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn ($state) => Product::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success', 'draft' => 'warning', default => 'gray'
                    }),
                TextColumn::make('stock')->label('Voorraad')->state(function (Product $record) {
                    $tracked = $record->variants->where('track_stock', true);
                    if ($tracked->isEmpty()) {
                        return 'Niet bijgehouden';
                    }

                    return $tracked->sum('stock').' op voorraad';
                })->color(fn (Product $record) => $record->variants->where('track_stock', true)->contains(fn ($v) => $v->stock <= (int) settings('products.low_stock_threshold', 10)) ? 'danger' : null),
                TextColumn::make('price')->label('Prijs')->state(function (Product $record) {
                    $prices = $record->variants->pluck('price');

                    return $prices->min() === $prices->max() ? money($prices->min()) : money($prices->min()).' – '.money($prices->max());
                }),
                TextColumn::make('collections.title')->label('Collecties')->badge()->color('gray')->toggleable(),
                TextColumn::make('product_type')->label('Type')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(Product::STATUSES),
                SelectFilter::make('collections')->label('Collectie')->relationship('collections', 'title'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('view')->label('Bekijk')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                    ->url(fn (Product $record) => $record->url(), shouldOpenInNewTab: true),
                ReplicateAction::make()->label('Dupliceren')
                    ->mutateRecordDataUsing(fn (array $data) => array_merge($data, ['title' => $data['title'].' (kopie)', 'handle' => $data['handle'].'-kopie-'.Str::lower(Str::random(4)), 'status' => 'draft', 'shopify_id' => null]))
                    ->after(function (Product $replica, Product $record) {
                        foreach ($record->variants as $variant) {
                            $replica->variants()->create(collect($variant->getAttributes())->except(['id', 'product_id', 'image_id', 'shopify_id', 'created_at', 'updated_at'])->all());
                        }
                        foreach ($record->images as $image) {
                            $replica->images()->create(['path' => $image->path, 'alt' => $image->alt, 'position' => $image->position]);
                        }
                        $replica->collections()->sync($record->collections->pluck('id'));
                    })
                    ->successRedirectUrl(fn (Product $replica) => self::getUrl('edit', ['record' => $replica])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')->label('Actief maken')->icon(Heroicon::OutlinedEye)
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'active']))->deselectRecordsAfterCompletion(),
                    BulkAction::make('draft')->label('Naar concept')->icon(Heroicon::OutlinedEyeSlash)
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'draft']))->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/nieuw'),
            'edit' => EditProduct::route('/{record}'),
        ];
    }
}
