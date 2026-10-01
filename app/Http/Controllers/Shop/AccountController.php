<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Services\Loyalty;
use App\Support\Countries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    private function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }

    public function dashboard()
    {
        $customer = $this->customer()->load('addresses');
        $orders = $customer->orders()->with(['items', 'fulfillments', 'payments', 'refunds', 'shippingRate'])->latest('placed_at')->take(4)->get();
        // De bestelling die nu het meest relevant is: nog onderweg of net verzonden
        $current = $orders->first(fn ($o) => $o->isInProgress())
            ?? $orders->first(fn ($o) => $o->fulfilled_at && $o->fulfilled_at->gt(now()->subDays(7)));
        $stats = [
            'orders' => $customer->orders()->whereNotNull('paid_at')->count(),
            'spent' => (int) $customer->orders()->whereNotNull('paid_at')->sum('total'),
        ];

        return view('shop.account.dashboard', compact('customer', 'orders', 'current', 'stats'));
    }

    public function rewards()
    {
        abort_unless(Loyalty::enabled() || $this->customer()->credit_balance > 0, 404);
        $customer = $this->customer();
        $points = $customer->loyaltyTransactions()->paginate(15, ['*'], 'punten');
        $credit = $customer->creditTransactions()->paginate(15, ['*'], 'tegoed');

        return view('shop.account.rewards', compact('customer', 'points', 'credit'));
    }

    public function redeem(Request $request)
    {
        $data = $request->validate(['points' => ['required', 'integer', 'min:1']]);
        $credit = Loyalty::redeem($this->customer(), (int) $data['points']);

        return back()->with('status', __('Gelukt! :value shoptegoed staat klaar voor je volgende bestelling.', ['value' => money($credit)]));
    }

    public function addresses()
    {
        return view('shop.account.addresses', ['customer' => $this->customer()->load('addresses'), 'countries' => Countries::LIST]);
    }

    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:150'],
            'address1' => ['required', 'string', 'max:200'],
            'address2' => ['nullable', 'string', 'max:200'],
            'zip' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:100'],
            'country_code' => ['required', Rule::in(array_keys(Countries::LIST))],
            'phone' => ['nullable', 'string', 'max:40'],
            'is_default' => ['nullable', 'boolean'],
        ], [], ['first_name' => 'voornaam', 'last_name' => 'achternaam', 'address1' => 'adres', 'zip' => 'postcode', 'city' => 'plaats', 'country_code' => 'land']);
    }

    public function storeAddress(Request $request)
    {
        $data = $this->validateAddress($request);
        $customer = $this->customer();
        $address = $customer->addresses()->create(collect($data)->except('is_default')->all() + ['is_default' => false]);
        $this->setDefault($address, ($data['is_default'] ?? false) || $customer->addresses()->count() === 1);

        return redirect()->route('account.addresses')->with('status', __('Adres opgeslagen.'));
    }

    public function updateAddress(Request $request, CustomerAddress $address)
    {
        abort_unless($address->customer_id === $this->customer()->id, 404);
        $data = $this->validateAddress($request);
        $address->update(collect($data)->except('is_default')->all());
        $this->setDefault($address, (bool) ($data['is_default'] ?? false));

        return redirect()->route('account.addresses')->with('status', __('Adres bijgewerkt.'));
    }

    public function defaultAddress(CustomerAddress $address)
    {
        abort_unless($address->customer_id === $this->customer()->id, 404);
        $this->setDefault($address, true);

        return redirect()->route('account.addresses')->with('status', __('Standaardadres gewijzigd.'));
    }

    public function deleteAddress(CustomerAddress $address)
    {
        abort_unless($address->customer_id === $this->customer()->id, 404);
        $wasDefault = $address->is_default;
        $address->delete();
        if ($wasDefault && ($next = $this->customer()->addresses()->first())) {
            $this->setDefault($next, true);
        }

        return redirect()->route('account.addresses')->with('status', __('Adres verwijderd.'));
    }

    private function setDefault(CustomerAddress $address, bool $default): void
    {
        if ($default) {
            CustomerAddress::where('customer_id', $address->customer_id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        }
    }
}
