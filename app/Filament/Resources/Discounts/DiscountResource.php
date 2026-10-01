<?php

namespace App\Filament\Resources\Discounts;

use App\Filament\Resources\Discounts\Pages\CreateDiscount;
use App\Filament\Resources\Discounts\Pages\EditDiscount;
use App\Filament\Resources\Discounts\Pages\ListDiscounts;
use App\Filament\Support\Money;
use App\Models\Collection;
use App\Models\Discount;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Kortingen';

    protected static ?string $modelLabel = 'korting';

    protected static ?string $pluralModelLabel = 'kortingen';

    protected static ?string $slug = 'kortingen';

    protected static ?int $navigationSort = 1;

    public const TYPES = ['percentage' => 'Percentage', 'fixed' => 'Vast bedrag', 'free_shipping' => 'Gratis verzending'];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Group::make([
                    Section::make('Korting')->schema([
                        Toggle::make('is_automatic')->label('Automatische korting (zonder code, geldt voor iedereen die aan de voorwaarden voldoet)')->default(false)->live(),
                        TextInput::make('code')->label('Kortingscode')->visible(fn (Get $get) => ! $get('is_automatic'))->required(fn (Get $get) => ! $get('is_automatic'))
                            ->unique(ignoreRecord: true)->dehydrateStateUsing(fn ($state) => $state ? strtoupper(trim($state)) : null)
                            ->suffixAction(Action::make('generate')->icon(Heroicon::OutlinedSparkles)->tooltip('Code maken')
                                ->action(fn (Set $set) => $set('code', strtoupper(Str::random(8))))),
                        TextInput::make('title')->label('Interne naam')->required()->helperText('Bij automatische kortingen ziet de klant deze naam in de winkelwagen.'),
                    ]),
                    Section::make('Waarde')->schema([
                        Select::make('type')->label('Type')->options(self::TYPES)->default('percentage')->required()->live(),
                        TextInput::make('value')->label('Waarde')->required(fn (Get $get) => $get('type') !== 'free_shipping')
                            ->visible(fn (Get $get) => $get('type') !== 'free_shipping')
                            ->prefix(fn (Get $get) => $get('type') === 'fixed' ? '€' : null)->suffix(fn (Get $get) => $get('type') === 'percentage' ? '%' : null)
                            ->rule('regex:/^\d+([.,]\d{1,2})?$/'),
                        Select::make('applies_to')->label('Geldt voor')->options(['all' => 'Alle producten', 'collections' => 'Bepaalde collecties', 'products' => 'Bepaalde producten'])
                            ->default('all')->required()->live()->visible(fn (Get $get) => $get('type') !== 'free_shipping'),
                        Select::make('target_ids')->label(fn (Get $get) => $get('applies_to') === 'collections' ? 'Collecties' : 'Producten')->multiple()
                            ->options(fn (Get $get) => $get('applies_to') === 'collections' ? Collection::pluck('title', 'id')->all() : Product::pluck('title', 'id')->all())
                            ->visible(fn (Get $get) => in_array($get('applies_to'), ['collections', 'products'], true) && $get('type') !== 'free_shipping')->required(),
                    ])->columns(2),
                    Section::make('Voorwaarden')->schema([
                        Money::input('min_subtotal')->label('Minimaal bestelbedrag'),
                        TextInput::make('usage_limit')->label('Maximaal aantal keer te gebruiken')->integer()->minValue(1)->placeholder('onbeperkt'),
                        Toggle::make('once_per_customer')->label('Eén keer per klant'),
                    ])->columns(2),
                ])->columnSpan(2),
                Group::make([
                    Section::make('Actief')->schema([
                        Toggle::make('is_active')->label('Actief')->default(true),
                        DateTimePicker::make('starts_at')->label('Vanaf')->native(false)->seconds(false),
                        DateTimePicker::make('ends_at')->label('Tot en met')->native(false)->seconds(false),
                    ]),
                    Section::make('Gebruik')->schema([
                        Text::make(fn (?Discount $record) => $record ? $record->usage_count.' keer gebruikt' : 'Nog niet gebruikt'),
                        Text::make(fn (?Discount $record) => $record?->code ? 'Deelbare link: '.url('/discount/'.$record->code) : '')->visible(fn (?Discount $record) => (bool) $record?->code),
                    ]),
                ])->columnSpan(1),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Code')->state(fn (Discount $record) => $record->code ?: 'Automatisch')->badge()->color(fn (Discount $record) => $record->code ? 'primary' : 'info')
                    ->searchable()->copyable(),
                TextColumn::make('title')->label('Naam')->searchable()->description(fn (Discount $record) => $record->summary()),
                TextColumn::make('usage_count')->label('Gebruikt')->formatStateUsing(fn ($state, Discount $record) => $state.($record->usage_limit ? ' / '.$record->usage_limit : ''))->alignCenter(),
                TextColumn::make('period')->label('Periode')->state(fn (Discount $record) => ($record->starts_at?->format('j-n-Y') ?? 'altijd').($record->ends_at ? ' t/m '.$record->ends_at->format('j-n-Y') : ''))->toggleable(),
                TextColumn::make('status')->label('Status')->badge()->state(fn (Discount $record) => $record->isCurrentlyValid() ? 'Actief' : ($record->is_active && $record->starts_at?->isFuture() ? 'Gepland' : 'Verlopen/uit'))
                    ->color(fn ($state) => match ($state) {
                        'Actief' => 'success', 'Gepland' => 'info', default => 'gray'
                    }),
                ToggleColumn::make('is_active')->label('Aan'),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()->label('Dupliceren')->excludeAttributes(['usage_count'])
                    ->mutateRecordDataUsing(fn (array $data) => array_merge($data, ['code' => $data['code'] ? $data['code'].'-2' : null, 'title' => $data['title'].' (kopie)'])),
            ]);
    }

    /** Waarde tonen in euro's of procenten; opslaan in centen of procenten. */
    public static function fillValue(array $data): array
    {
        if (($data['type'] ?? null) === 'fixed' && isset($data['value'])) {
            $data['value'] = number_format($data['value'] / 100, 2, ',', '');
        }

        return $data;
    }

    public static function saveValue(array $data): array
    {
        $data['value'] = match ($data['type'] ?? 'percentage') {
            'fixed' => to_cents($data['value'] ?? 0),
            'free_shipping' => 0,
            default => (int) min(100, (float) str_replace(',', '.', (string) ($data['value'] ?? 0))),
        };
        if (($data['type'] ?? null) === 'free_shipping' || ($data['applies_to'] ?? 'all') === 'all') {
            $data['applies_to'] = $data['type'] === 'free_shipping' ? 'all' : $data['applies_to'];
            $data['target_ids'] = null;
        }
        if (! empty($data['is_automatic'])) {
            $data['code'] = null;
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscounts::route('/'),
            'create' => CreateDiscount::route('/nieuw'),
            'edit' => EditDiscount::route('/{record}'),
        ];
    }
}
