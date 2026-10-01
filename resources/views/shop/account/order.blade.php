@extends('shop.account.layout')
@section('title', __('Bestelling :number', ['number' => $order->name]))
@php
  $tracking = $order->fulfillments->map->trackingLink()->filter()->first();
  $subjects = \App\Http\Controllers\Shop\AccountOrderController::SUBJECTS;
  $canReorder = $order->items->contains(fn ($i) => $i->variant && $i->variant->product?->status === 'active');
@endphp
@section('account')
  <a class="acct-back" href="{{ route('account.orders') }}"><svg width="16" height="16" aria-hidden="true"><use href="#i-arrow"/></svg> {{ __('Alle bestellingen') }}</a>
  <header class="acct-head acct-head--order" data-reveal>
    <div>
      <p class="eyebrow">{{ __('Besteld op :date', ['date' => $order->placed_at?->translatedFormat('j F Y')]) }}</p>
      <h1 class="h2 h2--xl">{{ __('Bestelling :number', ['number' => $order->name]) }}</h1>
    </div>
    <div class="acct-head__status">@include('shop.account.partials.status')</div>
  </header>

  <div class="acct-actions" data-reveal>
    @if($order->checkoutUrl())<a class="btn btn--primary" href="{{ route('account.orders.pay', $order->number) }}"><svg width="18" height="18" aria-hidden="true"><use href="#i-card"/></svg> {{ __('Betaling afronden') }}</a>@endif
    @if($tracking)<a class="btn btn--primary" href="{{ $tracking }}" target="_blank" rel="noopener"><svg width="18" height="18" aria-hidden="true"><use href="#i-truck"/></svg> {{ __('Volg je pakket') }}</a>@endif
    @if($order->isPaid())<a class="btn btn--ghost" href="{{ route('account.orders.invoice', $order->number) }}"><svg width="18" height="18" aria-hidden="true"><use href="#i-download"/></svg> {{ __('Factuur (PDF)') }}</a>@endif
    @if($canReorder)
      <form method="post" action="{{ route('account.orders.reorder', $order->number) }}">@csrf<button class="btn btn--ghost" type="submit"><svg width="18" height="18" aria-hidden="true"><use href="#i-repeat"/></svg> {{ __('Opnieuw bestellen') }}</button></form>
    @endif
    <a class="btn btn--ghost" href="#vraag"><svg width="18" height="18" aria-hidden="true"><use href="#i-chat"/></svg> {{ __('Vraag stellen') }}</a>
  </div>

  <div class="acct-order">
    <div class="acct-order__main">
      <section class="acct-card" aria-labelledby="status-title" data-reveal>
        <h2 class="acct-card__title" id="status-title">{{ __('Status') }}</h2>
        @include('shop.partials.order-timeline')
      </section>

      <section class="acct-card" aria-labelledby="items-title" data-reveal>
        <h2 class="acct-card__title" id="items-title">{{ trans_choice(':count artikel|:count artikelen', (int) $order->items->sum('quantity')) }}</h2>
        <ul class="oitems">
          @foreach($order->items as $item)
            @php($product = $item->variant?->product)
            <li class="oitem">
              <span class="oitem__img">@if($img = $item->imageUrl(200))<img src="{{ $img }}" alt="" loading="lazy">@endif</span>
              <span class="oitem__info">
                @if($product && $product->status === 'active')
                  <a class="oitem__title" href="{{ $product->url($item->variant) }}">{{ $item->title }}</a>
                @else
                  <span class="oitem__title">{{ $item->title }}</span>
                @endif
                @if($item->variant_title)<small>{{ $item->variant_title }}</small>@endif
                <span class="oitem__chips">
                  @if($item->fulfilled_quantity >= $item->quantity)<span class="ostatus ostatus--done">{{ __('Verzonden') }}</span>
                  @elseif($item->fulfilled_quantity > 0)<span class="ostatus ostatus--busy">{{ __(':n van :total verzonden', ['n' => $item->fulfilled_quantity, 'total' => $item->quantity]) }}</span>@endif
                  @if($item->refunded_quantity > 0)<span class="ostatus ostatus--off">{{ __(':n terugbetaald', ['n' => $item->refunded_quantity]) }}</span>@endif
                </span>
              </span>
              <span class="oitem__qty">{{ $item->quantity }} × {{ money($item->price) }}</span>
              <strong class="oitem__price">{{ money($item->price * $item->quantity) }}</strong>
            </li>
          @endforeach
        </ul>
        <dl class="summary__totals">
          <div><dt>{{ __('Subtotaal') }}</dt><dd>{{ money($order->subtotal) }}</dd></div>
          @if($order->discount_total)<div class="summary__discount"><dt>{{ __('Korting') }} @if($order->discount_code)<span class="code-chip">{{ $order->discount_code }}</span>@endif</dt><dd>-{{ money($order->discount_total) }}</dd></div>@endif
          <div><dt>{{ __('Verzending') }}@if($order->shipping_method) <small>({{ $order->shipping_method }})</small>@endif</dt><dd>{{ $order->shipping_total ? money($order->shipping_total) : __('Gratis') }}</dd></div>
          @if($order->gift_card_used)<div class="summary__discount"><dt>{{ __('Cadeaubon') }}</dt><dd>-{{ money($order->gift_card_used) }}</dd></div>@endif
          @if($order->credit_used)<div class="summary__discount"><dt>{{ __('Shoptegoed') }}</dt><dd>-{{ money($order->credit_used) }}</dd></div>@endif
          <div class="summary__total"><dt>{{ __('Totaal') }}</dt><dd>{{ money($order->total) }}</dd></div>
          @foreach($order->refunds->sortBy('created_at') as $refund)
            <div><dt>{{ __('Terugbetaald op :date', ['date' => $refund->created_at->translatedFormat('j M')]) }}@if($refund->reason) <small>({{ $refund->reason }})</small>@endif</dt><dd>-{{ money($refund->amount) }}</dd></div>
          @endforeach
        </dl>
        <p class="summary__tax">{{ __('Inclusief :amount btw', ['amount' => money($order->tax_total)]) }}@if($method = $order->paymentMethodLabel()) · {{ $method }}@endif</p>
      </section>
    </div>

    <aside class="acct-order__side">
      <section class="acct-card" data-reveal>
        <h2 class="acct-card__title">{{ __('Bezorgadres') }}</h2>
        <address class="acct-address">{!! nl2br(e($order->formattedShippingAddress())) !!}</address>
        @if($order->shipping_method)<p class="acct-muted"><svg width="16" height="16" aria-hidden="true"><use href="#i-truck"/></svg> {{ $order->shipping_method }}@if($order->shippingRate?->description) · {{ $order->shippingRate->description }}@endif</p>@endif
      </section>
      <section class="acct-card" data-reveal>
        <h2 class="acct-card__title">{{ __('Factuuradres') }}</h2>
        <address class="acct-address">{!! nl2br(e($order->formattedBillingAddress())) !!}</address>
      </section>
      @if(\App\Services\Loyalty::enabled() && ($order->points_earned > 0 || $order->isInProgress()))
        <section class="acct-card acct-card--sage" data-reveal>
          <h2 class="acct-card__title"><svg width="18" height="18" aria-hidden="true"><use href="#i-coin"/></svg> {{ __('Spaarpunten') }}</h2>
          @if($order->points_awarded_at && $order->points_earned > 0)
            <p>{{ __('Met deze bestelling heb je :points punten gespaard.', ['points' => $order->points_earned]) }}</p>
          @elseif($order->status !== 'cancelled')
            <p>{{ __('Je ontvangt :points punten zodra de bestelling :when is.', ['points' => \App\Services\Loyalty::pointsForOrder($order), 'when' => settings('loyalty.award_on') === 'fulfilled' ? __('verzonden') : __('betaald')]) }}</p>
          @endif
          <a class="text-link" href="{{ route('account.rewards') }}">{{ __('Naar mijn spaarpunten') }}</a>
        </section>
      @endif
    </aside>
  </div>

  <section class="acct-card acct-question" id="vraag" aria-labelledby="question-title" data-reveal>
    <div>
      <h2 class="acct-card__title" id="question-title">{{ __('Vraag of probleem met deze bestelling?') }}</h2>
      <p class="acct-muted">{{ __('Stuur ons een bericht; het bestelnummer sturen we automatisch mee. We reageren per e-mail, meestal dezelfde dag.') }}
        @if($wa = \App\Support\Storefront::whatsappUrl()) {{ __('Liever direct contact?') }} <a class="text-link" href="{{ $wa }}?text={{ rawurlencode(__('Hoi! Ik heb een vraag over bestelling :number', ['number' => $order->name])) }}" target="_blank" rel="noopener">WhatsApp</a>@endif
      </p>
    </div>
    <form method="post" action="{{ route('account.orders.question', $order->number) }}" class="form-stack">
      @csrf
      @if($errors->any())<div class="form-error" role="alert">{{ $errors->first() }}</div>@endif
      <label class="field"><span class="field__label">{{ __('Onderwerp') }}</span>
        <select class="field__input" name="subject">@foreach($subjects as $key => $label)<option value="{{ $key }}" @selected(old('subject') === $key)>{{ $label }}</option>@endforeach</select></label>
      <label class="field"><span class="field__label">{{ __('Bericht') }}</span>
        <textarea class="field__input" name="message" rows="4" required minlength="5">{{ old('message') }}</textarea></label>
      <button class="btn btn--primary" type="submit">{{ __('Versturen') }} <svg width="18" height="18" aria-hidden="true"><use href="#i-arrow"/></svg></button>
    </form>
  </section>
@endsection
