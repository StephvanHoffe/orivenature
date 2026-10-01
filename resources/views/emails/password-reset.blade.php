@extends('emails.layout', ['title' => __('Wachtwoord herstellen')])
@section('content')
  <h1 style="margin:0 0 14px;font-family:Georgia,'Libre Baskerville',serif;font-weight:400;font-size:28px;line-height:1.2;">{{ $name ? __('Hoi :name,', ['name' => $name]) : __('Hoi,') }}</h1>
  <p style="font-size:15px;line-height:1.6;margin:0;">{{ __('Je hebt gevraagd om een nieuw wachtwoord. Klik op de knop hieronder om er een te kiezen. De link is 60 minuten geldig.') }}</p>
  @include('emails._button', ['url' => $url, 'label' => __('Nieuw wachtwoord kiezen')])
  <p style="font-size:13px;color:#6b6c6b;margin:0;">{{ __('Heb je dit niet aangevraagd? Dan kun je deze mail negeren.') }}</p>
@endsection
