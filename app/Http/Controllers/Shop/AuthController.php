<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Loyalty;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('shop.account.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $credentials['email'] = strtolower($credentials['email']);
        if (! Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => __('Deze combinatie van e-mailadres en wachtwoord klopt niet.')]);
        }
        $request->session()->regenerate();
        Auth::guard('customer')->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('account.dashboard'));
    }

    public function showRegister()
    {
        return view('shop.account.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', PasswordRule::min(8)],
            'accepts_marketing' => ['nullable', 'boolean'],
        ]);
        $email = strtolower($data['email']);
        $customer = Customer::where('email', $email)->first();
        if ($customer?->hasAccount()) {
            throw ValidationException::withMessages(['email' => __('Er bestaat al een account met dit e-mailadres. Log in of vraag een nieuw wachtwoord aan.')]);
        }
        // Eerder als gast besteld: eerst via e-mail bevestigen dat het adres van jou is,
        // anders kan iemand anders met dit e-mailadres de bestellingen inzien
        if ($customer) {
            Password::broker('customers')->sendResetLink(['email' => $email]);

            return redirect()->route('account.login')->with('status', __('Je hebt al eens bij ons besteld. We hebben je een e-mail gestuurd met een link om je account te activeren; je bestellingen en punten staan dan meteen klaar.'));
        }
        $customer = new Customer(['email' => $email]);
        $customer->fill(['first_name' => $data['first_name'], 'last_name' => $data['last_name'] ?? null]);
        $customer->password = $data['password'];
        if (! empty($data['accepts_marketing'])) {
            $customer->accepts_marketing = true;
            $customer->marketing_consent_at = now();
        }
        $customer->save();
        Loyalty::signupBonus($customer);
        Auth::guard('customer')->login($customer, true);
        $request->session()->regenerate();

        return redirect()->intended(route('account.dashboard'))->with('status', __('Welkom! Je account is aangemaakt.'));
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function showRecover()
    {
        return view('shop.account.recover');
    }

    public function recover(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        $customer = Customer::where('email', strtolower($request->input('email')))->first();
        if ($customer) {
            Password::broker('customers')->sendResetLink(['email' => $customer->email]);
        }

        // Altijd dezelfde melding, zodat niet te achterhalen is welke e-mailadressen een account hebben
        return back()->with('status', __('Als er een account bij dit e-mailadres hoort, ontvang je binnen enkele minuten een e-mail met een link.'));
    }

    public function showReset(Request $request, string $token)
    {
        return view('shop.account.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);
        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password) {
                $activated = ! $customer->hasAccount();
                $customer->forceFill(['password' => $password])->save();
                event(new PasswordReset($customer));
                if ($activated) {
                    Loyalty::signupBonus($customer);
                }
                Auth::guard('customer')->login($customer);
            }
        );
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __('Deze link is verlopen of ongeldig. Vraag een nieuwe aan.')]);
        }

        return redirect()->route('account.dashboard')->with('status', __('Je wachtwoord is gewijzigd.'));
    }
}
