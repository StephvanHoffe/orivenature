<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactReceived extends Mailable
{
    public function __construct(public ContactMessage $contact) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(settings('notifications.from_email'), settings('notifications.from_name')),
            replyTo: [new Address($this->contact->email, $this->contact->name)],
            subject: (ContactMessage::TYPES[$this->contact->type] ?? 'Bericht').': '.$this->contact->name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contact-received');
    }
}
