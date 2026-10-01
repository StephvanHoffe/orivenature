@extends('shop.account.layout')
@section('title', __('Spaarpunten'))
@php
  $enabled = \App\Services\Loyalty::enabled();
  $redeemable = \App\Services\Loyalty::redeemablePoints($customer);
  $block = max(1, (int) settings('loyalty.redeem_points', 100));
  $toNext = $block - ($customer->points_balance % $block);
  $canRedeem = $enabled && $redeemable >= max($block, (int) settings('loyalty.min_redeem'));
@endphp
@section('account')
  <header class="acct-head" data-reveal>
    <p class="eyebrow">{{ settings('loyalty.name') }}</p>
    <h1 class="h2 h2--xl">{{ __('Spaarpunten en tegoed') }}</h1>
  </header>

  <section class="loyalty__card loyalty-wallet" data-reveal>
    <div class="loyalty__intro">
      <div class="wallet">
        <div class="wallet__item"><span>{{ __('Spaarpunten') }}</span><strong>{{ number_format($customer->points_balance, 0, ',', '.') }}</strong></div>
        <div class="wallet__item"><span>{{ __('Shoptegoed') }}</span><strong>{{ money($customer->credit_balance) }}</strong></div>
      </div>
      @if($canRedeem)
        <form method="post" action="{{ route('account.redeem') }}" class="redeem">
          @csrf
          <label class="field"><span class="field__label">{{ __('Punten inwisselen voor tegoed') }}</span>
            <select class="field__input" name="points">
              @for($p = $redeemable; $p >= $block; $p -= $block)
                <option value="{{ $p }}">{{ number_format($p, 0, ',', '.') }} {{ __('punten') }} → {{ money(\App\Services\Loyalty::creditFor($p)) }}</option>
              @endfor
            </select>
          </label>
          <button class="btn btn--primary" type="submit">{{ __('Inwisselen') }}</button>
        </form>
      @elseif($enabled)
        <div class="progress" role="img" aria-label="{{ __('Nog :n punten tot je volgende beloning', ['n' => $toNext]) }}">
          <span style="width: {{ min(100, round(($customer->points_balance % $block) / $block * 100)) }}%"></span>
        </div>
        <p class="field__hint">{{ __('Nog :n punten tot :value shoptegoed.', ['n' => $toNext, 'value' => money((int) settings('loyalty.redeem_value'))]) }}</p>
      @endif
      @error('points')<p class="form-error">{{ $message }}</p>@enderror
      @if($customer->credit_balance > 0)<p class="field__hint">{{ __('Je tegoed wordt bij de kassa automatisch van je bestelling afgetrokken.') }}</p>@endif
    </div>
    @if($enabled)
      <ol class="acct-how">
        <li><svg width="20" height="20" aria-hidden="true"><use href="#i-bag"/></svg><span><strong>{{ __('Sparen') }}</strong>{{ __(':points punt per besteed euro, automatisch bij elke bestelling.', ['points' => settings('loyalty.points_per_euro')]) }}</span></li>
        <li><svg width="20" height="20" aria-hidden="true"><use href="#i-coin"/></svg><span><strong>{{ __('Inwisselen') }}</strong>{{ \App\Services\Loyalty::rateText() }}.</span></li>
        <li><svg width="20" height="20" aria-hidden="true"><use href="#i-gift"/></svg><span><strong>{{ __('Besteden') }}</strong>{{ __('Je tegoed gebruik je bij je volgende bestelling.') }}</span></li>
      </ol>
    @endif
  </section>

  <div class="acct-pair acct-pair--top">
    @if($enabled || $points->total())
      <section class="acct-card" data-reveal>
        <h2 class="acct-card__title">{{ __('Puntengeschiedenis') }}</h2>
        <ul class="ledger">
          @forelse($points as $t)
            <li>
              <span>{{ $t->description ?: (\App\Models\LoyaltyTransaction::TYPES[$t->type] ?? $t->type) }}<small>{{ $t->created_at->translatedFormat('j F Y') }}</small></span>
              <strong class="{{ $t->points < 0 ? 'is-neg' : 'is-pos' }}">{{ $t->points > 0 ? '+' : '' }}{{ number_format($t->points, 0, ',', '.') }}</strong>
            </li>
          @empty
            <li class="ledger__empty">{{ __('Nog geen punten. Bij je eerste bestelling spaar je mee.') }}</li>
          @endforelse
        </ul>
        @include('shop.partials.pagination', ['paginator' => $points])
      </section>
    @endif
    <section class="acct-card" data-reveal>
      <h2 class="acct-card__title">{{ __('Tegoed') }}</h2>
      <ul class="ledger">
        @forelse($credit as $t)
          <li>
            <span>{{ $t->description ?: (\App\Models\CreditTransaction::TYPES[$t->type] ?? $t->type) }}<small>{{ $t->created_at->translatedFormat('j F Y') }}</small></span>
            <strong class="{{ $t->amount < 0 ? 'is-neg' : 'is-pos' }}">{{ $t->amount > 0 ? '+' : '−' }}{{ money(abs($t->amount)) }}</strong>
          </li>
        @empty
          <li class="ledger__empty">{{ __('Nog geen tegoed. Wissel punten in om tegoed te krijgen.') }}</li>
        @endforelse
      </ul>
      @include('shop.partials.pagination', ['paginator' => $credit])
    </section>
  </div>
@endsection
