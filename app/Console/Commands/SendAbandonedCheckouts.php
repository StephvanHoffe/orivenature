<?php

namespace App\Console\Commands;

use App\Mail\AbandonedCheckout;
use App\Models\Checkout;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendAbandonedCheckouts extends Command
{
    protected $signature = 'orive:verlaten-winkelwagens';

    protected $description = 'Stuurt een herinnering naar klanten die hun bestelling niet hebben afgerond';

    public function handle(): int
    {
        if (! settings('notifications.abandoned_enabled')) {
            return self::SUCCESS;
        }
        $delay = max(1, (int) settings('notifications.abandoned_delay_hours', 3));
        $sent = 0;
        Checkout::query()
            ->whereNotNull('email')
            ->whereNull('completed_at')
            ->whereNull('reminder_sent_at')
            ->where('updated_at', '<=', now()->subHours($delay))
            ->where('updated_at', '>=', now()->subDays(3))
            ->each(function (Checkout $checkout) use (&$sent) {
                // Niet mailen als de klant inmiddels toch heeft besteld
                $ordered = Order::where('email', $checkout->email)->whereNotNull('paid_at')->where('placed_at', '>=', $checkout->created_at)->exists();
                if (! $ordered && $checkout->cart) {
                    Mail::to($checkout->email)->send(new AbandonedCheckout($checkout));
                    $sent++;
                }
                $checkout->forceFill(['reminder_sent_at' => now()])->save();
            });
        $this->info("{$sent} herinnering(en) verstuurd.");

        return self::SUCCESS;
    }
}
