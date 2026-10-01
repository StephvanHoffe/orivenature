@php($wholesale = $type === 'wholesale')
<form class="account-card contact-form" method="post" action="{{ route('contact') }}" data-reveal>
  @csrf
  <input type="hidden" name="type" value="{{ $type }}">
  <h2 class="h3">{{ $wholesale ? __('Aanvraag retailer') : __('Stuur ons een bericht') }}</h2>
  @if($errors->any())
    <div class="form-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
  @endif
  <div class="field-row">
    <label class="field"><span class="field__label">{{ $wholesale ? __('Contactpersoon') : __('Naam') }}</span><input class="field__input" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required></label>
    <label class="field"><span class="field__label">{{ __('E-mailadres') }}</span><input class="field__input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></label>
  </div>
  <div class="field-row">
    <label class="field"><span class="field__label">{{ __('Telefoonnummer') }} @unless($wholesale){{ __('(optioneel)') }}@endunless</span><input class="field__input" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel"></label>
    <label class="field"><span class="field__label">{{ __('Bedrijfsnaam') }} @unless($wholesale){{ __('(optioneel)') }}@endunless</span><input class="field__input" type="text" name="company" value="{{ old('company') }}" autocomplete="organization" @if($wholesale) required @endif></label>
  </div>
  @if($wholesale)
    <div class="field-row">
      <label class="field"><span class="field__label">{{ __('KvK-nummer') }}</span><input class="field__input" type="text" name="kvk" value="{{ old('kvk') }}"></label>
      <label class="field"><span class="field__label">{{ __('Plaats') }}</span><input class="field__input" type="text" name="city" value="{{ old('city') }}" autocomplete="address-level2"></label>
    </div>
    <label class="field"><span class="field__label">{{ __('Website of Instagram') }}</span><input class="field__input" type="text" name="website_url" value="{{ old('website_url') }}"></label>
  @endif
  <label class="field"><span class="field__label">{{ $wholesale ? __('Vertel iets over je zaak (optioneel)') : __('Bericht') }}</span><textarea class="field__input" name="message" rows="5" @unless($wholesale) required @endunless>{{ old('message') }}</textarea></label>
  <label class="hp" aria-hidden="true">Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
  <button class="btn btn--primary btn--lg" type="submit">{{ $wholesale ? __('Aanvraag versturen') : __('Versturen') }} <svg width="18" height="18" aria-hidden="true"><use href="#i-arrow"/></svg></button>
</form>
