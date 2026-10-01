<footer class="checkout-footer">
  <div class="container checkout-footer__inner">
    @include('shop.partials.pay-icons')
    <nav class="checkout-footer__links" aria-label="{{ __('Voorwaarden') }}">
      @foreach(['checkout.terms_page' => __('Algemene voorwaarden'), 'checkout.privacy_page' => __('Privacy Policy')] as $key => $label)
        @if(settings($key))<a href="{{ url('/pages/'.settings($key)) }}">{{ $label }}</a>@endif
      @endforeach
      <a href="{{ url('/pages/contact') }}">{{ __('Contact') }}</a>
    </nav>
  </div>
</footer>
