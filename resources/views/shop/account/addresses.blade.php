@extends('shop.account.layout')
@section('title', __('Adressen'))
@php($failed = $errors->any() ? (string) old('_address') : null)
@section('account')
  <header class="acct-head" data-reveal>
    <p class="eyebrow">{{ __('Mijn account') }}</p>
    <h1 class="h2 h2--xl">{{ __('Adressen') }}</h1>
    <p class="acct-head__sub">{{ __('Je standaardadres staat bij het afrekenen al ingevuld.') }}</p>
  </header>
  @if($errors->any())<div class="form-error" role="alert"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

  <div class="acct-addresses">
    @foreach($customer->addresses as $address)
      <article class="acct-card acct-addr {{ $address->is_default ? 'is-default' : '' }}" data-reveal>
        <div class="acct-card__head">
          @if($address->is_default)<span class="ostatus ostatus--done">{{ __('Standaardadres') }}</span>@else<span></span>@endif
        </div>
        <address class="acct-address">{!! nl2br(e($address->formatted())) !!}@if($address->phone)<br>{{ $address->phone }}@endif</address>
        <div class="acct-addr__actions">
          @unless($address->is_default)
            <form method="post" action="{{ route('account.addresses.default', $address) }}">@csrf<button class="text-link" type="submit">{{ __('Maak standaard') }}</button></form>
          @endunless
          <form method="post" action="{{ route('account.addresses.delete', $address) }}" data-confirm="{{ __('Dit adres verwijderen?') }}">@csrf @method('delete')<button class="text-link acct-danger" type="submit">{{ __('Verwijderen') }}</button></form>
        </div>
        <details class="acct-edit" @if($failed === (string) $address->id) open @endif>
          <summary class="btn btn--ghost btn--sm"><svg width="16" height="16" aria-hidden="true"><use href="#i-edit"/></svg> {{ __('Bewerken') }}</summary>
          <form method="post" action="{{ route('account.addresses.update', $address) }}" class="form-stack">
            @csrf @method('put')
            @include('shop.account.address-fields', ['a' => $address])
            <button class="btn btn--primary" type="submit">{{ __('Opslaan') }}</button>
          </form>
        </details>
      </article>
    @endforeach

    <article class="acct-card acct-addr acct-addr--new" data-reveal>
      <details class="acct-edit" @if($customer->addresses->isEmpty() || $failed === 'new') open @endif>
        <summary class="acct-addr__add"><svg width="22" height="22" aria-hidden="true"><use href="#i-plus"/></svg> {{ __('Nieuw adres toevoegen') }}</summary>
        <form method="post" action="{{ route('account.addresses.store') }}" class="form-stack">
          @csrf
          @include('shop.account.address-fields', ['a' => null])
          <button class="btn btn--primary" type="submit">{{ __('Adres opslaan') }}</button>
        </form>
      </details>
    </article>
  </div>
@endsection
