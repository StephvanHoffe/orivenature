@extends('shop.layouts.app')
@section('title', __('Mijn account'))
@section('noindex', true)
@php
  $redeemable = \App\Services\Loyalty::redeemablePoints($customer);
  $block = (int) settings('loyalty.redeem_points', 100);
  $toNext = $block - ($customer->points_balance % $block);
@endphp
@section('content')
  <div class="section account-page">
    <div class="container">
      <header class="page-head page-head--row" data-reveal>
        <div>
          <p class="eyebrow">{{ __('Mijn account') }}</p>
          <h1 class="h2 h2--xl">{{ __('Hoi :name!', ['name' => $customer->first_name ?: $customer->name]) }}</h1>
        </div>
        <form method="post" action="{{ route('account.logout') }}">@csrf<button class="btn btn--ghost" type="submit">{{ __('Uitloggen') }}</button></form>
      </header>

      <div class="account-grid">
        <div class="account-main">
          @if(\App\Services\Loyalty::enabled())
            <section class="loyalty__card loyalty-wallet" id="sparen" data-reveal>
              <div class="loyalty__intro">
                <p class="pill-label"><svg width="16" height="16" aria-hidden="true"><use href="#i-gift"/></svg> {{ settings('loyalty.name') }}</p>
                <div class="wallet">
                  <div class="wallet__item"><span>{{ __('Spaarpunten') }}</span><strong>{{ number_format($customer->points_balance, 0, ',', '.') }}</strong></div>
                  <div class="wallet__item"><span>{{ __('Shoptegoed') }}</span><strong>{{ money($customer->credit_balance) }}</strong></div>
                </div>
                <p class="loyalty__text">{{ \App\Services\Loyalty::rateText() }}. {{ __('Je verdient :points punt per € 1.', ['points' => settings('loyalty.points_per_euro')]) }}</p>
                @if($redeemable >= max($block, (int) settings('loyalty.min_redeem')))
                  <form method="post" action="{{ route('account.redeem') }}" class="redeem">
                    @csrf
                    <label class="field"><span class="field__label">{{ __('Aantal punten inwisselen') }}</span>
                      <select class="field__input" name="points">
                        @for($p = $redeemable; $p >= $block; $p -= $block)
                          <option value="{{ $p }}">{{ $p }} {{ __('punten') }} → {{ money(\App\Services\Loyalty::creditFor($p)) }}</option>
                        @endfor
                      </select>
                    </label>
                    <button class="btn btn--primary" type="submit">{{ __('Inwisselen voor tegoed') }}</button>
                  </form>
                @else
                  <div class="progress" role="img" aria-label="{{ __('Nog :n punten tot je volgende beloning', ['n' => $toNext]) }}">
                    <span style="width: {{ min(100, round(($customer->points_balance % $block) / $block * 100)) }}%"></span>
                  </div>
                  <p class="field__hint">{{ __('Nog :n punten tot :value shoptegoed.', ['n' => $toNext, 'value' => money((int) settings('loyalty.redeem_value'))]) }}</p>
                @endif
                @error('points')<p class="form-error">{{ $message }}</p>@enderror
              </div>
              <div class="wallet-history">
                <h2 class="order-card__title">{{ __('Recente activiteit') }}</h2>
                <ul>
                  @forelse($loyalty->concat($credit)->sortByDesc('created_at')->take(8) as $t)
                    <li>
                      <span>{{ $t->description ?: (\App\Models\LoyaltyTransaction::TYPES[$t->type] ?? $t->type) }}<small>{{ $t->created_at->translatedFormat('j M Y') }}</small></span>
                      @if($t instanceof \App\Models\LoyaltyTransaction)
                        <strong class="{{ $t->points < 0 ? 'is-neg' : '' }}">{{ $t->points > 0 ? '+' : '' }}{{ $t->points }} {{ __('pt') }}</strong>
                      @else
                        <strong class="{{ $t->amount < 0 ? 'is-neg' : '' }}">{{ $t->amount > 0 ? '+' : '-' }}{{ money(abs($t->amount)) }}</strong>
                      @endif
                    </li>
                  @empty
                    <li><span>{{ __('Nog geen activiteit. Bij je eerste bestelling spaar je punten.') }}</span></li>
                  @endforelse
                </ul>
              </div>
            </section>
          @endif

          <section class="account-card" data-reveal>
            <h2 class="h2">{{ __('Bestellingen') }}</h2>
            @if($orders->isEmpty())
              <p>{{ __('Je hebt nog geen bestellingen geplaatst.') }}</p>
              <a class="btn btn--primary" href="{{ url('/collections/all') }}">{{ __('Ontdek onze essences') }}</a>
            @else
              <div class="table-wrap">
                <table class="table">
                  <thead><tr><th>{{ __('Bestelling') }}</th><th>{{ __('Datum') }}</th><th>{{ __('Status') }}</th><th>{{ __('Totaal') }}</th></tr></thead>
                  <tbody>
                    @foreach($orders as $order)
                      <tr>
                        <td><a class="text-link" href="{{ route('account.order', $order->number) }}">{{ $order->name }}</a></td>
                        <td>{{ $order->placed_at?->translatedFormat('j M Y') }}</td>
                        <td>{{ $order->status === 'cancelled' ? __('Geannuleerd') : (\App\Models\Order::FULFILLMENT_STATUSES[$order->fulfillment_status] ?? '') }}</td>
                        <td>{{ money($order->grandTotal()) }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
              @include('shop.partials.pagination', ['paginator' => $orders])
            @endif
          </section>
        </div>

        <aside class="account-side">
          <div class="account-card">
            <h2 class="h2">{{ __('Gegevens') }}</h2>
            <p>{{ $customer->name }}<br>{{ $customer->email }}</p>
            @if($a = $customer->defaultAddress())<address>{!! nl2br(e($a->formatted())) !!}</address>@endif
            <a class="text-link" href="{{ route('account.addresses') }}">{{ __('Adressen beheren (:n)', ['n' => $customer->addresses->count()]) }}</a>
          </div>
        </aside>
      </div>
    </div>
  </div>
@endsection
