<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerResetPassword extends Notification
{
    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from(settings('notifications.from_email'), settings('notifications.from_name'))
            ->subject(__('Stel je wachtwoord opnieuw in'))
            ->view('emails.password-reset', [
                'url' => url('/account/reset/'.$this->token.'?email='.urlencode($notifiable->email)),
                'name' => $notifiable->first_name,
            ]);
    }
}
