<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Services\Loyalty;
use App\Support\Countries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    private function customer()
    {
        return Auth::guard('customer')->user();
    }

    public function dashboard()
    {
        $customer = $this->customer()->load(['addresses']);
        $orders = $customer->orders()->with('items')->paginate(10);
        $loyalty = $customer->loyaltyTransactions()->take(10)->get();
        $credit = $customer->creditTransactions()->take(10)->get();

        return view('shop.account.dashboard', compact('customer', 'orders', 'loyalty', 'credit'));
    }

    public function order(string $number)
    {
        $order = $this->customer()->orders()->where('number', $number)->with(['items', 'fulfillments'])->firstOrFail();

        return view('shop.order-status', ['order' => $order, 'justPlaced' => false, 'inAccount' => true]);
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
        ]);
    }

    public function storeAddress(Request $request)
    {
        $data = $this->validateAddress($request);
        $customer = $this->customer();
        $address = $customer->addresses()->create($data + ['is_default' => false]);
        $this->setDefault($address, ($data['is_default'] ?? false) || $customer->addresses()->count() === 1);

        return back()->with('status', __('Adres opgeslagen.'));
    }

    public function updateAddress(Request $request, CustomerAddress $address)
    {
        abort_unless($address->customer_id === $this->customer()->id, 404);
        $data = $this->validateAddress($request);
        $address->update(collect($data)->except('is_default')->all());
        $this->setDefault($address, (bool) ($data['is_default'] ?? false));

        return back()->with('status', __('Adres bijgewerkt.'));
    }

    public function deleteAddress(CustomerAddress $address)
    {
        abort_unless($address->customer_id === $this->customer()->id, 404);
        $address->delete();

        return back()->with('status', __('Adres verwijderd.'));
    }

    private function setDefault(CustomerAddress $address, bool $default): void
    {
        if ($default) {
            CustomerAddress::where('customer_id', $address->customer_id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        }
    }
}
