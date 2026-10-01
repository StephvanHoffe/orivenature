<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Beveiligingsmelding aan de klant, bijv. bij een gewijzigd e-mailadres of wachtwoord. */
class AccountNotice extends Mailable
{
    public function __construct(public string $heading, public string $body, public ?string $firstName = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(settings('notifications.from_email'), settings('notifications.from_name')),
            subject: $this->heading,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.account-notice');
    }
}
