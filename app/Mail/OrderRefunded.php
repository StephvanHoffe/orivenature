<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Refund;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderRefunded extends Mailable
{
    public function __construct(public Order $order, public Refund $refund) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(settings('notifications.from_email'), settings('notifications.from_name')),
            subject: __('Terugbetaling voor bestelling :number', ['number' => $this->order->name]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-refunded');
    }
}
