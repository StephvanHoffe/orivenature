@extends('shop.account.layout')
@section('title', __('Bestellingen'))
@section('account')
  <header class="acct-head" data-reveal>
    <p class="eyebrow">{{ __('Mijn account') }}</p>
    <h1 class="h2 h2--xl">{{ __('Bestellingen') }}</h1>
  </header>
  <nav class="acct-filter" aria-label="{{ __('Filter') }}">
    @foreach([null => __('Alle'), 'lopend' => __('Lopend'), 'afgerond' => __('Afgerond')] as $key => $label)
      <a href="{{ route('account.orders', array_filter(['filter' => $key])) }}" @if($filter === ($key ?: null)) aria-current="true" @endif>{{ $label }}</a>
    @endforeach
  </nav>
  @if($orders->isEmpty())
    <div class="acct-empty">
      <svg width="34" height="34" aria-hidden="true"><use href="#i-bag"/></svg>
      <p>{{ $filter ? __('Geen bestellingen in deze lijst.') : __('Je hebt nog geen bestellingen geplaatst.') }}</p>
      @unless($filter)<a class="btn btn--primary" href="{{ url('/collections/all') }}">{{ __('Ontdek onze essences') }} <svg width="18" height="18" aria-hidden="true"><use href="#i-arrow"/></svg></a>@endunless
    </div>
  @else
    <div class="ocards">
      @foreach($orders as $order)
        @include('shop.account.partials.order-card')
      @endforeach
    </div>
    @include('shop.partials.pagination', ['paginator' => $orders])
  @endif
@endsection
