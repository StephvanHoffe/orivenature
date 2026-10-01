<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterWelcome;
use App\Models\Customer;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'source' => ['nullable', 'string', 'max:50'],
        ]);
        $email = strtolower($data['email']);
        $subscriber = NewsletterSubscriber::firstOrNew(['email' => $email]);
        $isNew = ! $subscriber->exists || $subscriber->unsubscribed_at !== null;
        $customer = Customer::where('email', $email)->first();
        $subscriber->fill([
            'source' => $subscriber->source ?? ($data['source'] ?? 'website'),
            'customer_id' => $customer?->id,
            'subscribed_at' => $subscriber->subscribed_at ?? now(),
            'unsubscribed_at' => null,
        ])->save();
        $customer?->forceFill(['accepts_marketing' => true, 'marketing_consent_at' => now()])->save();

        if ($isNew && settings('newsletter.welcome_enabled')) {
            try {
                Mail::to($email)->send(new NewsletterWelcome(settings('newsletter.discount_code') ?: null, $customer?->first_name));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $message = settings('newsletter.discount_code')
            ? __('Bedankt! Je kortingscode is onderweg naar je inbox.')
            : __('Bedankt voor je aanmelding!');

        return $request->expectsJson()
            ? response()->json(['message' => $message])
            : back()->with('newsletter', $message);
    }
}
