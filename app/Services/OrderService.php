<?php

namespace App\Services;

use App\Mail\OrderConfirmation;
use App\Mail\OrderRefunded;
use App\Mail\OrderShipped;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\GiftCard;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Services\Payments\Payments;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class OrderService
{
    /**
     * Maakt de bestelling aan en reserveert voorraad, korting, cadeaubon en tegoed.
     * Mislukt de betaling, dan wordt alles weer vrijgegeven (zie markPaymentFailed).
     */
    public function place(PricingResult $pricing, array $data, ?Customer $customer = null, ?string $checkoutToken = null): Order
    {
        if ($pricing->lines->isEmpty()) {
            throw ValidationException::withMessages(['cart' => __('Je winkelwagen is leeg.')]);
        }
        if (! $pricing->shippingAvailable || ! $pricing->shippingRate) {
            throw ValidationException::withMessages(['country_code' => __('We kunnen (nog) niet naar dit land verzenden.')]);
        }

        $order = DB::transaction(function () use ($pricing, $data, $customer) {
            // Voorraad opnieuw controleren met een lock, zodat er niet dubbel verkocht wordt
            foreach ($pricing->lines as $line) {
                $variant = ProductVariant::lockForUpdate()->find($line->variant->id);
                $max = $variant?->availableQuantity();
                if (! $variant || ($max !== null && $line->quantity > $max)) {
                    throw ValidationException::withMessages(['cart' => __(':title is helaas niet meer (voldoende) op voorraad.', ['title' => $line->title()])]);
                }
            }

            $shipping = $data['shipping_address'];
            $order = Order::create([
                'customer_id' => $customer?->id,
                'email' => $data['email'],
                'phone' => $shipping['phone'] ?? null,
                'subtotal' => $pricing->subtotal,
                'discount_total' => $pricing->discountTotal,
                'shipping_total' => $pricing->shippingTotal,
                'tax_total' => $pricing->taxTotal,
                'gift_card_used' => $pricing->giftCardUsed,
                'credit_used' => $pricing->creditUsed,
                'total' => $pricing->total,
                'discount_code' => $pricing->discount?->code,
                'discount_id' => $pricing->discount?->id,
                'gift_card_id' => $pricing->giftCard?->id,
                'shipping_method' => $pricing->shippingRate->name,
                'shipping_rate_id' => $pricing->shippingRate->id,
                'shipping_address' => $shipping,
                'billing_address' => $data['billing_address'] ?? null,
                'customer_note' => $data['customer_note'] ?? null,
                'accepts_marketing' => (bool) ($data['accepts_marketing'] ?? false),
                'source' => $data['source'] ?? 'web',
                'ip' => request()->ip(),
                'placed_at' => now(),
            ]);

            foreach ($pricing->lines as $line) {
                $order->items()->create([
                    'product_id' => $line->variant->product_id,
                    'variant_id' => $line->variant->id,
                    'title' => $line->title(),
                    'variant_title' => $line->variantTitle(),
                    'sku' => $line->variant->sku,
                    'image' => $line->variant->image?->path ?? $line->product()->featuredImage()?->path,
                    'price' => $line->unitPrice(),
                    'quantity' => $line->quantity,
                    'tax_rate' => $line->taxRate(),
                    'discount_allocated' => $line->discountAllocated,
                    'total' => $line->totalAfterDiscount(),
                ]);
                if ($line->variant->track_stock) {
                    ProductVariant::whereKey($line->variant->id)->decrement('stock', $line->quantity);
                }
            }

            if ($pricing->discount) {
                Discount::whereKey($pricing->discount->id)->increment('usage_count');
            }
            if ($pricing->giftCard && $pricing->giftCardUsed > 0) {
                $card = GiftCard::lockForUpdate()->find($pricing->giftCard->id);
                if ($card->balance < $pricing->giftCardUsed) {
                    throw ValidationException::withMessages(['gift_card' => __('Het saldo van de cadeaubon is gewijzigd. Probeer het opnieuw.')]);
                }
                $card->decrement('balance', $pricing->giftCardUsed);
                $card->transactions()->create(['order_id' => $order->id, 'amount' => -$pricing->giftCardUsed, 'note' => __('Gebruikt bij :number', ['number' => $order->name])]);
            }
            if ($customer && $pricing->creditUsed > 0) {
                $fresh = Customer::lockForUpdate()->find($customer->id);
                if ($fresh->credit_balance < $pricing->creditUsed) {
                    throw ValidationException::withMessages(['credit' => __('Je shoptegoed is gewijzigd. Probeer het opnieuw.')]);
                }
                Loyalty::addCredit($fresh, -$pricing->creditUsed, 'order', __('Gebruikt bij :number', ['number' => $order->name]), $order->id);
            }

            $order->log('placed', __('Bestelling geplaatst via de webshop.'));

            return $order;
        });

        if ($checkoutToken) {
            Checkout::where('token', $checkoutToken)->update(['order_id' => $order->id]);
        }

        // Volledig betaald met cadeaubon/tegoed: geen betaling nodig
        if ($order->total === 0) {
            $this->markPaid($order);
        }

        return $order;
    }

    public function markPaid(Order $order, ?Payment $payment = null): void
    {
        $updated = DB::transaction(function () use ($order) {
            $fresh = Order::lockForUpdate()->find($order->id);
            if ($fresh->paid_at) {
                return false;
            }
            $fresh->forceFill(['financial_status' => 'paid', 'paid_at' => now()])->save();

            return true;
        });
        if (! $updated) {
            return;
        }
        $order->refresh();
        $order->log('paid', $payment
            ? __('Betaling van :amount ontvangen (:method).', ['amount' => money($payment->amount), 'method' => $payment->method ?? $payment->provider])
            : __('Bestelling volledig betaald met cadeaubon of tegoed.'));

        if (settings('loyalty.award_on') === 'paid') {
            Loyalty::award($order);
        }
        if ($order->accepts_marketing) {
            NewsletterSubscriber::firstOrCreate(['email' => $order->email], [
                'first_name' => $order->shipping_address['first_name'] ?? null,
                'source' => 'checkout',
                'customer_id' => $order->customer_id,
                'subscribed_at' => now(),
            ]);
        }
        Checkout::where('order_id', $order->id)->update(['completed_at' => now()]);
        Analytics::event('purchase', null, $order->total);
        Meta::purchase($order);

        $this->send($order, new OrderConfirmation($order));
    }

    /** Betaling mislukt, verlopen of geannuleerd: reserveringen vrijgeven en bestelling annuleren. */
    public function markPaymentFailed(Order $order, string $status): void
    {
        $order->refresh();
        if ($order->paid_at || $order->status === 'cancelled') {
            return;
        }
        $financial = in_array($status, ['failed', 'expired'], true) ? $status : 'cancelled';
        DB::transaction(function () use ($order, $financial) {
            $order->forceFill(['financial_status' => $financial, 'status' => 'cancelled', 'cancelled_at' => now(), 'cancel_reason' => 'payment'])->save();
            $this->release($order, restock: true);
        });
        $order->log('payment_failed', __('Betaling :status. Voorraad, korting en tegoed zijn vrijgegeven.', ['status' => __(Order::FINANCIAL_STATUSES[$financial] ?? $financial)]));
    }

    /** Verzendt (een deel van) de bestelling. $items = [order_item_id => aantal], leeg = alles. */
    public function fulfill(Order $order, array $items = [], ?string $company = null, ?string $number = null, ?string $url = null, bool $notify = true, ?int $userId = null): Fulfillment
    {
        $order->loadMissing('items');
        $quantities = [];
        foreach ($order->items as $item) {
            $qty = $items ? (int) ($items[$item->id] ?? 0) : $item->unfulfilledQuantity();
            $qty = min($qty, $item->unfulfilledQuantity());
            if ($qty > 0) {
                $quantities[$item->id] = $qty;
            }
        }
        if (! $quantities) {
            throw new RuntimeException(__('Er is niets meer te verzenden.'));
        }

        $fulfillment = DB::transaction(function () use ($order, $quantities, $company, $number, $url, $notify, $userId) {
            foreach ($quantities as $itemId => $qty) {
                OrderItem::whereKey($itemId)->increment('fulfilled_quantity', $qty);
            }
            $fulfillment = $order->fulfillments()->create([
                'tracking_company' => $company,
                'tracking_number' => $number,
                'tracking_url' => $url,
                'items' => $quantities,
                'notify_customer' => $notify,
                'shipped_at' => now(),
                'user_id' => $userId,
            ]);
            $order->load('items');
            $open = $order->items->sum(fn (OrderItem $i) => $i->unfulfilledQuantity());
            $order->forceFill([
                'fulfillment_status' => $open > 0 ? 'partially_fulfilled' : 'fulfilled',
                'fulfilled_at' => $open > 0 ? $order->fulfilled_at : now(),
            ])->save();

            return $fulfillment;
        });

        $order->log('fulfilled', $number
            ? __('Verzonden met :company, track & trace :number.', ['company' => $company ?: __('vervoerder'), 'number' => $number])
            : __('Gemarkeerd als verzonden.'), [], $userId);

        if (settings('loyalty.award_on') === 'fulfilled' && $order->fulfillment_status === 'fulfilled') {
            Loyalty::award($order);
        }
        if ($notify) {
            $this->send($order, new OrderShipped($order, $fulfillment));
        }

        return $fulfillment;
    }

    /** Terugbetalen via de betaalprovider; optioneel voorraad terugzetten. */
    public function refund(Order $order, int $amount, ?string $reason = null, array $restockItems = [], bool $notify = true, ?int $userId = null): Refund
    {
        $amount = min($amount, $order->refundableAmount());
        if ($amount <= 0) {
            throw new RuntimeException(__('Er valt niets (meer) terug te betalen.'));
        }

        // Eerst via de betaling, het deel dat met tegoed/cadeaubon is betaald gaat daar weer naartoe
        $paid = $order->payments()->whereIn('status', ['paid'])->first();
        $paidByMoney = $order->total;
        $alreadyRefundedMoney = (int) $order->refunds()->whereNotNull('payment_id')->sum('amount');
        $viaPayment = $paid ? min($amount, max(0, $paidByMoney - $alreadyRefundedMoney)) : 0;
        $rest = $amount - $viaPayment;

        $providerId = null;
        if ($viaPayment > 0) {
            $providerId = Payments::gateway($paid->provider === 'test' ? 'test' : $paid->provider)->refund($paid, $viaPayment, $reason);
        }

        $refund = DB::transaction(function () use ($order, $amount, $viaPayment, $rest, $reason, $restockItems, $paid, $providerId, $userId) {
            $refund = $order->refunds()->create([
                'payment_id' => $viaPayment > 0 ? $paid->id : null,
                'amount' => $amount,
                'reason' => $reason,
                'provider_id' => $providerId,
                'status' => 'refunded',
                'restock' => (bool) $restockItems,
                'items' => $restockItems ?: null,
                'user_id' => $userId,
            ]);
            if ($rest > 0 && $order->customer_id) {
                Loyalty::addCredit($order->customer, $rest, 'refund', __('Terugbetaling :number', ['number' => $order->name]), $order->id, $userId);
            } elseif ($rest > 0 && $order->gift_card_id) {
                $order->giftCard->increment('balance', $rest);
                $order->giftCard->transactions()->create(['order_id' => $order->id, 'amount' => $rest, 'note' => __('Terugbetaling')]);
            }
            foreach ($restockItems as $itemId => $qty) {
                $item = $order->items()->find($itemId);
                if ($item && $qty > 0) {
                    $qty = min($qty, $item->quantity - $item->refunded_quantity);
                    $item->increment('refunded_quantity', $qty);
                    if ($item->variant && $item->variant->track_stock) {
                        $item->variant->increment('stock', $qty);
                    }
                }
            }
            $refunded = $order->refunded_total + $amount;
            $order->forceFill([
                'refunded_total' => $refunded,
                'financial_status' => $refunded >= $order->grandTotal() ? 'refunded' : 'partially_refunded',
            ])->save();

            return $refund;
        });

        $order->log('refunded', __(':amount terugbetaald.', ['amount' => money($amount)]).($reason ? ' '.__('Reden: :reason', ['reason' => $reason]) : ''), [], $userId);
        if ($order->financial_status === 'refunded') {
            Loyalty::revert($order);
        }
        if ($notify) {
            $this->send($order, new OrderRefunded($order, $refund));
        }

        return $refund;
    }

    /** Annuleert een bestelling (door de winkel). Betaalde bestellingen worden volledig terugbetaald. */
    public function cancel(Order $order, ?string $reason = null, bool $refund = true, bool $restock = true, bool $notify = true, ?int $userId = null): void
    {
        if ($order->status === 'cancelled') {
            return;
        }
        if ($order->isPaid() && $refund && $order->refundableAmount() > 0) {
            $items = $restock ? $order->items->mapWithKeys(fn (OrderItem $i) => [$i->id => $i->quantity - $i->refunded_quantity])->all() : [];
            $this->refund($order, $order->refundableAmount(), $reason ?: __('Bestelling geannuleerd'), $items, $notify, $userId);
            $restock = false;
        }
        DB::transaction(function () use ($order, $reason, $restock) {
            $order->forceFill(['status' => 'cancelled', 'cancelled_at' => now(), 'cancel_reason' => $reason])->save();
            if (! $order->isPaid()) {
                $order->forceFill(['financial_status' => 'cancelled'])->save();
                $this->release($order, $restock);
            } elseif ($restock) {
                $this->restock($order);
            }
        });
        Loyalty::revert($order);
        $order->log('cancelled', __('Bestelling geannuleerd.').($reason ? ' '.$reason : ''), [], $userId);
    }

    /** Geeft gereserveerde voorraad, kortingsgebruik, cadeaubon- en tegoedsaldo terug. */
    private function release(Order $order, bool $restock): void
    {
        if ($restock) {
            $this->restock($order);
        }
        if ($order->discount_id) {
            Discount::whereKey($order->discount_id)->where('usage_count', '>', 0)->decrement('usage_count');
        }
        if ($order->gift_card_id && $order->gift_card_used > 0) {
            $order->giftCard->increment('balance', $order->gift_card_used);
            $order->giftCard->transactions()->create(['order_id' => $order->id, 'amount' => $order->gift_card_used, 'note' => __('Teruggezet na geannuleerde bestelling')]);
        }
        if ($order->customer_id && $order->credit_used > 0) {
            Loyalty::addCredit($order->customer, $order->credit_used, 'refund', __('Teruggezet na geannuleerde bestelling :number', ['number' => $order->name]), $order->id);
        }
    }

    private function restock(Order $order): void
    {
        foreach ($order->items()->with('variant')->get() as $item) {
            $qty = $item->quantity - $item->refunded_quantity - $item->fulfilled_quantity;
            if ($qty > 0 && $item->variant && $item->variant->track_stock) {
                $item->variant->increment('stock', $qty);
            }
        }
    }

    private function send(Order $order, $mailable): void
    {
        try {
            $mail = Mail::to($order->email);
            if ($bcc = settings('notifications.bcc_orders')) {
                $mail->bcc($bcc);
            }
            $mail->send($mailable);
        } catch (\Throwable $e) {
            report($e);
            $order->log('mail_failed', __('E-mail kon niet worden verstuurd: :error', ['error' => $e->getMessage()]));
        }
    }
}
