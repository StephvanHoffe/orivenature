<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentController extends Controller
{
    public function __construct()
    {
        abort_unless((bool) auth()->user()?->is_active, 403);
    }

    public function invoice(Order $order)
    {
        $order->load('items');
        $taxes = $order->taxBreakdown();
        // Btw over de verzendkosten hoort bij het tarief met het grootste aandeel
        $shippingTax = $order->tax_total - array_sum($taxes);
        if ($shippingTax > 0 && $taxes) {
            $main = array_search(max($taxes), $taxes, true);
            $taxes[$main] += $shippingTax;
        }
        ksort($taxes);

        return Pdf::loadView('pdf.invoice', compact('order', 'taxes'))->setPaper('a4')
            ->stream('factuur-'.$order->number.'.pdf');
    }

    public function packingSlip(Order $order)
    {
        $order->load('items');

        return Pdf::loadView('pdf.packing-slip', compact('order'))->setPaper('a4')
            ->stream('pakbon-'.$order->number.'.pdf');
    }
}
