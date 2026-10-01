<?php

namespace App\Filament\Pages\Settings;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Mollie\Api\Http\Requests\GetEnabledMethodsRequest;
use Mollie\Api\MollieApiClient;

class PaymentSettings extends SettingsPage
{
    protected static array $groups = ['payments'];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'Betalingen';

    protected static ?string $title = 'Betalingen (Mollie)';

    protected static ?string $slug = 'instellingen/betalingen';

    protected static ?int $navigationSort = 2;

    protected function fields(): array
    {
        return [
            Section::make('Mollie')->schema([
                Text::make(new HtmlString('<div class="ob-help"><ol>
                    <li>Log in op <a href="https://my.mollie.com" target="_blank">my.mollie.com</a> en ga naar <strong>Ontwikkelaars &gt; API-sleutels</strong>.</li>
                    <li>Kopieer de <strong>Live API-sleutel</strong> (begint met <code>live_</code>). Om eerst te testen kun je de test-sleutel (<code>test_</code>) gebruiken.</li>
                    <li>Zet bij Mollie de betaalmethodes aan die je wilt aanbieden (iDEAL | Wero, Klarna, PayPal, creditcard, Apple Pay…). De klant kiest bij Mollie.</li>
                </ol></div>')),
                TextInput::make('payments.mollie_key')->label('API-sleutel')->password()->revealable()
                    ->rule('nullable')->rule('regex:/^(live|test)_[A-Za-z0-9]{20,}$/')
                    ->helperText('Webhook (wordt automatisch meegestuurd): '.url('/webhooks/mollie')),
                Toggle::make('payments.test_gateway')->label('Testbetalingen toestaan zonder Mollie-sleutel')
                    ->helperText('Alleen voor testen: bestellingen kunnen dan zonder echte betaling worden afgerond. Zet dit uit zodra de winkel live is.'),
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')->label('Verbinding testen')->icon(Heroicon::OutlinedSignal)->color('gray')
                ->action(function () {
                    $key = (string) settings('payments.mollie_key');
                    if ($key === '') {
                        Notification::make()->warning()->title('Sla eerst een API-sleutel op')->send();

                        return;
                    }
                    try {
                        $client = (new MollieApiClient)->setApiKey($key);
                        $methods = $client->send(new GetEnabledMethodsRequest);
                        $names = collect($methods)->map(fn ($m) => $m->description)->implode(', ');
                        Notification::make()->success()->title('Verbonden met Mollie ('.(str_starts_with($key, 'test_') ? 'testmodus' : 'live').')')
                            ->body($names ? 'Actieve betaalmethodes: '.$names : 'Er staan nog geen betaalmethodes aan in Mollie.')->persistent()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Verbinding mislukt')->body($e->getMessage())->persistent()->send();
                    }
                }),
        ];
    }
}
