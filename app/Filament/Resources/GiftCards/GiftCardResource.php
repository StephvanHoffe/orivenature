<?php

namespace App\Filament\Resources\GiftCards;

use App\Filament\Resources\GiftCards\Pages\CreateGiftCard;
use App\Filament\Resources\GiftCards\Pages\EditGiftCard;
use App\Filament\Resources\GiftCards\Pages\ListGiftCards;
use App\Filament\Support\Money;
use App\Mail\GiftCardIssued;
use App\Models\GiftCard;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

class GiftCardResource extends Resource
{
    protected static ?string $model = GiftCard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'Producten';

    protected static ?string $navigationLabel = 'Cadeaubonnen';

    protected static ?string $modelLabel = 'cadeaubon';

    protected static ?string $pluralModelLabel = 'cadeaubonnen';

    protected static ?string $slug = 'cadeaubonnen';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->columns(2)->schema([
                TextInput::make('code')->label('Code')->default(fn () => GiftCard::generateCode())->required()->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn ($state) => GiftCard::normalize($state)),
                Money::input('initial_value')->label('Waarde')->required()->disabledOn('edit'),
                Money::input('balance')->label('Huidig saldo')->visibleOn('edit')->required(),
                DatePicker::make('expires_at')->label('Geldig tot')->native(false)->placeholder('Geen einddatum'),
                Select::make('customer_id')->label('Klant')->relationship('customer', 'email')->searchable()->preload(),
                Toggle::make('is_active')->label('Actief')->default(true)->inline(false),
                Textarea::make('note')->label('Interne notitie')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Code')->formatStateUsing(fn (GiftCard $record) => $record->maskedCode())->searchable()->copyable()->copyableState(fn (GiftCard $record) => $record->code),
                TextColumn::make('balance')->label('Saldo')->formatStateUsing(fn ($state, GiftCard $record) => money($state).' van '.money($record->initial_value)),
                TextColumn::make('customer.email')->label('Klant')->placeholder('–'),
                TextColumn::make('expires_at')->label('Geldig tot')->date('j M Y')->placeholder('Geen einddatum'),
                TextColumn::make('created_at')->label('Aangemaakt')->date('j M Y')->toggleable(),
                ToggleColumn::make('is_active')->label('Actief'),
            ])
            ->recordActions([
                EditAction::make(),
                self::sendAction(),
            ]);
    }

    public static function sendAction(): Action
    {
        return Action::make('send')->label('Versturen')->icon(Heroicon::OutlinedEnvelope)->color('gray')
            ->fillForm(fn (GiftCard $record) => ['email' => $record->customer?->email])
            ->schema([
                TextInput::make('email')->label('E-mailadres ontvanger')->email()->required(),
                Textarea::make('message')->label('Persoonlijk bericht (optioneel)')->rows(3),
            ])
            ->action(function (GiftCard $record, array $data) {
                Mail::to($data['email'])->send(new GiftCardIssued($record, $data['message'] ?? null));
                Notification::make()->success()->title('Cadeaubon verstuurd naar '.$data['email'])->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGiftCards::route('/'),
            'create' => CreateGiftCard::route('/nieuw'),
            'edit' => EditGiftCard::route('/{record}'),
        ];
    }
}
