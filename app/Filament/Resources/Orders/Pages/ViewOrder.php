<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\Money;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Loyalty;
use App\Services\OrderService;
use App\Support\Countries;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Mail;

/** @property Order $record */
class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Bestelling '.$this->record->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->record->placed_at?->translatedFormat('j F Y \o\m H:i').' · '.(Order::FINANCIAL_STATUSES[$this->record->financial_status] ?? '').' · '.(Order::FULFILLMENT_STATUSES[$this->record->fulfillment_status] ?? '')
            .($this->record->status === 'cancelled' ? ' · Geannuleerd' : '');
    }

    private function run(callable $callback, string $success): void
    {
        try {
            $callback(app(OrderService::class));
            Notification::make()->success()->title($success)->send();
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->danger()->title('Dat is niet gelukt')->body($e->getMessage())->persistent()->send();
        }
        $this->record->refresh();
    }

    protected function getHeaderActions(): array
    {
        $order = $this->record;

        return [
            Action::make('fulfill')->label('Verzenden')->icon(Heroicon::OutlinedTruck)
                ->visible(fn () => $order->status === 'open' && $order->isPaid() && $order->fulfillment_status !== 'fulfilled')
                ->modalHeading('Bestelling verzenden')
                ->modalDescription('De klant krijgt een e-mail met de track & trace-link.')
                ->schema(fn () => [
                    Fieldset::make('Aantallen')->schema(
                        $order->items->filter(fn (OrderItem $i) => $i->unfulfilledQuantity() > 0)->map(fn (OrderItem $i) => TextInput::make('items.'.$i->id)
                            ->label($i->title.($i->variant_title ? ' – '.$i->variant_title : ''))
                            ->numeric()->minValue(0)->maxValue($i->unfulfilledQuantity())->default($i->unfulfilledQuantity())
                            ->suffix('van '.$i->unfulfilledQuantity()))->values()->all()
                    )->columns(1),
                    Grid::make(2)->schema([
                        Select::make('company')->label('Vervoerder')->options(['PostNL' => 'PostNL', 'DHL' => 'DHL', 'DPD' => 'DPD', 'GLS' => 'GLS', 'UPS' => 'UPS', 'Anders' => 'Anders'])->default('PostNL'),
                        TextInput::make('number')->label('Track & trace-code'),
                    ]),
                    TextInput::make('url')->label('Track & trace-link (optioneel)')->url()->helperText('Laat leeg voor PostNL, DHL, DPD, GLS en UPS: de link wordt automatisch gemaakt.'),
                    Toggle::make('notify')->label('Klant per e-mail informeren')->default(true),
                ])
                ->action(fn (array $data) => $this->run(fn (OrderService $s) => $s->fulfill(
                    $order, array_map('intval', $data['items'] ?? []), $data['company'] ?? null, $data['number'] ?? null, $data['url'] ?? null, (bool) $data['notify'], auth()->id(),
                ), 'Gemarkeerd als verzonden')),

            Action::make('markPaid')->label('Markeer als betaald')->icon(Heroicon::OutlinedBanknotes)->color('gray')
                ->visible(fn () => $order->status === 'open' && ! $order->isPaid())
                ->requiresConfirmation()->modalDescription('Gebruik dit als de klant op een andere manier heeft betaald, bijvoorbeeld per bankoverschrijving.')
                ->action(fn () => $this->run(fn (OrderService $s) => $s->markPaid($order), 'Bestelling staat op betaald')),

            ActionGroup::make([
                Action::make('invoice')->label('Factuur (PDF)')->icon(Heroicon::OutlinedDocumentText)
                    ->url(fn () => route('beheer.invoice', $order), shouldOpenInNewTab: true),
                Action::make('packingSlip')->label('Pakbon (PDF)')->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->url(fn () => route('beheer.packing-slip', $order), shouldOpenInNewTab: true),
                Action::make('statusPage')->label('Bestelstatus bekijken')->icon(Heroicon::OutlinedEye)
                    ->url(fn () => $order->statusUrl(), shouldOpenInNewTab: true),
                Action::make('resend')->label('Bevestiging opnieuw sturen')->icon(Heroicon::OutlinedEnvelope)
                    ->requiresConfirmation()->modalDescription(fn () => 'De orderbevestiging gaat naar '.$order->email.'.')
                    ->action(function () use ($order) {
                        Mail::to($order->email)->send(new OrderConfirmation($order));
                        $order->log('email', 'Orderbevestiging opnieuw verstuurd.', [], auth()->id());
                        Notification::make()->success()->title('Verstuurd')->send();
                    }),
                Action::make('edit')->label('Adres en notities bewerken')->icon(Heroicon::OutlinedPencilSquare)
                    ->fillForm(fn () => [
                        'shipping' => $order->shipping_address,
                        'note' => $order->note,
                        'tags' => $order->tags,
                        'email' => $order->email,
                        'phone' => $order->phone,
                    ])
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('email')->label('E-mailadres')->email()->required(),
                            TextInput::make('phone')->label('Telefoon'),
                        ]),
                        Fieldset::make('Bezorgadres')->schema([
                            TextInput::make('shipping.first_name')->label('Voornaam'),
                            TextInput::make('shipping.last_name')->label('Achternaam'),
                            TextInput::make('shipping.company')->label('Bedrijf'),
                            TextInput::make('shipping.address1')->label('Straat en huisnummer'),
                            TextInput::make('shipping.address2')->label('Toevoeging'),
                            TextInput::make('shipping.zip')->label('Postcode'),
                            TextInput::make('shipping.city')->label('Plaats'),
                            Select::make('shipping.country_code')->label('Land')->options(Countries::LIST),
                        ]),
                        Textarea::make('note')->label('Interne notitie')->rows(3),
                        TagsInput::make('tags')->label('Labels'),
                    ])
                    ->action(function (array $data) use ($order) {
                        $order->forceFill([
                            'shipping_address' => array_merge($order->shipping_address ?? [], $data['shipping'] ?? []),
                            'note' => $data['note'], 'tags' => $data['tags'] ?: null, 'email' => strtolower($data['email']), 'phone' => $data['phone'],
                        ])->save();
                        $order->log('edited', 'Bestelling bewerkt.', [], auth()->id());
                        Notification::make()->success()->title('Opgeslagen')->send();
                    }),
                Action::make('awardPoints')->label('Spaarpunten toekennen')->icon(Heroicon::OutlinedSparkles)
                    ->visible(fn () => settings('loyalty.enabled') && $order->customer_id && ! $order->points_awarded_at && $order->isPaid())
                    ->requiresConfirmation()
                    ->action(fn () => $this->run(fn () => Loyalty::award($order), 'Punten toegekend')),
                Action::make('refund')->label('Terugbetalen')->icon(Heroicon::OutlinedReceiptRefund)->color('warning')
                    ->visible(fn () => $order->refundableAmount() > 0)
                    ->schema(fn () => [
                        Money::input('amount')->label('Bedrag')->required()->default($order->refundableAmount())
                            ->helperText('Maximaal '.money($order->refundableAmount()).'. Het deel betaald met tegoed of cadeaubon gaat terug naar het tegoed van de klant.'),
                        TextInput::make('reason')->label('Reden (optioneel)'),
                        Toggle::make('restock')->label('Artikelen terug op voorraad zetten')->default(false),
                        Toggle::make('notify')->label('Klant per e-mail informeren')->default(true),
                    ])
                    ->modalHeading('Terugbetalen')->modalSubmitActionLabel('Terugbetalen')
                    ->action(function (array $data) use ($order) {
                        $items = $data['restock'] ? $order->items->mapWithKeys(fn (OrderItem $i) => [$i->id => $i->quantity - $i->refunded_quantity])->all() : [];
                        $this->run(fn (OrderService $s) => $s->refund($order, (int) $data['amount'], $data['reason'] ?? null, $items, (bool) $data['notify'], auth()->id()), 'Terugbetaling verwerkt');
                    }),
                Action::make('cancel')->label('Bestelling annuleren')->icon(Heroicon::OutlinedXCircle)->color('danger')
                    ->visible(fn () => $order->status !== 'cancelled')
                    ->schema([
                        TextInput::make('reason')->label('Reden'),
                        Toggle::make('refund')->label('Volledig terugbetalen')->default(true)->visible(fn () => $order->isPaid()),
                        Toggle::make('restock')->label('Voorraad terugzetten')->default(true),
                        Toggle::make('notify')->label('Klant per e-mail informeren')->default(true),
                    ])
                    ->modalHeading('Bestelling annuleren')->modalSubmitActionLabel('Annuleren')
                    ->action(fn (array $data) => $this->run(fn (OrderService $s) => $s->cancel(
                        $order, $data['reason'] ?? null, (bool) ($data['refund'] ?? false), (bool) $data['restock'], (bool) $data['notify'], auth()->id(),
                    ), 'Bestelling geannuleerd')),
            ])->label('Meer')->icon(Heroicon::OutlinedEllipsisVertical)->button()->color('gray'),
        ];
    }
}
