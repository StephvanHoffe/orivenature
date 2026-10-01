<?php

namespace App\Mail;

use App\Models\Fulfillment;
use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderShipped extends Mailable
{
    public function __construct(public Order $order, public Fulfillment $fulfillment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(settings('notifications.from_email'), settings('notifications.from_name')),
            subject: __('Je bestelling :number is onderweg', ['number' => $this->order->name]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-shipped');
    }
}
