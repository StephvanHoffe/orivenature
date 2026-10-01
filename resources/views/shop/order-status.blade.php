@extends('shop.layouts.app')
@section('title', __('Bestelling :number', ['number' => $order->name]))
@section('noindex', true)
@if($order->paid_at && ($justPlaced ?? false))
  @push('pixel')
    <script>fbq('track', 'Purchase', { value: {{ number_format($order->grandTotal() / 100, 2, '.', '') }}, currency: 'EUR', content_ids: @json($order->items->pluck('variant_id')->map(fn ($id) => (string) $id)), content_type: 'product', num_items: {{ $order->items->sum('quantity') }} }, { eventID: @json(\App\Services\Meta::purchaseEventId($order)) });</script>
  @endpush
@endif
@section('content')
  @php($pending = ! $order->paid_at && $order->status !== 'cancelled')
  <div class="section order-page">
    <div class="container container--narrow">
      <header class="order-page__head" data-reveal>
        @if($inAccount ?? false)<a class="article-page__back" href="{{ route('account.dashboard') }}"><svg width="16" height="16" aria-hidden="true"><use href="#i-arrow"/></svg> {{ __('Terug naar mijn account') }}</a>@endif
        <p class="eyebrow">{{ __('Bestelling :number', ['number' => $order->name]) }} · {{ $order->placed_at?->translatedFormat('j F Y') }}</p>
        @if($order->status === 'cancelled')
          <h1 class="h2 h2--xl">{{ __('Deze bestelling is geannuleerd') }}</h1>
        @elseif($pending)
          <h1 class="h2 h2--xl">{{ __('We wachten op je betaling') }}</h1>
          <p>{{ __('Zodra de betaling binnen is, ontvang je een bevestiging per e-mail. Deze pagina ververst vanzelf.') }}</p>
          @push('head')<meta http-equiv="refresh" content="6">@endpush
        @else
          <h1 class="h2 h2--xl">{{ __('Bedankt, :name!', ['name' => $order->shipping_address['first_name'] ?? '']) }}</h1>
          <p>{{ __('Je bestelling is ontvangen. We hebben een bevestiging gestuurd naar :email.', ['email' => $order->email]) }}</p>
        @endif
      </header>

      <ol class="order-steps" aria-label="{{ __('Status') }}">
        <li class="is-done">{{ __('Besteld') }}</li>
        <li class="{{ $order->paid_at ? 'is-done' : '' }}">{{ __('Betaald') }}</li>
        <li class="{{ $order->fulfillment_status !== 'unfulfilled' ? 'is-done' : '' }}">{{ __('Verzonden') }}</li>
      </ol>

      @foreach($order->fulfillments as $fulfillment)
        <div class="order-track">
          <svg width="22" height="22" aria-hidden="true"><use href="#i-truck"/></svg>
          <div>
            <strong>{{ __('Onderweg sinds :date', ['date' => $fulfillment->shipped_at?->translatedFormat('j F')]) }}</strong>
            @if($fulfillment->tracking_number)<br>{{ $fulfillment->tracking_company }} · {{ $fulfillment->tracking_number }}@endif
          </div>
          @if($link = $fulfillment->trackingLink())<a class="btn btn--primary" href="{{ $link }}" target="_blank" rel="noopener">{{ __('Volg je pakket') }}</a>@endif
        </div>
      @endforeach

      @if($order->paid_at && $order->points_earned > 0)
        <p class="loyalty-earn"><svg width="18" height="18" aria-hidden="true"><use href="#i-coin"/></svg>
          {{ __('Je hebt :points spaarpunten verdiend!', ['points' => $order->points_earned]) }}
          @if($order->customer && ! $order->customer->hasAccount()) <a class="text-link" href="{{ route('account.register') }}">{{ __('Maak een account om ze te gebruiken') }}</a>@endif
        </p>
      @endif

      <div class="order-card">
        <ul class="summary__lines">
          @foreach($order->items as $item)
            <li class="summary__line">
              <span class="summary__img">@if($img = $item->imageUrl(200))<img src="{{ $img }}" alt="" loading="lazy">@endif<b>{{ $item->quantity }}</b></span>
              <span class="summary__name">{{ $item->title }}@if($item->variant_title)<small>{{ $item->variant_title }}</small>@endif</span>
              <span class="summary__price">{{ money($item->price * $item->quantity) }}</span>
            </li>
          @endforeach
        </ul>
        <dl class="summary__totals">
          <div><dt>{{ __('Subtotaal') }}</dt><dd>{{ money($order->subtotal) }}</dd></div>
          @if($order->discount_total)<div class="summary__discount"><dt>{{ __('Korting') }} {{ $order->discount_code }}</dt><dd>-{{ money($order->discount_total) }}</dd></div>@endif
          <div><dt>{{ __('Verzending') }} ({{ $order->shipping_method }})</dt><dd>{{ $order->shipping_total ? money($order->shipping_total) : __('Gratis') }}</dd></div>
          @if($order->gift_card_used)<div class="summary__discount"><dt>{{ __('Cadeaubon') }}</dt><dd>-{{ money($order->gift_card_used) }}</dd></div>@endif
          @if($order->credit_used)<div class="summary__discount"><dt>{{ __('Shoptegoed') }}</dt><dd>-{{ money($order->credit_used) }}</dd></div>@endif
          <div class="summary__total"><dt>{{ __('Totaal') }}</dt><dd>{{ money($order->total) }}</dd></div>
          @if($order->refunded_total)<div><dt>{{ __('Terugbetaald') }}</dt><dd>-{{ money($order->refunded_total) }}</dd></div>@endif
        </dl>
        <p class="summary__tax">{{ __('Inclusief :amount btw', ['amount' => money($order->tax_total)]) }}</p>
      </div>

      <div class="account-grid account-grid--even">
        <div class="order-card"><h2 class="order-card__title">{{ __('Bezorgadres') }}</h2><address>{!! nl2br(e($order->formattedShippingAddress())) !!}</address></div>
        <div class="order-card"><h2 class="order-card__title">{{ __('Factuuradres') }}</h2><address>{!! nl2br(e($order->formattedBillingAddress())) !!}</address></div>
      </div>

      <p class="order-page__help">{{ __('Vragen over je bestelling?') }} <a class="text-link" href="mailto:{{ settings('store.email') }}">{{ settings('store.email') }}</a>@if($wa = \App\Support\Storefront::whatsappUrl()) · <a class="text-link" href="{{ $wa }}" target="_blank" rel="noopener">Whatsapp</a>@endif</p>
      <a class="btn btn--ghost" href="{{ route('home') }}">{{ __('Verder winkelen') }}</a>
    </div>
  </div>
@endsection
