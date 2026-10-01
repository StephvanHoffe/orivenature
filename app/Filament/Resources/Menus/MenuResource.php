<?php

namespace App\Filament\Resources\Menus;

use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Filament\Resources\Menus\Pages\ListMenus;
use App\Models\Menu;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|UnitEnum|null $navigationGroup = 'Webshop';

    protected static ?string $navigationLabel = 'Navigatie';

    protected static ?string $modelLabel = 'menu';

    protected static ?string $pluralModelLabel = "menu's";

    protected static ?string $slug = 'navigatie';

    protected static ?int $navigationSort = 5;

    private static function itemFields(): array
    {
        return [
            Grid::make(3)->schema([
                TextInput::make('title')->label('Tekst')->required(),
                TextInput::make('url')->label('Link')->required()->placeholder('/collections/matcha-essence of https://…'),
                TextInput::make('badge')->label('Label')->placeholder('bijv. nieuw'),
            ]),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->columns(2)->schema([
                TextInput::make('name')->label('Naam')->required(),
                TextInput::make('handle')->label('Code')->required()->unique(ignoreRecord: true)->disabledOn('edit')
                    ->helperText('Gebruikt door de winkel: main-menu (hoofdmenu), ons-bedrijf, klantenservice en ons-assortiment (footer).'),
            ]),
            Section::make('Menu-items')->columnSpanFull()->schema([
                Repeater::make('items')->hiddenLabel()->relationship()->orderColumn('position')->reorderableWithDragAndDrop()->collapsible()
                    ->itemLabel(fn (array $state) => $state['title'] ?? null)->addActionLabel('Item toevoegen')
                    ->schema([
                        ...self::itemFields(),
                        Repeater::make('children')->label('Subitems')->relationship()->orderColumn('position')->collapsible()->collapsed()
                            ->itemLabel(fn (array $state) => $state['title'] ?? null)->addActionLabel('Subitem toevoegen')->defaultItems(0)
                            ->schema(self::itemFields()),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Menu')->weight('bold')->description(fn (Menu $record) => $record->handle),
                TextColumn::make('items_list')->label('Items')->state(fn (Menu $record) => $record->items->pluck('title')->implode(' · '))->wrap(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/nieuw'),
            'edit' => EditMenu::route('/{record}'),
        ];
    }
}
