@extends('shop.layouts.app')
@section('title', __('Afrekenen'))
@section('template', 'checkout')
@section('noindex', true)
@push('pixel')
  <script>fbq('track', 'InitiateCheckout', { value: {{ number_format($pricing->subtotal / 100, 2, '.', '') }}, currency: 'EUR', num_items: {{ $cart->count() }} });</script>
@endpush
@php
  $address = $customer?->defaultAddress();
  $val = fn ($key, $default = null) => old($key, $default);
@endphp
@section('content')
  <div class="section checkout-page">
    <div class="container checkout">
      <button class="checkout-toggle" type="button" data-summary-toggle aria-expanded="false" aria-controls="checkout-summary">
        <span><svg width="18" height="18" aria-hidden="true"><use href="#i-bag"/></svg> <span data-toggle-label data-open="{{ __('Verberg je bestelling') }}" data-closed="{{ __('Bekijk je bestelling') }}">{{ __('Bekijk je bestelling') }}</span></span>
        <strong data-summary-total>{{ money($pricing->total) }}</strong>
      </button>
      <form class="checkout__form" method="post" action="{{ route('checkout.place') }}" data-checkout-form novalidate>
        @csrf
        <header class="page-head"><h1 class="h2 h2--xl">{{ __('Afrekenen') }}</h1></header>

        @if($errors->any())
          <div class="form-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <fieldset class="checkout__block">
          <legend class="checkout__legend"><span>1</span> {{ __('Contact') }}</legend>
          @guest('customer')<p class="checkout__login">{{ __('Al een account?') }} <a class="text-link" href="{{ route('account.login') }}">{{ __('Inloggen') }}</a> {{ __('en gebruik je spaarpunten.') }}</p>@endguest
          <label class="field"><span class="field__label">{{ __('E-mailadres') }}</span>
            <input class="field__input" type="email" name="email" value="{{ $email }}" autocomplete="email" required></label>
          <label class="field field--check"><input type="checkbox" name="accepts_marketing" value="1" @checked($val('accepts_marketing', settings('checkout.newsletter_default')))> <span>{{ __('Stuur mij nieuws, recepten en acties per e-mail') }}</span></label>
        </fieldset>

        <fieldset class="checkout__block">
          <legend class="checkout__legend"><span>2</span> {{ __('Bezorging') }}</legend>
          <label class="field"><span class="field__label">{{ __('Land') }}</span>
            <select class="field__input" name="country_code" autocomplete="country">
              @foreach($countries as $code => $name)<option value="{{ $code }}" @selected($val('country_code', $cart->meta('country_code') ?? $address?->country_code ?? 'NL') === $code)>{{ $name }}</option>@endforeach
            </select></label>
          <div class="field-row">
            <label class="field"><span class="field__label">{{ __('Voornaam') }}</span><input class="field__input" type="text" name="first_name" value="{{ $val('first_name', $address?->first_name ?? $customer?->first_name) }}" autocomplete="given-name" required></label>
            <label class="field"><span class="field__label">{{ __('Achternaam') }}</span><input class="field__input" type="text" name="last_name" value="{{ $val('last_name', $address?->last_name ?? $customer?->last_name) }}" autocomplete="family-name" required></label>
          </div>
          <label class="field"><span class="field__label">{{ __('Straat en huisnummer') }}</span><input class="field__input" type="text" name="address1" value="{{ $val('address1', $address?->address1) }}" autocomplete="address-line1" required></label>
          <label class="field"><span class="field__label">{{ __('Toevoeging, appartement (optioneel)') }}</span><input class="field__input" type="text" name="address2" value="{{ $val('address2', $address?->address2) }}" autocomplete="address-line2"></label>
          <div class="field-row">
            <label class="field"><span class="field__label">{{ __('Postcode') }}</span><input class="field__input" type="text" name="zip" value="{{ $val('zip', $address?->zip) }}" autocomplete="postal-code" required></label>
            <label class="field"><span class="field__label">{{ __('Plaats') }}</span><input class="field__input" type="text" name="city" value="{{ $val('city', $address?->city) }}" autocomplete="address-level2" required></label>
          </div>
          <label class="field"><span class="field__label">{{ __('Telefoonnummer') }} @unless(settings('checkout.require_phone')) {{ __('(optioneel, voor de bezorger)') }} @endunless</span><input class="field__input" type="tel" name="phone" value="{{ $val('phone', $address?->phone ?? $customer?->phone) }}" autocomplete="tel" @if(settings('checkout.require_phone')) required @endif></label>
          <label class="field"><span class="field__label">{{ __('Bedrijfsnaam (optioneel)') }}</span><input class="field__input" type="text" name="company" value="{{ $val('company', $address?->company) }}" autocomplete="organization"></label>
          @auth('customer')<label class="field field--check"><input type="checkbox" name="save_address" value="1" @checked(! $address)> <span>{{ __('Bewaar dit adres in mijn account') }}</span></label>@endauth
        </fieldset>

        <fieldset class="checkout__block">
          <legend class="checkout__legend"><span>3</span> {{ __('Verzendmethode') }}</legend>
          <div class="choices" data-shipping-rates data-unavailable="{{ __('We kunnen (nog) niet naar dit land verzenden.') }}">
            @if(! $pricing->shippingAvailable)
              <p class="form-error">{{ __('We kunnen (nog) niet naar dit land verzenden.') }}</p>
            @endif
            @foreach($pricing->shippingRates as $rate)
              <label class="choice">
                <input type="radio" name="shipping_rate_id" value="{{ $rate->id }}" @checked($rate->id === $pricing->shippingRate?->id)>
                <span class="choice__body"><strong>{{ $rate->name }}</strong>@if($rate->description)<small>{{ $rate->description }}</small>@endif</span>
                <span class="choice__price">{{ $pricing->freeShipping || $rate->price === 0 ? __('Gratis') : money($rate->price) }}</span>
              </label>
            @endforeach
          </div>
        </fieldset>

        <fieldset class="checkout__block">
          <legend class="checkout__legend"><span>4</span> {{ __('Factuuradres') }}</legend>
          <label class="field field--check"><input type="checkbox" name="billing_same" value="1" @checked(old('billing_same', true))> <span>{{ __('Zelfde als bezorgadres') }}</span></label>
          <div class="checkout__billing" data-billing @if(old('billing_same', true)) hidden @endif>
            <div class="field-row">
              <label class="field"><span class="field__label">{{ __('Voornaam') }}</span><input class="field__input" type="text" name="billing[first_name]" value="{{ old('billing.first_name') }}"></label>
              <label class="field"><span class="field__label">{{ __('Achternaam') }}</span><input class="field__input" type="text" name="billing[last_name]" value="{{ old('billing.last_name') }}"></label>
            </div>
            <label class="field"><span class="field__label">{{ __('Bedrijfsnaam (optioneel)') }}</span><input class="field__input" type="text" name="billing[company]" value="{{ old('billing.company') }}"></label>
            <label class="field"><span class="field__label">{{ __('Straat en huisnummer') }}</span><input class="field__input" type="text" name="billing[address1]" value="{{ old('billing.address1') }}"></label>
            <div class="field-row">
              <label class="field"><span class="field__label">{{ __('Postcode') }}</span><input class="field__input" type="text" name="billing[zip]" value="{{ old('billing.zip') }}"></label>
              <label class="field"><span class="field__label">{{ __('Plaats') }}</span><input class="field__input" type="text" name="billing[city]" value="{{ old('billing.city') }}"></label>
            </div>
            <label class="field"><span class="field__label">{{ __('Land') }}</span>
              <select class="field__input" name="billing[country_code]">@foreach(\App\Support\Countries::LIST as $code => $name)<option value="{{ $code }}" @selected(old('billing.country_code', 'NL') === $code)>{{ $name }}</option>@endforeach</select></label>
          </div>
        </fieldset>

        <fieldset class="checkout__block">
          <legend class="checkout__legend"><span>5</span> {{ __('Korting en tegoed') }}</legend>
          <div class="code-field">
            <label class="visually-hidden" for="discount_code">{{ __('Kortingscode') }}</label>
            <input class="field__input" id="discount_code" type="text" name="discount_code" placeholder="{{ __('Kortingscode') }}" autocomplete="off">
            <button class="btn btn--ghost" type="button" data-apply="discount_code">{{ __('toepassen') }}</button>
          </div>
          <p class="form-error" data-discount-error @if(! $errors->has('discount_code')) hidden @endif>{{ $errors->first('discount_code') }}</p>
          <div class="code-field">
            <label class="visually-hidden" for="gift_card">{{ __('Cadeaubon') }}</label>
            <input class="field__input" id="gift_card" type="text" name="gift_card" placeholder="{{ __('Cadeauboncode') }}" autocomplete="off">
            <button class="btn btn--ghost" type="button" data-apply="gift_card">{{ __('toepassen') }}</button>
          </div>
          <p class="form-error" data-gift-error hidden></p>
          @auth('customer')
            @if($customer->credit_balance > 0)
              <label class="choice choice--credit">
                <input type="checkbox" name="use_credit" value="1" @checked($cart->meta('use_credit'))>
                <span class="choice__body"><strong>{{ __('Gebruik mijn shoptegoed') }}</strong><small>{{ __('Je hebt :amount tegoed uit het spaarprogramma.', ['amount' => money($customer->credit_balance)]) }}</small></span>
              </label>
            @endif
          @else
            @if(\App\Services\Loyalty::enabled())
              <label class="field"><span class="field__label">{{ __('Wachtwoord (optioneel)') }}</span>
                <input class="field__input" type="password" name="password" autocomplete="new-password" minlength="8">
                <small class="field__hint">{{ __('Kies een wachtwoord en je account wordt meteen aangemaakt: je krijgt :bonus welkomstpunten en spaart punten met deze bestelling.', ['bonus' => settings('loyalty.signup_bonus')]) }}</small>
              </label>
            @endif
          @endauth
        </fieldset>

        @if(settings('checkout.order_note'))
          <fieldset class="checkout__block">
            <legend class="checkout__legend"><span>6</span> {{ __('Opmerking (optioneel)') }}</legend>
            <label class="visually-hidden" for="customer_note">{{ __('Opmerking') }}</label>
            <textarea class="field__input" id="customer_note" name="customer_note" rows="3">{{ old('customer_note') }}</textarea>
          </fieldset>
        @endif

        <div class="checkout__pay">
          <button class="btn btn--primary btn--lg btn--block" type="submit" data-pay @disabled(! $paymentsReady && $pricing->total > 0)>
            <svg width="18" height="18" aria-hidden="true"><use href="#i-lock"/></svg> {{ __('Bestelling plaatsen en betalen') }} · <span data-pay-total>{{ money($pricing->total) }}</span>
          </button>
          @include('shop.partials.pay-icons', ['class' => 'pay-icons--center'])
          <p class="checkout__legal">{!! __('Door je bestelling te plaatsen ga je akkoord met onze <a href=":terms">algemene voorwaarden</a> en ons <a href=":privacy">privacybeleid</a>.', ['terms' => url('/pages/'.settings('checkout.terms_page')), 'privacy' => url('/pages/'.settings('checkout.privacy_page'))]) !!}</p>
        </div>
      </form>

      <aside class="checkout__summary" id="checkout-summary" data-checkout-summary aria-label="{{ __('Overzicht') }}">
        @include('shop.partials.checkout-summary')
      </aside>
    </div>
  </div>
@endsection
