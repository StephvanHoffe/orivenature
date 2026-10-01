<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\Payments;
use App\Services\Payments\TestGateway;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /** Mollie meldt statuswijzigingen; we halen de status altijd zelf op bij Mollie. */
    public function webhook(Request $request)
    {
        $payment = Payment::where('provider', 'mollie')->where('provider_id', (string) $request->input('id'))->first();
        if ($payment) {
            try {
                Payments::gateway('mollie')->refresh($payment);
            } catch (\Throwable $e) {
                report($e);

                return response('error', 500);
            }
        }

        return response('ok');
    }

    public function test(string $payment)
    {
        abort_unless(Payments::testAllowed(), 404);
        $payment = Payment::where('provider', 'test')->where('provider_id', $payment)->with('order')->firstOrFail();

        return view('shop.test-payment', compact('payment'));
    }

    public function completeTest(Request $request, string $payment)
    {
        abort_unless(Payments::testAllowed(), 404);
        $payment = Payment::where('provider', 'test')->where('provider_id', $payment)->where('status', 'open')->firstOrFail();
        $status = in_array($request->input('status'), ['paid', 'failed', 'canceled'], true) ? $request->input('status') : 'paid';
        (new TestGateway)->complete($payment, $status);

        return redirect()->route('checkout.return', $payment->order->token);
    }
}
