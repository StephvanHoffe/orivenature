<?php

namespace App\Filament\Pages\Settings;

use App\Mail\OrderConfirmation;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;

class EmailSettings extends SettingsPage
{
    protected static array $groups = ['notifications', 'newsletter'];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'E-mails';

    protected static ?string $title = 'E-mails aan klanten';

    protected static ?string $slug = 'instellingen/e-mails';

    protected static ?int $navigationSort = 5;

    protected function fields(): array
    {
        return [
            Section::make('Afzender')->columns(2)->schema([
                TextInput::make('notifications.from_name')->label('Naam afzender')->required(),
                TextInput::make('notifications.from_email')->label('E-mailadres afzender')->email()->required()
                    ->helperText('Gebruik een adres van je eigen domein, anders komen mails in de spam.'),
                TextInput::make('notifications.bcc_orders')->label('Kopie van elke bestelling naar')->email()->placeholder('bijv. orders@orivenature.com'),
            ]),
            Section::make('Teksten')->schema([
                Textarea::make('notifications.confirmation_intro')->label('Orderbevestiging')->rows(3),
                Textarea::make('notifications.shipped_intro')->label('Verzendbevestiging')->rows(3),
            ]),
            Section::make('Verlaten winkelwagen')->columns(2)->schema([
                Toggle::make('notifications.abandoned_enabled')->label('Automatisch een herinnering sturen'),
                TextInput::make('notifications.abandoned_delay_hours')->label('Na')->integer()->minValue(1)->suffix('uur'),
                Textarea::make('notifications.abandoned_intro')->label('Tekst')->rows(3)->columnSpanFull(),
            ]),
            Section::make('Nieuwsbrief')->columns(2)->schema([
                Toggle::make('newsletter.welcome_enabled')->label('Welkomstmail sturen bij aanmelding'),
                TextInput::make('newsletter.discount_code')->label('Kortingscode in de welkomstmail')->helperText('Maak de code aan bij Marketing > Kortingen.'),
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')->label('Testmail sturen')->icon(Heroicon::OutlinedPaperAirplane)->color('gray')
                ->schema([TextInput::make('email')->label('Naar')->email()->required()->default(fn () => auth()->user()->email)])
                ->action(function (array $data) {
                    $order = Order::latest('id')->first();
                    try {
                        if ($order) {
                            Mail::to($data['email'])->send(new OrderConfirmation($order));
                        } else {
                            Mail::raw('Dit is een testmail van '.settings('store.name').'. De e-mailinstellingen werken.', fn ($m) => $m->to($data['email'])->subject('Testmail'));
                        }
                        Notification::make()->success()->title('Testmail verstuurd')->body($order ? 'Voorbeeld: de orderbevestiging van '.$order->name.'.' : null)->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Versturen mislukt')->body($e->getMessage().' Controleer de MAIL_-instellingen in het .env-bestand.')->persistent()->send();
                    }
                }),
        ];
    }
}
