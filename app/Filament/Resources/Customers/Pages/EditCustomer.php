<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Support\Money;
use App\Models\Customer;
use App\Services\Loyalty;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Password;

/** @property Customer $record */
class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->record->name ?: $this->record->email;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['new_password'] ?? null)) {
            $data['password'] = $data['new_password'];
        }
        unset($data['new_password']);
        if (! empty($data['accepts_marketing']) && ! $this->record->accepts_marketing) {
            $data['marketing_consent_at'] = now();
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        $customer = $this->record;

        return [
            Action::make('points')->label('Punten aanpassen')->icon(Heroicon::OutlinedSparkles)->color('gray')
                ->visible(fn () => (bool) settings('loyalty.enabled'))
                ->modalDescription(fn () => 'Huidig saldo: '.$customer->points_balance.' punten.')
                ->schema([
                    ToggleButtons::make('direction')->label('Actie')->options(['add' => 'Toevoegen', 'remove' => 'Afhalen'])->default('add')->inline()->required(),
                    TextInput::make('points')->label('Aantal punten')->integer()->minValue(1)->required(),
                    TextInput::make('reason')->label('Omschrijving')->placeholder('bijv. compensatie, actie, cadeau')->required(),
                ])
                ->action(function (array $data) use ($customer) {
                    $points = (int) $data['points'] * ($data['direction'] === 'remove' ? -1 : 1);
                    Loyalty::addPoints($customer, $points, 'adjust', $data['reason'], null, auth()->id());
                    Notification::make()->success()->title('Saldo bijgewerkt: '.$customer->fresh()->points_balance.' punten')->send();
                    $this->refreshFormData(['points_balance']);
                }),
            Action::make('credit')->label('Tegoed aanpassen')->icon(Heroicon::OutlinedWallet)->color('gray')
                ->modalDescription(fn () => 'Huidig tegoed: '.money($customer->credit_balance).'.')
                ->schema([
                    ToggleButtons::make('direction')->label('Actie')->options(['add' => 'Toevoegen', 'remove' => 'Afhalen'])->default('add')->inline()->required(),
                    Money::input('amount')->label('Bedrag')->required(),
                    TextInput::make('reason')->label('Omschrijving')->required(),
                ])
                ->action(function (array $data) use ($customer) {
                    $amount = (int) $data['amount'] * ($data['direction'] === 'remove' ? -1 : 1);
                    Loyalty::addCredit($customer, $amount, 'adjust', $data['reason'], null, auth()->id());
                    Notification::make()->success()->title('Tegoed bijgewerkt: '.money($customer->fresh()->credit_balance))->send();
                }),
            ActionGroup::make([
                Action::make('reset')->label('Wachtwoordlink sturen')->icon(Heroicon::OutlinedKey)
                    ->requiresConfirmation()->modalDescription(fn () => 'De klant ontvangt op '.$customer->email.' een link om een (nieuw) wachtwoord in te stellen. Handig om gasten uit te nodigen een account te maken.')
                    ->action(function () use ($customer) {
                        $status = Password::broker('customers')->sendResetLink(['email' => $customer->email]);
                        Notification::make()->{$status === Password::RESET_LINK_SENT ? 'success' : 'warning'}()->title(__($status))->send();
                    }),
                Action::make('mail')->label('E-mail sturen')->icon(Heroicon::OutlinedEnvelope)->url(fn () => 'mailto:'.$customer->email),
                DeleteAction::make(),
            ])->label('Meer')->button()->color('gray')->icon(Heroicon::OutlinedEllipsisVertical),
        ];
    }
}
