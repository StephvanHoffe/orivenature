{{-- Speelse productkaart met inhoudskeuze. Parameters: $product, $index --}}
@php
  $options = \App\Support\Storefront::cardOptions($product);
  foreach ($options as $i => $o) { $options[$i]['quick'] = route('products.quick', $product->handle).'?variant='.$o['variantId']; }
  $first = $product->variants->first();
  $multi = count($options) > 1;
@endphp
<article class="pcard" data-pcard data-handles="{{ $product->handle }}" data-reveal style="--d: {{ ($index ?? 0) * 0.1 }}s; --tone: {{ $product->tone ?: 'var(--sage)' }}">
  <script type="application/json" data-options>@json($options)</script>
  <div class="pcard__stage" data-tilt>
    <div class="pcard__badges">
      <span class="badge" data-bestseller @if(! $first?->is_bestseller) hidden @endif>{{ settings('products.bestseller_label') }}</span>
      @if(settings('products.badge'))<span class="badge">{{ settings('products.badge') }}</span>@endif
    </div>
    @if($product->chip)<span class="pcard__chip @if($product->chip_highlight) pcard__chip--new @endif">{{ $product->chip }}</span>@endif
    <span class="pcard__blob" aria-hidden="true"></span>
    <button class="pcard__peek" type="button" data-quickview="{{ $options[0]['quick'] ?? route('products.quick', $product->handle) }}">{{ __('snel bekijken') }}</button>
    @if($first && ($img = $first->imageUrl(600)))
      <img class="pcard__img" src="{{ $img }}" alt="{{ $first->displayTitle() }}" width="600" height="800" loading="lazy">
    @endif
  </div>
  <div class="pcard__body">
    <div class="pcard__head">
      <h3 class="pcard__title"><a href="{{ $product->url() }}" data-quickview="{{ $options[0]['quick'] ?? '' }}">{{ $product->title }}</a></h3>
      <p class="pcard__price"><span class="visually-hidden">{{ __('Prijs') }}</span><span data-price>{{ money($first?->price) }}</span></p>
    </div>
    <p class="pcard__desc">{{ $product->short_description ?: \Illuminate\Support\Str::words(strip_tags((string) $product->description), 22) }}</p>
    @if($multi)
      <div class="sizes" role="group" aria-label="{{ __('Kies inhoud') }}" data-active="0" @if(count($options) > 2) style="grid-template-columns: repeat({{ count($options) }}, 1fr)" @endif>
        @if(count($options) === 2)<span class="sizes__thumb" aria-hidden="true"></span>@endif
        @foreach($options as $i => $o)
          <button class="sizes__opt" type="button" data-size="{{ $i }}" aria-pressed="{{ $i === 0 ? 'true' : 'false' }}">{{ $o['label'] }}</button>
        @endforeach
      </div>
      <p class="pcard__save" data-save hidden></p>
    @endif
    <form class="pcard__form" method="post" action="{{ route('cart.add') }}" data-product-form>
      @csrf
      <input type="hidden" name="variant_id" value="{{ $first?->id }}" data-variant-input>
      <input type="hidden" name="quantity" value="1">
      <button class="btn btn--primary btn--block" type="submit" data-add @disabled(! $first?->isAvailable())>
        <svg width="18" height="18" aria-hidden="true"><use href="#i-bag"/></svg>
        <span data-add-label>{{ $first?->isAvailable() ? __('toevoegen aan winkelwagen') : __('uitverkocht') }}</span>
      </button>
    </form>
  </div>
</article>
