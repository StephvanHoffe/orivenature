@php
  $items = $order->items;
  $count = (int) $items->sum('quantity');
  $tracking = $order->fulfillments->map->trackingLink()->filter()->first();
  $steps = collect($order->timeline())->whereIn('state', ['done', 'current', 'todo'])->take(4);
@endphp
<article class="ocard">
  <header class="ocard__head">
    <div>
      <a class="ocard__num" href="{{ route('account.orders.show', $order->number) }}">{{ __('Bestelling :number', ['number' => $order->name]) }}</a>
      <p class="ocard__date">{{ $order->placed_at?->translatedFormat('j F Y') }}</p>
    </div>
    <div class="ocard__status">@include('shop.account.partials.status')</div>
  </header>
  <a class="ocard__body" href="{{ route('account.orders.show', $order->number) }}" aria-label="{{ __('Bekijk bestelling :number', ['number' => $order->name]) }}">
    <span class="ocard__thumbs">
      @foreach($items->take(4) as $item)
        <span class="ocard__thumb">@if($img = $item->imageUrl(200))<img src="{{ $img }}" alt="" loading="lazy">@endif @if($item->quantity > 1)<b>{{ $item->quantity }}</b>@endif</span>
      @endforeach
      @if($items->count() > 4)<span class="ocard__thumb ocard__more">+{{ $items->count() - 4 }}</span>@endif
    </span>
    <span class="ocard__meta">
      <span>{{ trans_choice(':count artikel|:count artikelen', $count) }}</span>
      <strong>{{ money($order->grandTotal()) }}</strong>
    </span>
  </a>
  @if($order->status !== 'cancelled' && $order->financial_status !== 'refunded')
    <div class="ocard__progress" aria-hidden="true">
      @foreach($steps as $step)<span class="is-{{ $step['state'] }}"></span>@endforeach
    </div>
  @endif
  <footer class="ocard__actions">
    <a class="btn btn--ghost btn--sm" href="{{ route('account.orders.show', $order->number) }}">{{ __('Bekijken') }}</a>
    @if($tracking)<a class="btn btn--primary btn--sm" href="{{ $tracking }}" target="_blank" rel="noopener"><svg width="16" height="16" aria-hidden="true"><use href="#i-truck"/></svg> {{ __('Volg pakket') }}</a>@endif
    @if($order->checkoutUrl())<a class="btn btn--primary btn--sm" href="{{ route('account.orders.pay', $order->number) }}"><svg width="16" height="16" aria-hidden="true"><use href="#i-card"/></svg> {{ __('Betaling afronden') }}</a>@endif
    @if(! $order->isInProgress() || $order->status === 'cancelled')
      <form method="post" action="{{ route('account.orders.reorder', $order->number) }}">@csrf<button class="btn btn--ghost btn--sm" type="submit"><svg width="16" height="16" aria-hidden="true"><use href="#i-repeat"/></svg> {{ __('Opnieuw bestellen') }}</button></form>
    @endif
  </footer>
</article>
