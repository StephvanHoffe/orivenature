<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Mail\ContactReceived;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        // Spamval: dit veld is onzichtbaar voor mensen
        if (filled($request->input('website'))) {
            return back()->with('status', __('Bedankt voor je bericht! We nemen zo snel mogelijk contact met je op.'));
        }
        $type = $request->input('type') === 'wholesale' ? 'wholesale' : 'contact';
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => [$type === 'wholesale' ? 'required' : 'nullable', 'string', 'max:150'],
            'message' => [$type === 'contact' ? 'required' : 'nullable', 'string', 'max:5000'],
            'kvk' => ['nullable', 'string', 'max:30'],
            'website_url' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
        ], [], ['name' => 'naam', 'message' => 'bericht', 'company' => 'bedrijfsnaam', 'phone' => 'telefoonnummer']);

        $message = ContactMessage::create([
            'type' => $type,
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'message' => $data['message'] ?? null,
            'data' => array_filter(['kvk' => $data['kvk'] ?? null, 'website' => $data['website_url'] ?? null, 'plaats' => $data['city'] ?? null]) ?: null,
            'ip' => $request->ip(),
        ]);

        try {
            Mail::to(settings('store.email'))->send(new ContactReceived($message));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('status', $type === 'wholesale'
            ? __('Bedankt voor je aanvraag! We bekijken je gegevens en nemen snel contact met je op.')
            : __('Bedankt voor je bericht! We nemen zo snel mogelijk contact met je op.'));
    }
}
