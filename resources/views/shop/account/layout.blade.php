@extends('shop.layouts.app')
@section('noindex', true)
@section('template', 'account')
@php
  $me = auth('customer')->user();
  $initials = collect([$me->first_name, $me->last_name])->filter()->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: mb_strtoupper(mb_substr($me->email, 0, 1));
  $openOrders = $me->orders()->where('status', '!=', 'cancelled')->where('financial_status', '!=', 'refunded')->where('fulfillment_status', '!=', 'fulfilled')->count();
  $nav = [
      ['account.dashboard', 'home', __('Overzicht'), null, 'account.dashboard'],
      ['account.orders', 'bag', __('Bestellingen'), $openOrders ?: null, 'account.orders*'],
  ];
  if (\App\Services\Loyalty::enabled() || $me->credit_balance > 0) {
      $nav[] = ['account.rewards', 'coin', __('Spaarpunten'), \App\Services\Loyalty::enabled() ? number_format($me->points_balance, 0, ',', '.') : null, 'account.rewards'];
  }
  $nav[] = ['account.addresses', 'pin', __('Adressen'), null, 'account.addresses'];
  $nav[] = ['account.profile', 'user', __('Gegevens'), null, 'account.profile'];
@endphp
@section('content')
  <div class="section account-page">
    <div class="container acct">
      <aside class="acct__side">
        <div class="acct__me">
          <span class="acct__avatar" aria-hidden="true">{{ $initials }}</span>
          <span class="acct__who"><strong>{{ $me->name }}</strong><small>{{ $me->email }}</small></span>
        </div>
        <nav class="acct__nav" aria-label="{{ __('Mijn account') }}">
          @foreach($nav as [$route, $icon, $label, $badge, $pattern])
            <a href="{{ route($route) }}" @if(request()->routeIs($pattern)) aria-current="page" @endif>
              <svg width="20" height="20" aria-hidden="true"><use href="#i-{{ $icon }}"/></svg>
              <span>{{ $label }}</span>
              @if($badge)<b>{{ $badge }}</b>@endif
            </a>
          @endforeach
          <form method="post" action="{{ route('account.logout') }}" class="acct__logout">
            @csrf
            <button type="submit"><svg width="20" height="20" aria-hidden="true"><use href="#i-logout"/></svg> <span>{{ __('Uitloggen') }}</span></button>
          </form>
        </nav>
      </aside>
      <div class="acct__main">
        @yield('account')
      </div>
    </div>
  </div>
@endsection
