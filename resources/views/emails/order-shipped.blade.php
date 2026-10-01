@extends('emails.layout', ['title' => __('Je bestelling is onderweg')])
@section('content')
  <p style="margin:0 0 6px;font-size:13px;letter-spacing:2px;text-transform:uppercase;color:#52572e;">{{ __('Bestelling :number', ['number' => $order->name]) }}</p>
  <h1 style="margin:0 0 14px;font-family:Georgia,'Libre Baskerville',serif;font-weight:400;font-size:28px;line-height:1.2;">{{ __('Je bestelling is onderweg') }}</h1>
  <p style="font-size:15px;line-height:1.6;margin:0;">{{ settings('notifications.shipped_intro') }}</p>
  @if($fulfillment->tracking_number)
    <p style="font-size:14px;margin:16px 0 0;">{{ $fulfillment->tracking_company }} · <strong>{{ $fulfillment->tracking_number }}</strong></p>
  @endif
  @if($link = $fulfillment->trackingLink())
    @include('emails._button', ['url' => $link, 'label' => __('Volg je pakket')])
  @else
    @include('emails._button', ['url' => $order->statusUrl(), 'label' => __('Bekijk je bestelling')])
  @endif
  @include('emails._order-lines')
@endsection
