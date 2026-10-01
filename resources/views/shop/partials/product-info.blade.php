{{-- Productweergave voor de productpagina en "snel bekijken". Parameters: $product, $variant, $quick --}}
@php
  $options = \App\Support\Storefront::cardOptions($product);
  $index = max(0, $product->variants->search(fn ($v) => $v->id === $variant?->id));
  $current = $options[$index] ?? null;
  $mainImage = $variant?->image ?? $product->images->first();
  $quick = $quick ?? false;
@endphp
<div class="qv @if(! $quick) product-page__inner @endif" data-product>
  <script type="application/json" data-product-options>@json($options)</script>
  <div class="qv__gallery" data-gallery>
    <div class="qv__main" style="--tone: {{ $product->tone ?: 'var(--sage)' }}">
      @if($mainImage)
        <img class="qv__main-img @if($mainImage->isPackshot()) is-pack @endif" src="{{ $mainImage->url(1200) }}" srcset="{{ \App\Support\Media::srcset($mainImage->path, [600, 900, 1200]) }}" sizes="(min-width: 800px) 50vw, 100vw" alt="{{ $mainImage->alt ?: $product->title }}">
      @endif
      <div class="pcard__badges">
        <span class="badge" data-bestseller @if(! ($current['bestseller'] ?? false)) hidden @endif>{{ settings('products.bestseller_label') }}</span>
        @if(settings('products.badge'))<span class="badge">{{ settings('products.badge') }}</span>@endif
      </div>
    </div>
    @if($product->images->count() > 1)
      <div class="qv__thumbs" role="group" aria-label="{{ __('Afbeeldingen') }}">
        @foreach($product->images as $image)
          <button class="qv__thumb @if($image->isPackshot()) is-pack @endif" style="--tone: {{ $product->tone ?: 'var(--sage)' }}" type="button" data-thumb="{{ $image->url(1200) }}" data-alt="{{ $image->alt ?: $product->title }}" data-pack="{{ $image->isPackshot() ? 'true' : 'false' }}" aria-label="{{ __('Afbeelding :n', ['n' => $loop->iteration]) }}" aria-current="{{ $image->id === $mainImage?->id ? 'true' : 'false' }}">
            <img src="{{ $image->url(200) }}" alt="" loading="lazy">
          </button>
        @endforeach
      </div>
    @endif
  </div>

  <div class="qv__info">
    @if($quick)<h2 class="qv__title">{{ $product->title }}</h2>@else<h1 class="qv__title">{{ $product->title }}</h1>@endif
    <p class="qv__price"><span class="visually-hidden">{{ __('Prijs') }}</span><span data-product-price>{{ $current['price'] ?? '' }}@if($current['compareAt'] ?? null) <s>{{ $current['compareAt'] }}</s>@endif</span></p>

    @if(count($options) > 1)
      <div class="size-links" role="group" aria-label="{{ __('Kies inhoud') }}">
        @foreach($options as $i => $o)
          <button class="size-links__opt" type="button" data-variant-index="{{ $i }}" aria-current="{{ $i === $index ? 'true' : 'false' }}">{{ $o['label'] }}</button>
        @endforeach
      </div>
      <p class="pcard__save" data-save @if(empty($current['save'])) hidden @endif>{{ $current['save'] ?? '' }}</p>
    @endif

    @if($product->description)
      <div class="qv__desc rte">{!! $product->description !!}</div>
    @endif

    <form class="qv__form" method="post" action="{{ route('cart.add') }}" data-product-form>
      @csrf
      <input type="hidden" name="variant_id" value="{{ $variant?->id }}">
      <div class="qv__buy">
        <div class="qty" role="group" aria-label="{{ __('Aantal') }}">
          <button type="button" data-qty-step="-1" aria-label="{{ __('Eén minder') }}"><svg width="14" height="14" aria-hidden="true"><use href="#i-minus"/></svg></button>
          <input class="qty__input" type="number" name="quantity" value="1" min="1" max="99" aria-label="{{ __('Aantal') }}">
          <button type="button" data-qty-step="1" aria-label="{{ __('Eén meer') }}"><svg width="14" height="14" aria-hidden="true"><use href="#i-plus"/></svg></button>
        </div>
        <button class="btn btn--primary btn--lg" type="submit" data-add @disabled(! ($current['available'] ?? false))>
          <svg width="18" height="18" aria-hidden="true"><use href="#i-bag"/></svg>
          <span data-add-label>{{ ($current['available'] ?? false) ? __('toevoegen aan winkelwagen') : __('uitverkocht') }}</span>
        </button>
      </div>
      <p class="form-error" data-form-error role="alert" hidden></p>
    </form>

    @if(! empty($current['points']))
      <p class="loyalty-earn"><svg width="18" height="18" aria-hidden="true"><use href="#i-coin"/></svg> <span data-loyalty-text>{{ $current['points'] }}</span></p>
    @endif

    @if($perks = array_filter((array) settings('products.perks'), fn ($p) => ! empty($p['text'])))
      <ul class="qv__perks">
        @foreach($perks as $perk)
          <li><svg width="18" height="18" aria-hidden="true"><use href="#i-{{ $perk['icon'] ?? 'check' }}"/></svg>{{ $perk['text'] }}</li>
        @endforeach
      </ul>
    @endif

    @if($quick)
      <a class="qv__more" href="{{ $product->url($variant) }}">{{ __('bekijk volledige productpagina') }}</a>
    @endif
  </div>
</div>
