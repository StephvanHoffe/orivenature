<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Mail\AccountNotice;
use App\Models\Customer;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AccountProfileController extends Controller
{
    private function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }

    private function checkPassword(Request $request, string $field = 'current_password'): void
    {
        if (! Hash::check((string) $request->input($field), (string) $this->customer()->password)) {
            throw ValidationException::withMessages([$field => __('Je huidige wachtwoord klopt niet.')])->errorBag($request->input('_form', 'default'));
        }
    }

    private function notify(Customer $customer, string $to, string $heading, string $body): void
    {
        try {
            Mail::to($to)->send(new AccountNotice($heading, $body, $customer->first_name));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function edit()
    {
        $customer = $this->customer();
        $subscribed = $customer->accepts_marketing
            || NewsletterSubscriber::where('email', $customer->email)->whereNull('unsubscribed_at')->exists();

        return view('shop.account.profile', compact('customer', 'subscribed'));
    }

    public function updateDetails(Request $request)
    {
        $data = $request->validateWithBag('details', [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
        ], [], ['first_name' => 'voornaam', 'last_name' => 'achternaam', 'phone' => 'telefoonnummer']);
        $this->customer()->update($data);

        return redirect()->route('account.profile')->with('status', __('Je gegevens zijn opgeslagen.'));
    }

    public function updateEmail(Request $request)
    {
        $customer = $this->customer();
        $request->merge(['email' => strtolower(trim((string) $request->input('email'))), '_form' => 'email']);
        $data = $request->validateWithBag('email', [
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('customers', 'email')->ignore($customer->id)],
            'current_password' => ['required', 'string'],
        ], ['email.unique' => __('Dit e-mailadres is al bij ons bekend. Neem contact met ons op als je je accounts wilt samenvoegen.')], ['email' => 'e-mailadres', 'current_password' => 'huidig wachtwoord']);
        $this->checkPassword($request);
        $old = $customer->email;
        if ($old === $data['email']) {
            return redirect()->route('account.profile');
        }
        $customer->forceFill(['email' => $data['email']])->save();
        NewsletterSubscriber::where('email', $old)->update(['email' => $data['email']]);
        $this->notify($customer, $old, __('Je e-mailadres is gewijzigd'), __('Het e-mailadres van je account is gewijzigd naar :email. Je logt voortaan met dit adres in.', ['email' => $data['email']]));

        return redirect()->route('account.profile')->with('status', __('Je e-mailadres is gewijzigd. Je logt voortaan in met :email.', ['email' => $data['email']]));
    }

    public function updatePassword(Request $request)
    {
        $request->merge(['_form' => 'password']);
        $request->validateWithBag('password', [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], ['current_password' => 'huidig wachtwoord', 'password' => 'nieuw wachtwoord']);
        $this->checkPassword($request);
        $customer = $this->customer();
        $customer->forceFill(['password' => $request->input('password')])->save();
        $request->session()->regenerate();
        $this->notify($customer, $customer->email, __('Je wachtwoord is gewijzigd'), __('Het wachtwoord van je account is zojuist gewijzigd.'));

        return redirect()->route('account.profile')->with('status', __('Je wachtwoord is gewijzigd.'));
    }

    public function updateMarketing(Request $request)
    {
        $customer = $this->customer();
        $on = $request->boolean('accepts_marketing');
        $customer->forceFill(['accepts_marketing' => $on, 'marketing_consent_at' => $on ? ($customer->marketing_consent_at ?? now()) : null])->save();
        $subscriber = NewsletterSubscriber::firstOrNew(['email' => $customer->email]);
        if ($on) {
            $subscriber->fill(['customer_id' => $customer->id, 'first_name' => $customer->first_name, 'source' => $subscriber->source ?? 'account', 'subscribed_at' => $subscriber->subscribed_at ?? now(), 'unsubscribed_at' => null])->save();
        } elseif ($subscriber->exists) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        return redirect()->route('account.profile')->with('status', $on ? __('Je ontvangt voortaan onze nieuwsbrief.') : __('Je bent afgemeld voor de nieuwsbrief.'));
    }

    /** Alle gegevens die we van de klant hebben (AVG: recht op inzage). */
    public function export()
    {
        $customer = $this->customer()->fresh(['addresses', 'orders.items', 'loyaltyTransactions', 'creditTransactions']);
        $data = [
            'gegevens' => $customer->only(['first_name', 'last_name', 'email', 'phone', 'accepts_marketing', 'created_at']),
            'adressen' => $customer->addresses->map->toOrderAddress()->all(),
            'bestellingen' => $customer->orders->map(fn ($o) => [
                'nummer' => $o->name, 'datum' => $o->placed_at?->toDateTimeString(), 'status' => $o->customerStatus()[0],
                'totaal' => money($o->grandTotal()), 'bezorgadres' => $o->shipping_address, 'factuuradres' => $o->billing_address,
                'artikelen' => $o->items->map(fn ($i) => ['product' => $i->title, 'variant' => $i->variant_title, 'aantal' => $i->quantity, 'prijs' => money($i->price)])->all(),
            ])->all(),
            'spaarpunten' => ['saldo' => $customer->points_balance, 'historie' => $customer->loyaltyTransactions->map(fn ($t) => ['datum' => $t->created_at->toDateTimeString(), 'punten' => $t->points, 'omschrijving' => $t->description])->all()],
            'tegoed' => ['saldo' => money($customer->credit_balance), 'historie' => $customer->creditTransactions->map(fn ($t) => ['datum' => $t->created_at->toDateTimeString(), 'bedrag' => money($t->amount), 'omschrijving' => $t->description])->all()],
        ];

        return response()->streamDownload(
            fn () => print json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'mijn-gegevens-'.str(settings('store.name'))->slug().'.json',
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    /** Account verwijderen. Bestellingen blijven bewaard voor de boekhouding (wettelijke bewaarplicht). */
    public function destroy(Request $request)
    {
        $request->merge(['_form' => 'delete']);
        $request->validateWithBag('delete', ['current_password' => ['required', 'string'], 'confirm' => ['accepted']], [],
            ['current_password' => 'wachtwoord', 'confirm' => 'bevestiging']);
        $this->checkPassword($request);
        $customer = $this->customer();
        foreach ($customer->orders as $order) {
            $order->log('customer', __('Klantaccount verwijderd op verzoek van de klant.'));
        }
        NewsletterSubscriber::where('email', $customer->email)->update(['unsubscribed_at' => now(), 'customer_id' => null]);
        $email = $customer->email;
        $firstName = $customer->first_name;
        Auth::guard('customer')->logout();
        $customer->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        try {
            Mail::to($email)->send(new AccountNotice(__('Je account is verwijderd'), __('Je account, adressen, spaarpunten en tegoed zijn verwijderd. Gegevens van eerdere bestellingen bewaren we alleen zolang de wet dat voorschrijft voor onze boekhouding.'), $firstName));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('home')->with('status', __('Je account is verwijderd.'));
    }
}
