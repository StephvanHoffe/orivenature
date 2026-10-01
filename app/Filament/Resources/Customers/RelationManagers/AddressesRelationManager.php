<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Models\CustomerAddress;
use App\Support\Countries;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Adressen';

    protected static ?string $modelLabel = 'adres';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('first_name')->label('Voornaam'),
            TextInput::make('last_name')->label('Achternaam'),
            TextInput::make('company')->label('Bedrijf'),
            TextInput::make('phone')->label('Telefoon'),
            TextInput::make('address1')->label('Straat en huisnummer')->required(),
            TextInput::make('address2')->label('Toevoeging'),
            TextInput::make('zip')->label('Postcode')->required(),
            TextInput::make('city')->label('Plaats')->required(),
            Select::make('country_code')->label('Land')->options(Countries::LIST)->default('NL')->required(),
            Toggle::make('is_default')->label('Standaardadres'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('formatted')->label('Adres')->state(fn (CustomerAddress $record) => nl2br(e($record->formatted())))->html(),
                IconColumn::make('is_default')->label('Standaard')->boolean(),
            ])
            ->headerActions([CreateAction::make()->label('Adres toevoegen')->after(fn (CustomerAddress $record) => $this->syncDefault($record))])
            ->recordActions([EditAction::make()->after(fn (CustomerAddress $record) => $this->syncDefault($record)), DeleteAction::make()]);
    }

    private function syncDefault(CustomerAddress $address): void
    {
        if ($address->is_default) {
            $address->customer->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
        }
    }
}
