@php($order = $getRecord())
<div class="ob-lines">
  @foreach($order->items as $item)
    <div class="ob-line">
      <div class="ob-line__img">@if($url = $item->imageUrl(120))<img src="{{ $url }}" alt="">@endif</div>
      <div class="ob-line__info">
        <strong>{{ $item->title }}</strong>
        @if($item->variant_title)<span>{{ $item->variant_title }}</span>@endif
        @if($item->sku)<span class="ob-muted">SKU {{ $item->sku }}</span>@endif
        <span class="ob-chips">
          @if($item->fulfilled_quantity >= $item->quantity)<span class="ob-chip ob-chip--ok">verzonden</span>
          @elseif($item->fulfilled_quantity > 0)<span class="ob-chip">{{ $item->fulfilled_quantity }} van {{ $item->quantity }} verzonden</span>@endif
          @if($item->refunded_quantity > 0)<span class="ob-chip ob-chip--warn">{{ $item->refunded_quantity }} terugbetaald</span>@endif
        </span>
      </div>
      <div class="ob-line__qty">{{ money($item->price) }} × {{ $item->quantity }}</div>
      <div class="ob-line__total">{{ money($item->price * $item->quantity) }}</div>
    </div>
  @endforeach
  @foreach($order->fulfillments as $f)
    <div class="ob-ship">
      <span>📦 Verzonden {{ $f->shipped_at?->translatedFormat('j M Y H:i') }}@if($f->tracking_company) met {{ $f->tracking_company }}@endif</span>
      @if($f->tracking_number)
        @if($link = $f->trackingLink())<a href="{{ $link }}" target="_blank" rel="noopener">{{ $f->tracking_number }} ↗</a>@else<span>{{ $f->tracking_number }}</span>@endif
      @endif
    </div>
  @endforeach
</div>
