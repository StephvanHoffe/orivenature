<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Mail\ContactReceived;
use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\Order;
use App\Services\Cart;
use App\Services\Documents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AccountOrderController extends Controller
{
    private function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }

    private function find(string $number): Order
    {
        return $this->customer()->orders()->where('number', $number)
            ->with(['items.variant.product', 'fulfillments', 'payments', 'refunds', 'shippingRate'])->firstOrFail();
    }

    public function index(Request $request)
    {
        $filter = in_array($request->query('filter'), ['lopend', 'afgerond'], true) ? $request->query('filter') : null;
        $query = $this->customer()->orders()->with(['items', 'fulfillments', 'payments', 'refunds', 'shippingRate'])->latest('placed_at');
        if ($filter === 'lopend') {
            $query->where('status', '!=', 'cancelled')->where('financial_status', '!=', 'refunded')->where('fulfillment_status', '!=', 'fulfilled');
        } elseif ($filter === 'afgerond') {
            $query->where(fn ($q) => $q->where('status', 'cancelled')->orWhere('financial_status', 'refunded')->orWhere('fulfillment_status', 'fulfilled'));
        }

        return view('shop.account.orders', ['orders' => $query->paginate(10)->withQueryString(), 'filter' => $filter, 'customer' => $this->customer()]);
    }

    public function show(string $number)
    {
        $order = $this->find($number);

        return view('shop.account.order', ['order' => $order, 'customer' => $this->customer()]);
    }

    public function invoice(string $number)
    {
        $order = $this->find($number);
        abort_unless($order->isPaid(), 404);

        return Documents::invoice($order)->download('factuur-'.$order->number.'.pdf');
    }

    /** Zet alle artikelen van een eerdere bestelling (weer) in de winkelwagen. */
    public function reorder(string $number, Cart $cart)
    {
        $order = $this->find($number);
        $added = 0;
        $skipped = [];
        foreach ($order->items as $item) {
            if (! $item->variant || $item->variant->product?->status !== 'active') {
                $skipped[] = $item->title;

                continue;
            }
            try {
                $cart->add($item->variant->id, $item->quantity);
                $added++;
            } catch (ValidationException) {
                $skipped[] = $item->title;
            }
        }
        $message = $added
            ? __('De artikelen van bestelling :number staan in je winkelwagen.', ['number' => $order->name])
            : __('Deze artikelen zijn helaas niet meer leverbaar.');
        if ($added && $skipped) {
            $message .= ' '.__('Niet meer leverbaar: :items.', ['items' => implode(', ', array_unique($skipped))]);
        }

        return redirect()->route('cart')->with('status', $message);
    }

    /** Terug naar de openstaande betaling (bijv. als de klant het betaalscherm had gesloten). */
    public function pay(string $number)
    {
        $order = $this->find($number);
        $url = $order->checkoutUrl();
        if (! $url) {
            return redirect()->route('account.orders.show', $order->number)
                ->with('status', __('Deze betaling kan niet meer worden afgerond. Plaats de bestelling opnieuw via "Opnieuw bestellen".'));
        }

        return redirect()->away($url);
    }

    public function question(Request $request, string $number)
    {
        $order = $this->find($number);
        $data = $request->validate([
            'subject' => ['required', 'string', 'in:vraag,beschadigd,ontbreekt,retour,anders'],
            'message' => ['required', 'string', 'min:5', 'max:3000'],
        ], [], ['message' => 'bericht', 'subject' => 'onderwerp']);
        $customer = $this->customer();
        $message = ContactMessage::create([
            'type' => 'order',
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'message' => $data['message'],
            'data' => ['bestelling' => $order->name, 'onderwerp' => self::SUBJECTS[$data['subject']], 'order_id' => $order->id],
            'ip' => $request->ip(),
        ]);
        $order->log('customer_message', __('Vraag van de klant (:subject): :message', ['subject' => self::SUBJECTS[$data['subject']], 'message' => $data['message']]), [], null);
        try {
            Mail::to(settings('store.email'))->send(new ContactReceived($message));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('account.orders.show', $order->number)->with('status', __('Bedankt! We hebben je bericht ontvangen en reageren zo snel mogelijk per e-mail.'));
    }

    public const SUBJECTS = [
        'vraag' => 'Vraag over mijn bestelling',
        'beschadigd' => 'Product beschadigd',
        'ontbreekt' => 'Er ontbreekt iets',
        'retour' => 'Retourneren',
        'anders' => 'Iets anders',
    ];
}
