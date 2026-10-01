@extends('emails.layout', ['title' => __('Bestelling :number', ['number' => $order->name])])
@section('content')
  <p style="margin:0 0 6px;font-size:13px;letter-spacing:2px;text-transform:uppercase;color:#52572e;">{{ __('Bestelling :number', ['number' => $order->name]) }}</p>
  <h1 style="margin:0 0 14px;font-family:Georgia,'Libre Baskerville',serif;font-weight:400;font-size:28px;line-height:1.2;">{{ __('Bedankt, :name!', ['name' => $order->shipping_address['first_name'] ?? '']) }}</h1>
  <p style="font-size:15px;line-height:1.6;margin:0;">{{ settings('notifications.confirmation_intro') }}</p>
  @include('emails._order-lines')
  @if($order->points_earned > 0)
    <p style="background:#e4ead2;border-radius:14px;padding:12px 16px;font-size:14px;">{{ __('Je hebt :points spaarpunten verdiend met deze bestelling. Wissel ze in je account in voor shoptegoed.', ['points' => $order->points_earned]) }}</p>
  @endif
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.6;margin-top:10px;">
    <tr>
      <td valign="top" style="padding-right:10px;"><strong>{{ __('Verzendadres') }}</strong><br>{!! nl2br(e($order->formattedShippingAddress())) !!}</td>
      <td valign="top"><strong>{{ __('Verzendmethode') }}</strong><br>{{ $order->shipping_method }}</td>
    </tr>
  </table>
  @include('emails._button', ['url' => $order->statusUrl(), 'label' => __('Bekijk je bestelling')])
  <p style="font-size:13px;color:#6b6c6b;margin:0;">{{ __('Vragen? Antwoord op deze mail of app ons, we helpen je graag.') }}</p>
@endsection
