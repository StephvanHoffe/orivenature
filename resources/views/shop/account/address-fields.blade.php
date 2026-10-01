@php
  $key = $a?->id ?? 'new';
  $useOld = (string) old('_address') === (string) $key;
  $v = fn ($field) => $useOld ? old($field) : $a?->{$field};
  $p = 'adr-'.$key.'-';
@endphp
<input type="hidden" name="_address" value="{{ $key }}">
<div class="field-row">
  <label class="field" for="{{ $p }}first"><span class="field__label">{{ __('Voornaam') }}</span><input class="field__input" id="{{ $p }}first" type="text" name="first_name" value="{{ $v('first_name') }}" autocomplete="given-name" required></label>
  <label class="field" for="{{ $p }}last"><span class="field__label">{{ __('Achternaam') }}</span><input class="field__input" id="{{ $p }}last" type="text" name="last_name" value="{{ $v('last_name') }}" autocomplete="family-name" required></label>
</div>
<label class="field" for="{{ $p }}company"><span class="field__label">{{ __('Bedrijf (optioneel)') }}</span><input class="field__input" id="{{ $p }}company" type="text" name="company" value="{{ $v('company') }}" autocomplete="organization"></label>
<label class="field" for="{{ $p }}a1"><span class="field__label">{{ __('Straat en huisnummer') }}</span><input class="field__input" id="{{ $p }}a1" type="text" name="address1" value="{{ $v('address1') }}" autocomplete="address-line1" required></label>
<label class="field" for="{{ $p }}a2"><span class="field__label">{{ __('Toevoeging (optioneel)') }}</span><input class="field__input" id="{{ $p }}a2" type="text" name="address2" value="{{ $v('address2') }}" autocomplete="address-line2"></label>
<div class="field-row">
  <label class="field" for="{{ $p }}zip"><span class="field__label">{{ __('Postcode') }}</span><input class="field__input" id="{{ $p }}zip" type="text" name="zip" value="{{ $v('zip') }}" autocomplete="postal-code" required></label>
  <label class="field" for="{{ $p }}city"><span class="field__label">{{ __('Plaats') }}</span><input class="field__input" id="{{ $p }}city" type="text" name="city" value="{{ $v('city') }}" autocomplete="address-level2" required></label>
</div>
<div class="field-row">
  <label class="field" for="{{ $p }}country"><span class="field__label">{{ __('Land') }}</span>
    <select class="field__input" id="{{ $p }}country" name="country_code" autocomplete="country">@foreach($countries as $code => $name)<option value="{{ $code }}" @selected(($v('country_code') ?? 'NL') === $code)>{{ $name }}</option>@endforeach</select></label>
  <label class="field" for="{{ $p }}phone"><span class="field__label">{{ __('Telefoon (optioneel)') }}</span><input class="field__input" id="{{ $p }}phone" type="tel" name="phone" value="{{ $v('phone') }}" autocomplete="tel"></label>
</div>
@unless($a?->is_default)
  <label class="field field--check"><input type="checkbox" name="is_default" value="1" @checked($useOld && old('is_default'))> <span>{{ __('Als standaardadres instellen') }}</span></label>
@endunless
