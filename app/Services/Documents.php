<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

/** Factuur en pakbon als PDF; gebruikt door het beheer en door Mijn account. */
class Documents
{
    public static function invoice(Order $order): \Barryvdh\DomPDF\PDF
    {
        $order->loadMissing('items');
        $taxes = $order->taxBreakdown();
        // Btw over de verzendkosten hoort bij het tarief met het grootste aandeel
        $shippingTax = $order->tax_total - array_sum($taxes);
        if ($shippingTax > 0 && $taxes) {
            $main = array_search(max($taxes), $taxes, true);
            $taxes[$main] += $shippingTax;
        }
        ksort($taxes);

        return Pdf::loadView('pdf.invoice', compact('order', 'taxes'))->setPaper('a4');
    }

    public static function packingSlip(Order $order): \Barryvdh\DomPDF\PDF
    {
        $order->loadMissing('items');

        return Pdf::loadView('pdf.packing-slip', compact('order'))->setPaper('a4');
    }
}
