@extends('shop.layouts.app')
@section('title', $product->seo_title ?: $product->title)
@section('description', $product->seo_description ?: $product->short_description ?: strip_tags((string) $product->description))
@section('image', $product->imageUrl(1200))
@section('og_type', 'product')
@section('template', 'product')
@push('head')
  <script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org', '@type' => 'Product', 'name' => $product->title,
    'description' => trim(strip_tags((string) $product->description)), 'image' => $product->images->map(fn ($i) => $i->url(1200))->values(),
    'brand' => ['@type' => 'Brand', 'name' => settings('store.name')],
    'offers' => $product->variants->map(fn ($v) => ['@type' => 'Offer', 'sku' => $v->sku ?: (string) $v->id, 'name' => $v->title, 'price' => number_format($v->price / 100, 2, '.', ''), 'priceCurrency' => 'EUR', 'availability' => $v->isAvailable() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock', 'url' => url($product->url($v))])->values(),
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush
@push('pixel')
  <script>fbq('track', 'ViewContent', { content_ids: @json($product->variants->pluck('id')->map(fn ($id) => (string) $id)), content_type: 'product', value: {{ number_format($variant->price / 100, 2, '.', '') }}, currency: 'EUR' });</script>
@endpush
@section('content')
  <div class="section product-page">
    <div class="container">
      @include('shop.partials.product-info', ['product' => $product, 'variant' => $variant, 'quick' => false])
    </div>
  </div>
  @if($related->isNotEmpty())
    <section class="section products" aria-labelledby="related-title">
      <div class="container">
        <header class="section-head" data-reveal><h2 class="h2" id="related-title">{{ __('Ontdek ook') }} <em>{{ __('onze essences') }}</em></h2></header>
        <div class="product-grid">
          @foreach($related as $item)
            @include('shop.partials.essence-card', ['product' => $item, 'index' => $loop->index])
          @endforeach
        </div>
      </div>
    </section>
  @endif
@endsection
