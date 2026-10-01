<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Documents;

class DocumentController extends Controller
{
    public function __construct()
    {
        abort_unless((bool) auth()->user()?->is_active, 403);
    }

    public function invoice(Order $order)
    {
        return Documents::invoice($order)->stream('factuur-'.$order->number.'.pdf');
    }

    public function packingSlip(Order $order)
    {
        return Documents::packingSlip($order)->stream('pakbon-'.$order->number.'.pdf');
    }
}
