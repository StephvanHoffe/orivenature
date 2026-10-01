@extends('emails.layout', ['title' => __('Terugbetaling')])
@section('content')
  <p style="margin:0 0 6px;font-size:13px;letter-spacing:2px;text-transform:uppercase;color:#52572e;">{{ __('Bestelling :number', ['number' => $order->name]) }}</p>
  <h1 style="margin:0 0 14px;font-family:Georgia,'Libre Baskerville',serif;font-weight:400;font-size:28px;line-height:1.2;">{{ __('We hebben :amount terugbetaald', ['amount' => money($refund->amount)]) }}</h1>
  <p style="font-size:15px;line-height:1.6;margin:0;">{{ __('Het bedrag staat binnen enkele werkdagen weer op je rekening, via dezelfde betaalmethode als je bestelling. Betaalde je (deels) met shoptegoed of een cadeaubon, dan is dat deel daar weer op teruggezet.') }}</p>
  @if($refund->reason)<p style="font-size:14px;color:#6b6c6b;">{{ __('Reden') }}: {{ $refund->reason }}</p>@endif
  @include('emails._button', ['url' => $order->statusUrl(), 'label' => __('Bekijk je bestelling')])
@endsection
