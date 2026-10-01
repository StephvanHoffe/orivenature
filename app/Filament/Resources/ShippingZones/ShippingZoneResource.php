<?php

namespace App\Filament\Resources\ShippingZones;

use App\Filament\Resources\ShippingZones\Pages\CreateShippingZone;
use App\Filament\Resources\ShippingZones\Pages\EditShippingZone;
use App\Filament\Resources\ShippingZones\Pages\ListShippingZones;
use App\Filament\Support\Money;
use App\Models\ShippingZone;
use App\Support\Countries;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ShippingZoneResource extends Resource
{
    protected static ?string $model = ShippingZone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Instellingen';

    protected static ?string $navigationLabel = 'Verzending';

    protected static ?string $modelLabel = 'verzendzone';

    protected static ?string $pluralModelLabel = 'verzendzones';

    protected static ?string $slug = 'verzending';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->canManageSettings();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Zone')->columnSpanFull()->schema([
                TextInput::make('name')->label('Naam')->required()->placeholder('bijv. Nederland en België'),
                Select::make('countries')->label('Landen')->options(Countries::LIST)->multiple()->required()->searchable(),
            ]),
            Section::make('Tarieven')->description('De klant kiest aan de kassa uit de tarieven die passen bij het bestelbedrag.')->columnSpanFull()->schema([
                Repeater::make('rates')->hiddenLabel()->relationship()->orderColumn('position')->addActionLabel('Tarief toevoegen')
                    ->itemLabel(fn (array $state) => $state['name'] ?? null)->collapsible()
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('name')->label('Naam')->required()->placeholder('bijv. PostNL'),
                            TextInput::make('description')->label('Omschrijving')->placeholder('bijv. 1-2 werkdagen'),
                            Money::input('price')->label('Prijs')->required()->default(0)->helperText('0 = gratis'),
                            Money::input('min_subtotal')->label('Vanaf bestelbedrag')->helperText('Bijv. gratis vanaf € 50'),
                            Money::input('max_subtotal')->label('Tot bestelbedrag'),
                            Toggle::make('is_active')->label('Actief')->default(true)->inline(false),
                        ]),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')->label('Zone')->weight('bold'),
                TextColumn::make('countries')->label('Landen')->formatStateUsing(fn ($state) => Countries::name($state))->badge()->color('gray'),
                TextColumn::make('rates_list')->label('Tarieven')->state(fn (ShippingZone $record) => $record->rates->map(fn ($r) => $r->name.' '.($r->price ? money($r->price) : 'gratis'))->implode(' · '))->wrap(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShippingZones::route('/'),
            'create' => CreateShippingZone::route('/nieuw'),
            'edit' => EditShippingZone::route('/{record}'),
        ];
    }
}
