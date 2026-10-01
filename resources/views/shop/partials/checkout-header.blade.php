<header class="checkout-header">
  <div class="container checkout-header__inner">
    <a class="text-link checkout-header__back" href="{{ route('cart') }}">
      <svg width="18" height="18" aria-hidden="true" style="transform:rotate(180deg)"><use href="#i-arrow"/></svg>
      <span>{{ __('Winkelwagen') }}</span>
    </a>
    <a class="checkout-header__logo" href="{{ url('/') }}">@include('shop.partials.logo', ['class' => 'logo'])</a>
    <p class="checkout-header__secure">
      <svg width="18" height="18" aria-hidden="true"><use href="#i-lock"/></svg>
      <span>{{ __('Veilig afrekenen') }}</span>
    </p>
  </div>
</header>
