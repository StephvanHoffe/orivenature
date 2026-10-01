<?php

namespace App\Mail;

use App\Models\GiftCard;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class GiftCardIssued extends Mailable
{
    public function __construct(public GiftCard $giftCard, public ?string $message = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(settings('notifications.from_email'), settings('notifications.from_name')),
            subject: __('Je cadeaubon van :store', ['store' => settings('store.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.gift-card');
    }
}
