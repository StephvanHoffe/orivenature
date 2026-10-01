@extends('emails.layout', ['title' => __('Je winkelwagen')])
@section('content')
  <h1 style="margin:0 0 14px;font-family:Georgia,'Libre Baskerville',serif;font-weight:400;font-size:28px;line-height:1.2;">{{ __('Nog even dit…') }}</h1>
  <p style="font-size:15px;line-height:1.6;margin:0;">{{ settings('notifications.abandoned_intro') }}</p>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:18px 0;">
    @foreach($checkout->cart as $line)
      <tr>
        <td style="padding:8px 0;border-bottom:1px solid #eee;width:64px;">@if(!empty($line['image']))<img src="{{ \App\Support\Media::url($line['image'], 120) }}" width="52" alt="" style="display:block;border-radius:10px;background:#c9d6a9;">@endif</td>
        <td style="padding:8px;border-bottom:1px solid #eee;font-size:14px;"><strong>{{ $line['title'] }}</strong>@if(!empty($line['variant_title']))<br><span style="color:#6b6c6b;">{{ $line['variant_title'] }}</span>@endif</td>
        <td align="right" style="padding:8px 0;border-bottom:1px solid #eee;font-size:14px;">{{ $line['quantity'] }} × {{ money($line['price']) }}</td>
      </tr>
    @endforeach
  </table>
  @include('emails._button', ['url' => $checkout->recoveryUrl(), 'label' => __('Rond je bestelling af')])
@endsection
