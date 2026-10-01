@php([$label, $tone] = $order->customerStatus())
<span class="ostatus ostatus--{{ $tone }}">{{ $label }}</span>
@if($order->financial_status === 'partially_refunded')<span class="ostatus ostatus--off">{{ __('Deels terugbetaald') }}</span>@endif
