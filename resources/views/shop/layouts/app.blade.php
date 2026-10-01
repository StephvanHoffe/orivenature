<!doctype html>
<html lang="nl" class="no-js">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="theme-color" content="#52572e">
  @php
    $pageTitle = trim($__env->yieldContent('title'));
    $pageTitle = $pageTitle ? $pageTitle.' – '.settings('store.name') : settings('seo.title');
    $pageDescription = trim($__env->yieldContent('description')) ?: settings('seo.description');
    $pageImage = trim($__env->yieldContent('image')) ?: (settings('seo.og_image') ? \App\Support\Media::url(settings('seo.og_image'), 1200) : null);
  @endphp
  <title>{{ $pageTitle }}</title>
  <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($pageDescription), 300) }}">
  <link rel="canonical" href="{{ url()->current() }}">
  @hasSection('noindex')<meta name="robots" content="noindex">@endif
  @if(settings('meta.domain_verification'))<meta name="facebook-domain-verification" content="{{ settings('meta.domain_verification') }}">@endif
  <meta property="og:site_name" content="{{ settings('store.name') }}">
  <meta property="og:type" content="@yield('og_type', 'website')">
  <meta property="og:title" content="{{ $pageTitle }}">
  <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($pageDescription), 300) }}">
  <meta property="og:url" content="{{ url()->current() }}">
  @if($pageImage)<meta property="og:image" content="{{ $pageImage }}">@endif
  <meta name="twitter:card" content="summary_large_image">
  @if(settings('store.favicon'))
    <link rel="icon" href="{{ \App\Support\Media::url(settings('store.favicon'), 120) }}">
  @else
    <link rel="icon" href="{{ asset('storefront/img/favicon.png') }}">
  @endif

  <link rel="preload" href="{{ asset('storefront/fonts/Raleway-normal-latin-faccb3.woff2') }}" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="{{ asset('storefront/fonts/LibreBaskerville-normal-latin-eeb780.woff2') }}" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="{{ asset('storefront/fonts/fonts.css') }}">
  <link rel="stylesheet" href="{{ asset('storefront/css/shop.css') }}?v={{ filemtime(public_path('storefront/css/shop.css')) }}">
  @stack('head')

  <script>
    document.documentElement.classList.replace('no-js', 'js');
    window.shop = {
      routes: { cart: @json(route('cart')), cartAdd: @json(route('cart.add')), cartChange: @json(route('cart.change')), quote: @json(route('checkout.quote')), newsletter: @json(route('newsletter')) },
      strings: { added: @json(__('toegevoegd!')), error: @json(__('Er ging iets mis. Probeer het opnieuw.')), soldOut: @json(__('uitverkocht')), addToCart: @json(__('toevoegen aan winkelwagen')) },
      openCartOnAdd: @json((bool) settings('cart.open_on_add'))
    };
  </script>
  <script src="{{ asset('storefront/js/shop.js') }}?v={{ filemtime(public_path('storefront/js/shop.js')) }}" defer></script>

  @if($pixel = \App\Services\Meta::pixelId())
    <script>
      !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
      fbq('init', @json($pixel));
      fbq('track', 'PageView');
    </script>
    @stack('pixel')
  @endif
</head>
<body class="template-@yield('template', 'page')">
  @include('shop.partials.icons')
  <a class="skip-link" href="#main">{{ __('Naar inhoud') }}</a>

  @php($isCheckout = trim($__env->yieldContent('template')) === 'checkout')
  @include($isCheckout ? 'shop.partials.checkout-header' : 'shop.partials.header')

  <main id="main" tabindex="-1">
    @if(session('status'))
      <div class="container"><p class="flash" role="status">{{ session('status') }}</p></div>
    @endif
    @yield('content')
  </main>

  @include($isCheckout ? 'shop.partials.checkout-footer' : 'shop.partials.footer')

  <dialog class="drawer drawer--right cart" id="cart" aria-labelledby="cart-title" data-drawer="cart">
    <div class="cart-host" data-cart-host>
      @include('shop.partials.cart-drawer', app(\App\Http\Controllers\Shop\CartController::class)->drawerData())
    </div>
  </dialog>

  <dialog class="modal" id="quickview" aria-label="{{ __('snel bekijken') }}" data-drawer="quickview">
    <button class="icon-btn modal__close" type="button" data-close aria-label="{{ __('Sluiten') }}">
      <svg width="24" height="24" aria-hidden="true"><use href="#i-close"/></svg>
    </button>
    <div class="qv-host" data-qv></div>
  </dialog>

  @if(settings('store.whatsapp_bubble') && ($wa = \App\Support\Storefront::whatsappUrl()))
    <a class="wa-bubble" href="{{ $wa }}" target="_blank" rel="noopener" aria-label="{{ __('Stuur ons een bericht via Whatsapp') }}" data-wa>
      <svg width="26" height="26" aria-hidden="true"><use href="#i-whatsapp"/></svg>
      @if(settings('store.whatsapp_tip'))<span class="wa-bubble__tip">{{ settings('store.whatsapp_tip') }}</span>@endif
    </a>
  @endif

  @stack('scripts')
</body>
</html>
