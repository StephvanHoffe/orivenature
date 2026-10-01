<div class="summary">
  <h2 class="summary__title">{{ __('Je bestelling') }}</h2>
  <ul class="summary__lines">
    @foreach($pricing->lines as $line)
      <li class="summary__line">
        <span class="summary__img">@if($img = $line->variant->imageUrl(200))<img src="{{ $img }}" alt="" loading="lazy">@endif<b>{{ $line->quantity }}</b></span>
        <span class="summary__name">{{ $line->title() }}@if($line->variantTitle())<small>{{ $line->variantTitle() }}</small>@endif</span>
        <span class="summary__price">{{ money($line->total()) }}</span>
      </li>
    @endforeach
  </ul>
  <dl class="summary__totals">
    <div><dt>{{ __('Subtotaal') }}</dt><dd>{{ money($pricing->subtotal) }}</dd></div>
    @if($pricing->discount)
      <div class="summary__discount"><dt>{{ __('Korting') }} <span class="code-chip">{{ $pricing->discount->code ?: $pricing->discount->title }} @if($pricing->discount->code)<button type="button" data-remove-code="discount_code" aria-label="{{ __('Kortingscode verwijderen') }}">×</button>@endif</span></dt><dd>-{{ money($pricing->discountTotal) }}</dd></div>
    @endif
    <div><dt>{{ __('Verzending') }}</dt><dd>{{ $pricing->shippingRate ? ($pricing->shippingTotal ? money($pricing->shippingTotal) : __('Gratis')) : '—' }}</dd></div>
    @if($pricing->giftCardUsed)
      <div class="summary__discount"><dt>{{ __('Cadeaubon') }} <span class="code-chip">{{ $pricing->giftCard->maskedCode() }} <button type="button" data-remove-code="gift_card" aria-label="{{ __('Cadeaubon verwijderen') }}">×</button></span></dt><dd>-{{ money($pricing->giftCardUsed) }}</dd></div>
    @endif
    @if($pricing->creditUsed)
      <div class="summary__discount"><dt>{{ __('Shoptegoed') }}</dt><dd>-{{ money($pricing->creditUsed) }}</dd></div>
    @endif
    <div class="summary__total"><dt>{{ __('Totaal') }}</dt><dd>{{ money($pricing->total) }}</dd></div>
  </dl>
  <p class="summary__tax">{{ __('Inclusief :amount btw', ['amount' => money($pricing->taxTotal)]) }}</p>
  @if($earn = \App\Services\Loyalty::earnText($pricing->subtotal - $pricing->discountTotal - $pricing->creditUsed))
    <p class="loyalty-earn loyalty-earn--cart"><svg width="18" height="18" aria-hidden="true"><use href="#i-coin"/></svg> {{ $earn }}</p>
  @endif
  <ul class="summary__perks">
    @foreach(array_filter((array) settings('cart.perks')) as $perk)<li><svg width="16" height="16" aria-hidden="true"><use href="#i-check"/></svg> {{ $perk }}</li>@endforeach
  </ul>
</div>
