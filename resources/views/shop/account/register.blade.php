@extends('shop.layouts.app')
@section('title', __('Account aanmaken'))
@section('noindex', true)
@section('content')
  <div class="section account-page">
    <div class="container container--form">
      <div class="account-card" data-reveal>
        <h1 class="h2 h2--xl">{{ __('Account aanmaken') }}</h1>
        @if(\App\Services\Loyalty::enabled())
          <p class="loyalty-earn"><svg width="18" height="18" aria-hidden="true"><use href="#i-gift"/></svg> {{ __('Word lid van het :program: je krijgt :bonus welkomstpunten en spaart bij elke bestelling.', ['program' => settings('loyalty.name'), 'bonus' => settings('loyalty.signup_bonus')]) }}</p>
        @endif
        <form method="post" action="{{ route('account.register') }}" class="form-stack">
          @csrf
          @if($errors->any())<div class="form-error" role="alert"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
          <div class="field-row">
            <label class="field"><span class="field__label">{{ __('Voornaam') }}</span><input class="field__input" type="text" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" required></label>
            <label class="field"><span class="field__label">{{ __('Achternaam') }}</span><input class="field__input" type="text" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name"></label>
          </div>
          <label class="field"><span class="field__label">{{ __('E-mailadres') }}</span><input class="field__input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></label>
          <label class="field"><span class="field__label">{{ __('Wachtwoord (minimaal 8 tekens)') }}</span><input class="field__input" type="password" name="password" autocomplete="new-password" minlength="8" required></label>
          <label class="field field--check"><input type="checkbox" name="accepts_marketing" value="1" @checked(old('accepts_marketing'))> <span>{{ __('Stuur mij nieuws, recepten en acties per e-mail') }}</span></label>
          <button class="btn btn--primary btn--lg btn--block" type="submit">{{ __('Account aanmaken') }}</button>
          <a class="text-link" href="{{ route('account.login') }}">{{ __('Al een account? Inloggen') }}</a>
        </form>
      </div>
    </div>
  </div>
@endsection
