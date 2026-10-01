<?php

namespace App\Filament\Resources\Subscribers\Pages;

use App\Filament\Resources\Subscribers\SubscriberResource;
use App\Models\NewsletterSubscriber;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListSubscribers extends ListRecords
{
    protected static string $resource = SubscriberResource::class;

    public function getSubheading(): ?string
    {
        return 'Exporteer de lijst om in Klaviyo, Mailchimp of Laposta te importeren.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label('Actieve abonnees exporteren')->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->action(fn () => SubscriberResource::export(NewsletterSubscriber::whereNull('unsubscribed_at')->cursor())),
            CreateAction::make()->label('Abonnee toevoegen')->mutateDataUsing(fn (array $data) => $data + ['subscribed_at' => now(), 'source' => 'beheer']),
        ];
    }
}
