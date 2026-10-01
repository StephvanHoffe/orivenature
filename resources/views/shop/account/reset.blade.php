@extends('shop.layouts.app')
@section('title', __('Nieuw wachtwoord'))
@section('noindex', true)
@section('content')
  <div class="section account-page">
    <div class="container container--form">
      <div class="account-card" data-reveal>
        <h1 class="h2 h2--xl">{{ __('Nieuw wachtwoord') }}</h1>
        <form method="post" action="{{ route('account.reset.update') }}" class="form-stack">
          @csrf
          <input type="hidden" name="token" value="{{ $token }}">
          @if($errors->any())<p class="form-error" role="alert">{{ $errors->first() }}</p>@endif
          <label class="field"><span class="field__label">{{ __('E-mailadres') }}</span><input class="field__input" type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required></label>
          <label class="field"><span class="field__label">{{ __('Nieuw wachtwoord') }}</span><input class="field__input" type="password" name="password" autocomplete="new-password" minlength="8" required></label>
          <label class="field"><span class="field__label">{{ __('Herhaal wachtwoord') }}</span><input class="field__input" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required></label>
          <button class="btn btn--primary btn--lg btn--block" type="submit">{{ __('Wachtwoord opslaan') }}</button>
        </form>
      </div>
    </div>
  </div>
@endsection
