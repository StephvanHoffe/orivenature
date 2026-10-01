<?php

namespace App\Filament\Resources\Redirects;

use App\Filament\Resources\Redirects\Pages\ListRedirects;
use App\Models\Redirect;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnRight;

    protected static string|UnitEnum|null $navigationGroup = 'Webshop';

    protected static ?string $navigationLabel = 'Doorverwijzingen';

    protected static ?string $modelLabel = 'doorverwijzing';

    protected static ?string $pluralModelLabel = 'doorverwijzingen';

    protected static ?string $slug = 'doorverwijzingen';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        $path = fn ($state) => '/'.ltrim(trim((string) parse_url((string) $state, PHP_URL_PATH).(parse_url((string) $state, PHP_URL_QUERY) ? '?'.parse_url((string) $state, PHP_URL_QUERY) : '')), '/');

        return $schema->components([
            TextInput::make('from_path')->label('Oud adres')->placeholder('/products/oude-naam')->required()->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn ($state) => rtrim($path($state), '/') ?: '/'),
            TextInput::make('to_path')->label('Nieuw adres')->placeholder('/products/nieuwe-naam')->required()
                ->dehydrateStateUsing(fn ($state) => str_starts_with((string) $state, 'http') ? $state : $path($state)),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('hits', 'desc')
            ->columns([
                TextColumn::make('from_path')->label('Oud adres')->searchable(),
                TextColumn::make('to_path')->label('Nieuw adres')->searchable(),
                TextColumn::make('hits')->label('Gebruikt')->alignEnd()->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListRedirects::route('/')];
    }
}
