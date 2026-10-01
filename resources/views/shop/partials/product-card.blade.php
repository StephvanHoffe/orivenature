{{-- Productkaart in collecties en zoekresultaten. Parameters: $product, $index --}}
@php
  $tones = ['var(--sage)', 'var(--mauve)', 'var(--mauve-soft)'];
  $variant = $product->defaultVariant();
@endphp
<article class="pcard pcard--grid" data-reveal style="--d: {{ (($index ?? 0) % 4) * 0.08 }}s; --tone: {{ $product->tone ?: $tones[($index ?? 0) % 3] }}">
  <a class="pcard__stage" href="{{ $product->url() }}" data-tilt>
    <span class="pcard__blob" aria-hidden="true"></span>
    @if(settings('products.badge'))<span class="pcard__badges"><span class="badge">{{ settings('products.badge') }}</span></span>@endif
    @if($img = $product->imageUrl(600))<img class="pcard__img" src="{{ $img }}" alt="{{ $product->title }}" width="600" height="800" loading="lazy">@endif
  </a>
  <div class="pcard__body">
    <div class="pcard__head">
      <h3 class="pcard__title"><a href="{{ $product->url() }}">{{ $product->title }}</a></h3>
      <p class="pcard__price">{{ $product->hasOnlyDefaultVariant() ? '' : __('vanaf').' ' }}{{ money($product->minPrice()) }}</p>
    </div>
    @if($product->hasOnlyDefaultVariant() && $variant)
      <form class="pcard__form" method="post" action="{{ route('cart.add') }}" data-product-form>
        @csrf
        <input type="hidden" name="variant_id" value="{{ $variant->id }}">
        <input type="hidden" name="quantity" value="1">
        <button class="btn btn--primary btn--block" type="submit" data-add @disabled(! $variant->isAvailable())>
          <svg width="18" height="18" aria-hidden="true"><use href="#i-bag"/></svg>
          <span data-add-label>{{ $variant->isAvailable() ? __('toevoegen aan winkelwagen') : __('uitverkocht') }}</span>
        </button>
      </form>
    @else
      <a class="btn btn--primary btn--block" href="{{ $product->url() }}" data-quickview="{{ route('products.quick', $product->handle) }}">{{ __('kies inhoud') }}</a>
    @endif
  </div>
</article>
