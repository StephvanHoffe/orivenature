<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ShippingZone;
use App\Services\Analytics;
use App\Services\Cart;
use App\Services\Loyalty;
use App\Services\OrderService;
use App\Services\Payments\Payments;
use App\Services\Pricing;
use App\Services\PricingResult;
use App\Support\Countries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(private Cart $cart) {}

    private function customer(): ?Customer
    {
        return Auth::guard('customer')->user();
    }

    private function pricing(?string $email = null): PricingResult
    {
        return Pricing::calculate(
            $this->cart->lines(),
            $this->cart->meta('discount_code'),
            $this->cart->meta('country_code') ?? $this->customer()?->defaultAddress()?->country_code ?? 'NL',
            $this->cart->meta('shipping_rate_id'),
            $this->cart->meta('gift_card'),
            $this->customer(),
            (bool) $this->cart->meta('use_credit'),
            $email ?? $this->customer()?->email ?? session('checkout_email'),
        );
    }

    private function countries(): array
    {
        $codes = ShippingZone::allCountries() ?: ['NL'];

        return collect($codes)->mapWithKeys(fn ($c) => [$c => Countries::name($c)])->sort()->sortBy(fn ($n, $c) => $c === 'NL' ? 0 : ($c === 'BE' ? 1 : 2))->all();
    }

    public function show()
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart');
        }
        if (! $this->cart->meta('checkout_started')) {
            Analytics::event('begin_checkout', null, $this->cart->subtotal());
            $this->cart->setMeta('checkout_started', true);
        }
        if ($this->customer() && $this->cart->meta('use_credit') === null) {
            $this->cart->setMeta('use_credit', true);
        }

        return view('shop.checkout', [
            'pricing' => $this->pricing(),
            'countries' => $this->countries(),
            'customer' => $this->customer()?->load('addresses'),
            'email' => old('email', $this->customer()?->email ?? session('checkout_email')),
            'cart' => $this->cart,
            'paymentsReady' => Payments::isConfigured(),
        ]);
    }

    /** Herberekent de totalen zodra land, verzendmethode, kortingscode, cadeaubon of tegoed verandert. */
    public function quote(Request $request)
    {
        $data = $request->validate([
            'email' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'shipping_rate_id' => ['nullable', 'integer'],
            'discount_code' => ['nullable', 'string', 'max:60'],
            'gift_card' => ['nullable', 'string', 'max:60'],
            'use_credit' => ['nullable', 'boolean'],
        ]);
        foreach (['country_code', 'shipping_rate_id', 'gift_card'] as $key) {
            if ($request->has($key)) {
                $this->cart->setMeta($key, $data[$key] ?: null);
            }
        }
        if ($request->has('discount_code')) {
            $this->cart->setMeta('discount_code', $data['discount_code'] ? strtoupper(trim($data['discount_code'])) : null);
        }
        if ($request->has('use_credit')) {
            $this->cart->setMeta('use_credit', (bool) $data['use_credit']);
        }
        $email = filter_var($data['email'] ?? null, FILTER_VALIDATE_EMAIL) ?: null;
        if ($email) {
            session(['checkout_email' => $email]);
            $this->rememberCheckout($email);
        }

        $pricing = $this->pricing($email);
        // Ongeldige codes niet bewaren
        if ($pricing->discountError) {
            $this->cart->setMeta('discount_code', null);
        }
        if ($pricing->giftCardError) {
            $this->cart->setMeta('gift_card', null);
        }

        return response()->json([
            'pricing' => $pricing->toArray(),
            'summary' => view('shop.partials.checkout-summary', ['pricing' => $pricing, 'cart' => $this->cart])->render(),
        ]);
    }

    /** Bewaart de winkelwagen voor de herinneringsmail bij een verlaten checkout. */
    private function rememberCheckout(string $email): void
    {
        $token = session('checkout_token') ?? Str::random(48);
        session(['checkout_token' => $token]);
        Checkout::updateOrCreate(['token' => $token], [
            'email' => $email,
            'customer_id' => $this->customer()?->id,
            'cart' => $this->cart->lines()->map->toArray()->values()->all(),
            'subtotal' => $this->cart->subtotal(),
        ]);
    }

    public function place(Request $request, OrderService $orders)
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart');
        }
        $countries = array_keys($this->countries());
        $requirePhone = (bool) settings('checkout.require_phone');
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'accepts_marketing' => ['nullable', 'boolean'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:150'],
            'address1' => ['required', 'string', 'max:200'],
            'address2' => ['nullable', 'string', 'max:200'],
            'zip' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:100'],
            'country_code' => ['required', Rule::in($countries)],
            'phone' => [$requirePhone ? 'required' : 'nullable', 'string', 'max:40'],
            'shipping_rate_id' => ['required', 'integer'],
            'billing_same' => ['nullable', 'boolean'],
            'billing.first_name' => ['exclude_if:billing_same,1', 'required', 'string', 'max:100'],
            'billing.last_name' => ['exclude_if:billing_same,1', 'required', 'string', 'max:100'],
            'billing.company' => ['exclude_if:billing_same,1', 'nullable', 'string', 'max:150'],
            'billing.address1' => ['exclude_if:billing_same,1', 'required', 'string', 'max:200'],
            'billing.zip' => ['exclude_if:billing_same,1', 'required', 'string', 'max:20'],
            'billing.city' => ['exclude_if:billing_same,1', 'required', 'string', 'max:100'],
            'billing.country_code' => ['exclude_if:billing_same,1', 'required', 'string', 'size:2'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
            'save_address' => ['nullable', 'boolean'],
        ], [], [
            'first_name' => 'voornaam', 'last_name' => 'achternaam', 'address1' => 'adres', 'zip' => 'postcode',
            'city' => 'plaats', 'country_code' => 'land', 'phone' => 'telefoonnummer', 'shipping_rate_id' => 'verzendmethode',
        ]);

        $this->cart->setMeta('country_code', $data['country_code']);
        $this->cart->setMeta('shipping_rate_id', (int) $data['shipping_rate_id']);
        $customer = $this->resolveCustomer($data);
        $pricing = Pricing::calculate(
            $this->cart->lines(), $this->cart->meta('discount_code'), $data['country_code'], (int) $data['shipping_rate_id'],
            $this->cart->meta('gift_card'), $customer, (bool) $this->cart->meta('use_credit') && Auth::guard('customer')->check(), $data['email'],
        );
        if ($pricing->discountError) {
            $this->cart->setMeta('discount_code', null);
            throw ValidationException::withMessages(['discount_code' => $pricing->discountError]);
        }
        if ($pricing->total > 0 && ! Payments::isConfigured()) {
            throw ValidationException::withMessages(['payment' => __('Betalen is op dit moment niet mogelijk. Neem contact met ons op.')]);
        }

        $shipping = collect($data)->only(['first_name', 'last_name', 'company', 'address1', 'address2', 'zip', 'city', 'country_code', 'phone'])->all();
        $billing = ($data['billing_same'] ?? true) ? null : array_merge($data['billing'] ?? [], ['address2' => null]);

        $order = $orders->place($pricing, [
            'email' => strtolower($data['email']),
            'shipping_address' => $shipping,
            'billing_address' => $billing,
            'customer_note' => $data['customer_note'] ?? null,
            'accepts_marketing' => $data['accepts_marketing'] ?? false,
        ], $customer, session('checkout_token'));

        if ($customer && ($data['save_address'] ?? false) && Auth::guard('customer')->check() && ! $customer->addresses()->where('address1', $shipping['address1'])->where('zip', $shipping['zip'])->exists()) {
            $customer->addresses()->create($shipping + ['is_default' => ! $customer->addresses()->exists()]);
        }

        $this->cart->clear();
        session()->forget(['checkout_token']);
        session(['last_order' => $order->token]);

        if ($order->total === 0) {
            return redirect()->route('orders.status', $order->token);
        }

        try {
            return redirect()->away(Payments::gateway()->createPayment($order));
        } catch (\Throwable $e) {
            report($e);
            app(OrderService::class)->markPaymentFailed($order, 'failed');
            $this->cart->restore($order->items->map(fn ($i) => ['variant_id' => $i->variant_id, 'quantity' => $i->quantity])->all());

            return redirect()->route('checkout')->withInput()->withErrors(['payment' => __('De betaling kon niet worden gestart. Probeer het opnieuw of kies een andere betaalmethode.')]);
        }
    }

    /** Koppelt de bestelling aan een klant. Gasten krijgen ook een klantkaart; zo sparen ze toch punten. */
    private function resolveCustomer(array $data): Customer
    {
        if ($customer = $this->customer()) {
            return $customer;
        }
        $email = strtolower($data['email']);
        $customer = Customer::firstOrCreate(['email' => $email], [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
        ]);
        if (! empty($data['accepts_marketing']) && ! $customer->accepts_marketing) {
            $customer->forceFill(['accepts_marketing' => true, 'marketing_consent_at' => now()])->save();
        }
        // Account aanmaken tijdens het afrekenen (alleen voor nieuwe e-mailadressen;
        // bestaande gasten activeren hun account via de link in hun e-mail)
        if (! empty($data['password']) && $customer->wasRecentlyCreated) {
            $customer->forceFill(['password' => $data['password']])->save();
            Loyalty::signupBonus($customer);
            Auth::guard('customer')->login($customer);
        }

        return $customer;
    }

    public function return(string $token)
    {
        $order = Order::where('token', $token)->firstOrFail();
        $payment = $order->payments()->first();
        if ($payment && ! $order->paid_at && $payment->provider === 'mollie') {
            try {
                Payments::gateway('mollie')->refresh($payment);
                $order->refresh();
            } catch (\Throwable $e) {
                report($e);
            }
        }
        if ($order->status === 'cancelled' && ! $order->paid_at) {
            $this->cart->restore($order->items->map(fn ($i) => ['variant_id' => $i->variant_id, 'quantity' => $i->quantity])->all());

            return redirect()->route('checkout')->withErrors(['payment' => __('De betaling is niet gelukt of geannuleerd. Je winkelwagen staat nog klaar, probeer het gerust opnieuw.')]);
        }

        return redirect()->route('orders.status', $order->token);
    }

    public function status(string $token)
    {
        $order = Order::where('token', $token)->with(['items', 'fulfillments', 'customer'])->firstOrFail();
        $justPlaced = session('last_order') === $order->token;

        return view('shop.order-status', compact('order', 'justPlaced'));
    }
}
