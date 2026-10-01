@extends('emails.layout', ['title' => __('Welkom')])
@section('content')
  <h1 style="margin:0 0 14px;font-family:Georgia,'Libre Baskerville',serif;font-weight:400;font-size:28px;line-height:1.2;">{{ $firstName ? __('Welkom, :name!', ['name' => $firstName]) : __('Welkom bij de community!') }}</h1>
  <p style="font-size:15px;line-height:1.6;margin:0;">{{ __('Leuk dat je je hebt aangemeld. Je hoort als eerste over nieuwe essences, recepten en acties.') }}</p>
  @if($code)
    <p style="font-size:15px;line-height:1.6;">{{ __('Zoals beloofd: met deze code krijg je korting op je eerste bestelling.') }}</p>
    <p style="text-align:center;margin:20px 0;"><span style="display:inline-block;padding:14px 24px;border:2px dashed #52572e;border-radius:12px;font-size:22px;letter-spacing:3px;font-weight:700;">{{ $code }}</span></p>
  @endif
  @include('emails._button', ['url' => url('/'), 'label' => __('Ontdek onze essences')])
@endsection
