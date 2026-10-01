<?php

namespace App\Filament\Pages\Settings;

use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use App\Services\Loyalty;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use UnitEnum;

class LoyaltySettings extends SettingsPage
{
    protected static array $groups = ['loyalty'];

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Spaarprogramma';

    protected static ?string $title = 'Spaarprogramma';

    protected static ?string $slug = 'marketing/spaarprogramma';

    protected static ?int $navigationSort = 2;

    protected function beforeSave(array $data): array
    {
        $data['loyalty']['redeem_value'] = to_cents($data['loyalty']['redeem_value'] ?? 0);

        return $data;
    }

    public function mount(): void
    {
        parent::mount();
        $this->data['loyalty']['redeem_value'] = number_format((int) settings('loyalty.redeem_value') / 100, 2, ',', '');
    }

    protected function fields(): array
    {
        $outstanding = (int) Customer::sum('points_balance');
        $members = Customer::whereNotNull('password')->count();
        $earned30 = (int) LoyaltyTransaction::where('points', '>', 0)->where('created_at', '>=', now()->subDays(30))->sum('points');
        $redeemed30 = (int) -LoyaltyTransaction::where('type', 'redeem')->where('created_at', '>=', now()->subDays(30))->sum('points');

        return [
            Section::make('Overzicht')->schema([
                Text::make(new HtmlString('<div class="ob-help">'
                    ."<strong>{$members}</strong> klanten met een account · <strong>{$outstanding}</strong> openstaande punten (waarde ".money(Loyalty::creditFor($outstanding)).')<br>'
                    ."Laatste 30 dagen: <strong>{$earned30}</strong> punten verdiend, <strong>{$redeemed30}</strong> punten ingewisseld."
                    .'<br>Klanten zien hun punten en wisselen ze in via <a href="'.e(url('/account')).'" target="_blank">Mijn account</a>. Punten en tegoed per klant pas je aan bij Klanten.</div>')),
            ]),
            Section::make('Instellingen')->columns(2)->schema([
                Toggle::make('loyalty.enabled')->label('Spaarprogramma actief')->columnSpanFull(),
                TextInput::make('loyalty.name')->label('Naam van het programma'),
                TextInput::make('loyalty.points_per_euro')->label('Punten per besteed euro')->numeric()->minValue(0)
                    ->helperText('Over het bedrag na korting, zonder verzendkosten.'),
                TextInput::make('loyalty.redeem_points')->label('Aantal punten…')->integer()->minValue(1),
                TextInput::make('loyalty.redeem_value')->label('…is waard aan tegoed')->prefix('€')->rule('regex:/^\d+([.,]\d{1,2})?$/'),
                TextInput::make('loyalty.min_redeem')->label('Minimaal in te wisselen')->integer()->suffix('punten'),
                TextInput::make('loyalty.signup_bonus')->label('Welkomstpunten bij een nieuw account')->integer()->minValue(0),
                Select::make('loyalty.award_on')->label('Punten toekennen zodra de bestelling')->options(['paid' => 'betaald is', 'fulfilled' => 'verzonden is']),
                TextInput::make('loyalty.earn_text')->label('Tekst in winkelwagen en kassa')->helperText('[points] = aantal punten'),
            ]),
        ];
    }
}
