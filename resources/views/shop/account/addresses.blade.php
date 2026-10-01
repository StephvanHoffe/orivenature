@extends('shop.layouts.app')
@section('title', __('Adressen'))
@section('noindex', true)
@section('content')
  <div class="section account-page">
    <div class="container">
      <header class="page-head page-head--row" data-reveal>
        <h1 class="h2 h2--xl">{{ __('Adressen') }}</h1>
        <a class="btn btn--ghost" href="{{ route('account.dashboard') }}">{{ __('Terug naar mijn account') }}</a>
      </header>
      @if($errors->any())<div class="form-error" role="alert"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
      <div class="account-grid account-grid--even">
        @foreach($customer->addresses as $address)
          <div class="account-card">
            @if($address->is_default)<p class="pill-label">{{ __('Standaardadres') }}</p>@endif
            <address>{!! nl2br(e($address->formatted())) !!}</address>
            <details class="address-edit">
              <summary class="text-link">{{ __('Bewerken') }}</summary>
              <form method="post" action="{{ route('account.addresses.update', $address) }}" class="form-stack">
                @csrf @method('put')
                @include('shop.account.address-fields', ['a' => $address])
                <button class="btn btn--primary" type="submit">{{ __('Opslaan') }}</button>
              </form>
            </details>
            <form method="post" action="{{ route('account.addresses.delete', $address) }}" class="address-delete">@csrf @method('delete')<button class="text-link" type="submit">{{ __('Verwijderen') }}</button></form>
          </div>
        @endforeach
        <div class="account-card">
          <h2 class="h2">{{ __('Nieuw adres') }}</h2>
          <form method="post" action="{{ route('account.addresses.store') }}" class="form-stack">
            @csrf
            @include('shop.account.address-fields', ['a' => null])
            <button class="btn btn--primary" type="submit">{{ __('Adres toevoegen') }}</button>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
