@extends('shop.layouts.app')
@section('title', __('Wachtwoord vergeten'))
@section('noindex', true)
@section('content')
  <div class="section account-page">
    <div class="container container--form">
      <div class="account-card" data-reveal>
        <h1 class="h2 h2--xl">{{ __('Wachtwoord vergeten') }}</h1>
        <p>{{ __('Vul je e-mailadres in. We sturen je een link om een nieuw wachtwoord te kiezen.') }}</p>
        @if(session('status'))<p class="form-success" role="status">{{ session('status') }}</p>@endif
        <form method="post" action="{{ route('account.recover') }}" class="form-stack">
          @csrf
          @error('email')<p class="form-error" role="alert">{{ $message }}</p>@enderror
          <label class="field"><span class="field__label">{{ __('E-mailadres') }}</span><input class="field__input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></label>
          <button class="btn btn--primary btn--block" type="submit">{{ __('Verstuur de link') }}</button>
          <a class="text-link" href="{{ route('account.login') }}">{{ __('Terug naar inloggen') }}</a>
        </form>
      </div>
    </div>
  </div>
@endsection
