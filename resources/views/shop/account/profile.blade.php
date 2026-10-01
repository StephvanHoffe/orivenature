@extends('shop.account.layout')
@section('title', __('Gegevens'))
@section('account')
  <header class="acct-head" data-reveal>
    <p class="eyebrow">{{ __('Mijn account') }}</p>
    <h1 class="h2 h2--xl">{{ __('Gegevens') }}</h1>
  </header>

  <div class="acct-forms">
    <section class="acct-card" id="persoonlijk" data-reveal>
      <h2 class="acct-card__title">{{ __('Persoonlijke gegevens') }}</h2>
      <form method="post" action="{{ route('account.profile.details') }}" class="form-stack">
        @csrf @method('put')
        @if($errors->details->any())<div class="form-error" role="alert">{{ $errors->details->first() }}</div>@endif
        <div class="field-row">
          <label class="field" for="p-first"><span class="field__label">{{ __('Voornaam') }}</span><input class="field__input" id="p-first" type="text" name="first_name" value="{{ old('first_name', $customer->first_name) }}" autocomplete="given-name" required></label>
          <label class="field" for="p-last"><span class="field__label">{{ __('Achternaam') }}</span><input class="field__input" id="p-last" type="text" name="last_name" value="{{ old('last_name', $customer->last_name) }}" autocomplete="family-name"></label>
        </div>
        <label class="field" for="p-phone"><span class="field__label">{{ __('Telefoonnummer') }}</span><input class="field__input" id="p-phone" type="tel" name="phone" value="{{ old('phone', $customer->phone) }}" autocomplete="tel"><small class="field__hint">{{ __('Handig voor de bezorger.') }}</small></label>
        <button class="btn btn--primary" type="submit">{{ __('Opslaan') }}</button>
      </form>
    </section>

    <section class="acct-card" id="e-mail" data-reveal>
      <h2 class="acct-card__title">{{ __('E-mailadres') }}</h2>
      <p class="acct-muted">{{ __('Nu: :email. Hiermee log je in en ontvang je je orderbevestigingen.', ['email' => $customer->email]) }}</p>
      <form method="post" action="{{ route('account.profile.email') }}" class="form-stack">
        @csrf @method('put')
        @if($errors->email->any())<div class="form-error" role="alert">{{ $errors->email->first() }}</div>@endif
        <label class="field" for="p-email"><span class="field__label">{{ __('Nieuw e-mailadres') }}</span><input class="field__input" id="p-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></label>
        <label class="field" for="p-email-pw"><span class="field__label">{{ __('Je huidige wachtwoord') }}</span><input class="field__input" id="p-email-pw" type="password" name="current_password" autocomplete="current-password" required></label>
        <button class="btn btn--primary" type="submit">{{ __('E-mailadres wijzigen') }}</button>
      </form>
    </section>

    <section class="acct-card" id="wachtwoord" data-reveal>
      <h2 class="acct-card__title">{{ __('Wachtwoord') }}</h2>
      <form method="post" action="{{ route('account.profile.password') }}" class="form-stack">
        @csrf @method('put')
        @if($errors->password->any())<div class="form-error" role="alert">{{ $errors->password->first() }}</div>@endif
        <label class="field" for="p-pw-current"><span class="field__label">{{ __('Huidig wachtwoord') }}</span><input class="field__input" id="p-pw-current" type="password" name="current_password" autocomplete="current-password" required></label>
        <div class="field-row">
          <label class="field" for="p-pw-new"><span class="field__label">{{ __('Nieuw wachtwoord') }}</span><input class="field__input" id="p-pw-new" type="password" name="password" autocomplete="new-password" minlength="8" required></label>
          <label class="field" for="p-pw-confirm"><span class="field__label">{{ __('Herhaal nieuw wachtwoord') }}</span><input class="field__input" id="p-pw-confirm" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required></label>
        </div>
        <small class="field__hint">{{ __('Minimaal 8 tekens.') }}</small>
        <button class="btn btn--primary" type="submit">{{ __('Wachtwoord wijzigen') }}</button>
      </form>
    </section>

    <section class="acct-card" id="nieuwsbrief" data-reveal>
      <h2 class="acct-card__title">{{ __('Nieuwsbrief') }}</h2>
      <form method="post" action="{{ route('account.profile.marketing') }}" class="form-stack">
        @csrf @method('put')
        <input type="hidden" name="accepts_marketing" value="0">
        <label class="acct-switch" for="p-marketing">
          <input id="p-marketing" type="checkbox" name="accepts_marketing" value="1" role="switch" @checked($subscribed)>
          <span class="acct-switch__track" aria-hidden="true"></span>
          <span>{{ __('Stuur mij nieuws, recepten en acties per e-mail') }}</span>
        </label>
        <button class="btn btn--ghost" type="submit">{{ __('Voorkeur opslaan') }}</button>
      </form>
    </section>

    <section class="acct-card acct-privacy" id="privacy" data-reveal>
      <h2 class="acct-card__title">{{ __('Privacy') }}</h2>
      <p class="acct-muted">{{ __('Bekijk welke gegevens we van je hebben, of verwijder je account.') }}</p>
      <a class="btn btn--ghost" href="{{ route('account.profile.export') }}"><svg width="18" height="18" aria-hidden="true"><use href="#i-download"/></svg> {{ __('Download mijn gegevens') }}</a>
      <details class="acct-delete" @if($errors->delete->any()) open @endif>
        <summary class="text-link acct-danger">{{ __('Account verwijderen') }}</summary>
        <form method="post" action="{{ route('account.profile.destroy') }}" class="form-stack">
          @csrf @method('delete')
          <p>{{ __('Je account, adressen, spaarpunten (:points) en tegoed (:credit) worden verwijderd. Gegevens van eerdere bestellingen bewaren we alleen zolang de wet dat voorschrijft voor onze boekhouding.', ['points' => $customer->points_balance, 'credit' => money($customer->credit_balance)]) }}</p>
          @if($errors->delete->any())<div class="form-error" role="alert">{{ $errors->delete->first() }}</div>@endif
          <label class="field" for="p-del-pw"><span class="field__label">{{ __('Je wachtwoord') }}</span><input class="field__input" id="p-del-pw" type="password" name="current_password" autocomplete="current-password" required></label>
          <label class="field field--check"><input type="checkbox" name="confirm" value="1" required> <span>{{ __('Ik begrijp dat dit niet ongedaan kan worden gemaakt') }}</span></label>
          <button class="btn acct-btn-danger" type="submit">{{ __('Account definitief verwijderen') }}</button>
        </form>
      </details>
    </section>
  </div>
@endsection
