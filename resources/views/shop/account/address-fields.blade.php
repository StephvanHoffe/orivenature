<div class="field-row">
  <label class="field"><span class="field__label">{{ __('Voornaam') }}</span><input class="field__input" type="text" name="first_name" value="{{ $a?->first_name }}" required></label>
  <label class="field"><span class="field__label">{{ __('Achternaam') }}</span><input class="field__input" type="text" name="last_name" value="{{ $a?->last_name }}" required></label>
</div>
<label class="field"><span class="field__label">{{ __('Bedrijf') }}</span><input class="field__input" type="text" name="company" value="{{ $a?->company }}"></label>
<label class="field"><span class="field__label">{{ __('Straat en huisnummer') }}</span><input class="field__input" type="text" name="address1" value="{{ $a?->address1 }}" required></label>
<label class="field"><span class="field__label">{{ __('Toevoeging') }}</span><input class="field__input" type="text" name="address2" value="{{ $a?->address2 }}"></label>
<div class="field-row">
  <label class="field"><span class="field__label">{{ __('Postcode') }}</span><input class="field__input" type="text" name="zip" value="{{ $a?->zip }}" required></label>
  <label class="field"><span class="field__label">{{ __('Plaats') }}</span><input class="field__input" type="text" name="city" value="{{ $a?->city }}" required></label>
</div>
<label class="field"><span class="field__label">{{ __('Land') }}</span>
  <select class="field__input" name="country_code">@foreach($countries as $code => $name)<option value="{{ $code }}" @selected(($a?->country_code ?? 'NL') === $code)>{{ $name }}</option>@endforeach</select></label>
<label class="field"><span class="field__label">{{ __('Telefoon') }}</span><input class="field__input" type="tel" name="phone" value="{{ $a?->phone }}"></label>
<label class="field field--check"><input type="checkbox" name="is_default" value="1" @checked($a?->is_default)> <span>{{ __('Als standaardadres instellen') }}</span></label>
