@extends('shop.layouts.app')
@section('title', __('Inloggen'))
@section('noindex', true)
@section('content')
  <div class="section account-page">
    <div class="container container--form">
      <div class="account-card" data-reveal>
        <h1 class="h2 h2--xl">{{ __('Inloggen') }}</h1>
        @if(\App\Services\Loyalty::enabled())<p class="loyalty-earn"><svg width="18" height="18" aria-hidden="true"><use href="#i-gift"/></svg> {{ __('Log in om je spaarpunten en shoptegoed te bekijken.') }}</p>@endif
        <form method="post" action="{{ route('account.login') }}" class="form-stack">
          @csrf
          @if($errors->any())<p class="form-error" role="alert">{{ $errors->first() }}</p>@endif
          <label class="field"><span class="field__label">{{ __('E-mailadres') }}</span><input class="field__input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></label>
          <label class="field"><span class="field__label">{{ __('Wachtwoord') }}</span><input class="field__input" type="password" name="password" autocomplete="current-password" required></label>
          <label class="field field--check"><input type="checkbox" name="remember" value="1" checked> <span>{{ __('Ingelogd blijven') }}</span></label>
          <button class="btn btn--primary btn--lg btn--block" type="submit">{{ __('Inloggen') }}</button>
          <a class="text-link" href="{{ route('account.recover') }}">{{ __('Wachtwoord vergeten?') }}</a>
          <a class="text-link" href="{{ route('account.register') }}">{{ __('Nog geen account? Maak er gratis een aan') }}</a>
        </form>
      </div>
    </div>
  </div>
@endsection
