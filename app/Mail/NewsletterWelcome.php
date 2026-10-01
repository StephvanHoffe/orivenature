<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewsletterWelcome extends Mailable
{
    public function __construct(public ?string $code, public ?string $firstName = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(settings('notifications.from_email'), settings('notifications.from_name')),
            subject: $this->code ? __('Welkom bij Orivé – hier is je kortingscode') : __('Welkom bij Orivé'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.newsletter-welcome');
    }
}
