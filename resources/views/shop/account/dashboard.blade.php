@extends('shop.account.layout')
@section('title', __('Mijn account'))
@php
  $loyalty = \App\Services\Loyalty::enabled();
  $block = max(1, (int) settings('loyalty.redeem_points', 100));
  $toNext = $block - ($customer->points_balance % $block);
  $redeemable = \App\Services\Loyalty::redeemablePoints($customer);
  $address = $customer->defaultAddress();
@endphp
@section('account')
  <header class="acct-head" data-reveal>
    <p class="eyebrow">{{ __('Mijn account') }}</p>
    <h1 class="h2 h2--xl">{{ __('Hoi :name!', ['name' => $customer->first_name ?: $customer->name]) }}</h1>
    <p class="acct-head__sub">{{ __('Klant sinds :date', ['date' => $customer->created_at->translatedFormat('F Y')]) }}</p>
  </header>

  @if($current)
    <section class="acct-card acct-current" data-reveal aria-labelledby="current-title">
      <div class="acct-card__head">
        <div>
          <p class="eyebrow">{{ $current->isInProgress() ? __('Lopende bestelling') : __('Onlangs verzonden') }}</p>
          <h2 class="acct-card__title" id="current-title">{{ __('Bestelling :number', ['number' => $current->name]) }}</h2>
        </div>
        <div>@include('shop.account.partials.status', ['order' => $current])</div>
      </div>
      @include('shop.partials.order-timeline', ['order' => $current])
      <div class="acct-card__actions">
        <a class="text-link" href="{{ route('account.orders.show', $current->number) }}">{{ __('Bekijk de bestelling') }}</a>
        @if($current->checkoutUrl())<a class="btn btn--primary" href="{{ route('account.orders.pay', $current->number) }}">{{ __('Betaling afronden') }}</a>@endif
      </div>
    </section>
  @endif

  <div class="acct-tiles" data-reveal>
    @if($loyalty)
      <a class="acct-tile acct-tile--sage" href="{{ route('account.rewards') }}">
        <span class="acct-tile__label"><svg width="18" height="18" aria-hidden="true"><use href="#i-coin"/></svg> {{ __('Spaarpunten') }}</span>
        <strong>{{ number_format($customer->points_balance, 0, ',', '.') }}</strong>
        @if($redeemable >= max($block, (int) settings('loyalty.min_redeem')))
          <span class="acct-tile__hint">{{ __('Wissel in voor :value tegoed', ['value' => money(\App\Services\Loyalty::creditFor($redeemable))]) }} →</span>
        @else
          <span class="acct-tile__bar"><span style="width: {{ min(100, round(($customer->points_balance % $block) / $block * 100)) }}%"></span></span>
          <span class="acct-tile__hint">{{ __('Nog :n punten tot :value', ['n' => $toNext, 'value' => money((int) settings('loyalty.redeem_value'))]) }}</span>
        @endif
      </a>
    @endif
    <a class="acct-tile acct-tile--mauve" href="{{ $loyalty ? route('account.rewards') : url('/collections/all') }}">
      <span class="acct-tile__label"><svg width="18" height="18" aria-hidden="true"><use href="#i-gift"/></svg> {{ __('Shoptegoed') }}</span>
      <strong>{{ money($customer->credit_balance) }}</strong>
      <span class="acct-tile__hint">{{ $customer->credit_balance > 0 ? __('Wordt automatisch gebruikt bij de kassa') : __('Spaar punten en wissel ze in') }}</span>
    </a>
    <a class="acct-tile" href="{{ route('account.orders') }}">
      <span class="acct-tile__label"><svg width="18" height="18" aria-hidden="true"><use href="#i-bag"/></svg> {{ __('Bestellingen') }}</span>
      <strong>{{ $stats['orders'] }}</strong>
      <span class="acct-tile__hint">{{ __(':amount besteed', ['amount' => money($stats['spent'])]) }}</span>
    </a>
  </div>

  <section class="acct-section" data-reveal aria-labelledby="recent-title">
    <div class="acct-section__head">
      <h2 class="acct-section__title" id="recent-title">{{ __('Recente bestellingen') }}</h2>
      @if($orders->isNotEmpty())<a class="text-link" href="{{ route('account.orders') }}">{{ __('Alle bestellingen') }}</a>@endif
    </div>
    @if($orders->isEmpty())
      <div class="acct-empty">
        <svg width="34" height="34" aria-hidden="true"><use href="#i-bag"/></svg>
        <p>{{ __('Je hebt nog geen bestellingen geplaatst.') }}@if($loyalty) {{ __('Bij je eerste bestelling spaar je meteen punten.') }}@endif</p>
        <a class="btn btn--primary" href="{{ url('/collections/all') }}">{{ __('Ontdek onze essences') }} <svg width="18" height="18" aria-hidden="true"><use href="#i-arrow"/></svg></a>
      </div>
    @else
      <div class="ocards">
        @foreach($orders->reject(fn ($o) => $current && $o->is($current))->take(3) as $order)
          @include('shop.account.partials.order-card')
        @endforeach
      </div>
      @if($current && $orders->count() === 1)<p class="acct-muted">{{ __('Dit is je enige bestelling tot nu toe.') }}</p>@endif
    @endif
  </section>

  <div class="acct-pair" data-reveal>
    <section class="acct-card">
      <div class="acct-card__head"><h2 class="acct-card__title">{{ __('Gegevens') }}</h2><a class="text-link" href="{{ route('account.profile') }}">{{ __('Wijzigen') }}</a></div>
      <dl class="acct-dl">
        <div><dt>{{ __('Naam') }}</dt><dd>{{ $customer->name }}</dd></div>
        <div><dt>{{ __('E-mail') }}</dt><dd>{{ $customer->email }}</dd></div>
        <div><dt>{{ __('Telefoon') }}</dt><dd>{{ $customer->phone ?: '–' }}</dd></div>
        <div><dt>{{ __('Nieuwsbrief') }}</dt><dd>{{ $customer->accepts_marketing ? __('Aangemeld') : __('Niet aangemeld') }}</dd></div>
      </dl>
    </section>
    <section class="acct-card">
      <div class="acct-card__head"><h2 class="acct-card__title">{{ __('Standaardadres') }}</h2><a class="text-link" href="{{ route('account.addresses') }}">{{ $address ? __('Beheren') : __('Toevoegen') }}</a></div>
      @if($address)
        <address class="acct-address">{!! nl2br(e($address->formatted())) !!}</address>
      @else
        <p class="acct-muted">{{ __('Sla een adres op, dan is het afrekenen de volgende keer sneller.') }}</p>
      @endif
    </section>
  </div>
@endsection
