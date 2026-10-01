@extends('emails.layout', ['title' => $heading])
@section('content')
  <h1 style="margin:0 0 14px;font-family:Georgia,'Libre Baskerville',serif;font-weight:400;font-size:26px;line-height:1.2;">{{ $heading }}</h1>
  @if($firstName)<p style="font-size:15px;line-height:1.6;margin:0 0 10px;">{{ __('Hoi :name,', ['name' => $firstName]) }}</p>@endif
  <p style="font-size:15px;line-height:1.6;margin:0;">{{ $body }}</p>
  <p style="font-size:14px;line-height:1.6;margin:16px 0 0;color:#6b6c6b;">{{ __('Heb jij dit niet gedaan? Neem dan direct contact met ons op via :email.', ['email' => settings('store.email')]) }}</p>
  @include('emails._button', ['url' => route('account.login'), 'label' => __('Naar mijn account')])
@endsection
