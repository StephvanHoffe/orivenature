<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Instellingen';

    protected static ?string $navigationLabel = 'Gebruikers';

    protected static ?string $modelLabel = 'gebruiker';

    protected static ?string $pluralModelLabel = 'gebruikers';

    protected static ?string $slug = 'gebruikers';

    protected static ?int $navigationSort = 20;

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->canManageSettings();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->columns(2)->schema([
                TextInput::make('name')->label('Naam')->required(),
                TextInput::make('email')->label('E-mailadres')->email()->required()->unique(ignoreRecord: true),
                Select::make('role')->label('Rol')->options(User::ROLES)->default('staff')->required()
                    ->helperText('Medewerkers kunnen bestellingen, producten en klanten beheren, maar geen instellingen of gebruikers.')
                    ->disabled(fn (?User $record) => $record?->is(auth()->user())),
                Toggle::make('is_active')->label('Actief (mag inloggen)')->default(true)->inline(false)
                    ->disabled(fn (?User $record) => $record?->is(auth()->user())),
                TextInput::make('password')->label('Wachtwoord')->password()->revealable()->minLength(8)
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'Laat leeg om niets te wijzigen.' : null),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Naam')->weight('bold')->description(fn (User $record) => $record->email),
                TextColumn::make('role')->label('Rol')->badge()->formatStateUsing(fn ($state) => User::ROLES[$state] ?? $state),
                IconColumn::make('is_active')->label('Actief')->boolean(),
                TextColumn::make('created_at')->label('Sinds')->date('j M Y'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/nieuw'),
            'edit' => EditUser::route('/{record}'),
        ];
    }
}
