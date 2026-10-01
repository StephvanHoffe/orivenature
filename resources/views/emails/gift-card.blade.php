@extends('emails.layout', ['title' => __('Cadeaubon')])
@section('content')
  <h1 style="margin:0 0 14px;font-family:Georgia,'Libre Baskerville',serif;font-weight:400;font-size:28px;line-height:1.2;">{{ __('Je hebt een cadeaubon!') }}</h1>
  @if($message)<p style="font-size:15px;line-height:1.6;margin:0 0 14px;white-space:pre-line;">{{ $message }}</p>@endif
  <div style="margin:18px 0;padding:22px;border-radius:16px;background:#52572e;color:#f2efe4;text-align:center;">
    <div style="font-size:13px;letter-spacing:2px;text-transform:uppercase;opacity:.8;">{{ __('Tegoed') }}</div>
    <div style="font-family:Georgia,serif;font-size:34px;margin:6px 0 12px;">{{ money($giftCard->balance) }}</div>
    <div style="display:inline-block;padding:8px 14px;border-radius:10px;background:#f2efe4;color:#2b2b2b;font-family:monospace;font-size:18px;letter-spacing:2px;">{{ $giftCard->code }}</div>
    @if($giftCard->expires_at)<div style="font-size:12px;margin-top:10px;opacity:.8;">{{ __('Geldig tot :date', ['date' => $giftCard->expires_at->translatedFormat('j F Y')]) }}</div>@endif
  </div>
  <p style="font-size:14px;line-height:1.6;margin:0;">{{ __('Vul de code in bij het afrekenen onder "Korting en tegoed".') }}</p>
  @include('emails._button', ['url' => url('/collections/all'), 'label' => __('Shop nu')])
@endsection
