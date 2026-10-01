@extends('shop.layouts.app')
@section('title', __('Winkelwagen'))
@section('template', 'cart')
@section('noindex', true)
@section('content')
  <div class="section cart-page">
    <div class="container container--narrow">
      <header class="page-head" data-reveal><h1 class="h2 h2--xl">{{ __('Winkelwagen') }}</h1></header>
      @if($lines->isEmpty())
        <div class="cart-empty">
          <h2>{{ __('Je winkelwagen is leeg') }}</h2>
          <a class="btn btn--primary" href="{{ url('/collections/all') }}">{{ __('Doorgaan met winkelen') }}</a>
        </div>
      @else
        <form action="{{ route('cart.update') }}" method="post" class="cart-page__form">
          @csrf
          <div class="cart-page__lines">
            @foreach($lines as $line)
              <div class="line line--page">
                <a class="line__img" href="{{ $line->product()->url($line->variant) }}">@if($img = $line->variant->imageUrl(200))<img src="{{ $img }}" alt="" loading="lazy">@endif</a>
                <div>
                  <p class="line__title"><a href="{{ $line->product()->url($line->variant) }}">{{ $line->title() }}</a></p>
                  @if($line->variantTitle())<p class="line__variant">{{ $line->variantTitle() }}</p>@endif
                  <p class="line__price">{{ money($line->unitPrice()) }}</p>
                </div>
                <div class="line__side">
                  <label class="visually-hidden" for="qty-{{ $line->variant->id }}">{{ __('Aantal :title', ['title' => $line->title()]) }}</label>
                  <input class="field__input qty__input--page" id="qty-{{ $line->variant->id }}" type="number" name="quantities[{{ $line->variant->id }}]" value="{{ $line->quantity }}" min="0" max="99">
                  <button class="line__remove" type="submit" name="quantities[{{ $line->variant->id }}]" value="0" aria-label="{{ __('Verwijder :title', ['title' => $line->title()]) }}"><svg width="18" height="18" aria-hidden="true"><use href="#i-trash"/></svg></button>
                </div>
              </div>
            @endforeach
          </div>
          <div class="cart-page__summary">
            <div class="cart__total"><span>{{ __('Subtotaal') }}</span><strong>{{ money($pricing->subtotal) }}</strong></div>
            @if($earn = \App\Services\Loyalty::earnText($pricing->subtotal))<p class="loyalty-earn loyalty-earn--cart"><svg width="18" height="18" aria-hidden="true"><use href="#i-coin"/></svg> {{ $earn }}</p>@endif
            @if(settings('cart.note'))<p class="cart__note">{{ settings('cart.note') }}</p>@endif
            <div class="cart-page__actions">
              <button class="btn btn--ghost" type="submit">{{ __('bijwerken') }}</button>
              <button class="btn btn--primary btn--lg" type="submit" name="checkout" value="1">{{ __('afrekenen') }} <svg width="18" height="18" aria-hidden="true"><use href="#i-arrow"/></svg></button>
            </div>
            @include('shop.partials.pay-icons')
          </div>
        </form>
      @endif
    </div>
  </div>
@endsection
