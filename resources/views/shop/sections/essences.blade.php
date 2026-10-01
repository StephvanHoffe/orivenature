@php($cards = collect($data['products'] ?? [])->map(fn ($h) => $products->get($h))->filter())
<section class="section products" id="essence" aria-labelledby="essence-title">
  <div class="container">
    <header class="section-head" data-reveal>
      <h2 class="h2" id="essence-title">{{ $data['heading'] ?? '' }} @if(!empty($data['heading_italic']))<em>{{ $data['heading_italic'] }}</em>@endif</h2>
      @if(!empty($data['button_label']))<a class="btn btn--ghost" href="{{ $data['button_link'] ?? '/collections/all' }}">{{ $data['button_label'] }} <svg width="16" height="16" aria-hidden="true"><use href="#i-arrow"/></svg></a>@endif
    </header>
    <div class="product-grid">
      @foreach($cards as $product)
        @include('shop.partials.essence-card', ['product' => $product, 'index' => $loop->index])
      @endforeach
    </div>
    @if(!empty($data['assurance']))
      <div class="assurance" data-reveal>
        <ul class="assurance__list">
          @foreach(array_filter($data['assurance']) as $i => $text)
            <li><svg width="20" height="20" aria-hidden="true"><use href="#i-{{ ['truck', 'clock', 'leaf'][$i] ?? 'check' }}"/></svg> {{ $text }}</li>
          @endforeach
        </ul>
        @include('shop.partials.pay-icons')
      </div>
    @endif
  </div>
</section>
@if($cards->isNotEmpty())
  <div class="sticky-cta" data-sticky-cta data-sticky-target="essence" aria-hidden="true">
    <div class="sticky-cta__stack" aria-hidden="true">
      @foreach($cards->take(3) as $p)@if($img = $p->imageUrl(120))<img src="{{ $img }}" alt="" width="34" height="45">@endif @endforeach
    </div>
    <p class="sticky-cta__text"><strong>{{ $data['sticky_title'] ?? '' }}</strong><span>{{ __('vanaf :price', ['price' => money($cards->min(fn ($p) => $p->minPrice()))]) }}</span></p>
    @if($wa = \App\Support\Storefront::whatsappUrl())<a class="sticky-cta__wa" href="{{ $wa }}" target="_blank" rel="noopener" aria-label="{{ __('Stuur ons een bericht via Whatsapp') }}" tabindex="-1"><svg width="22" height="22" aria-hidden="true"><use href="#i-whatsapp"/></svg></a>@endif
    <a class="btn btn--primary" href="#essence" tabindex="-1">{{ __('shop nu') }}</a>
  </div>
@endif
