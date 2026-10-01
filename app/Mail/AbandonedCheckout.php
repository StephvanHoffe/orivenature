<?php

namespace App\Mail;

use App\Models\Checkout;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AbandonedCheckout extends Mailable
{
    public function __construct(public Checkout $checkout) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(settings('notifications.from_email'), settings('notifications.from_name')),
            subject: __('Je winkelwagen staat nog voor je klaar'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.abandoned-checkout');
    }
}
