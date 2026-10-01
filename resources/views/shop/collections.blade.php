@extends('shop.layouts.app')
@section('title', __('Alle collecties'))
@section('template', 'list-collections')
@section('content')
  <div class="section">
    <div class="container"><header class="page-head" data-reveal><h1 class="h2 h2--xl">{{ __('Alle collecties') }}</h1></header></div>
    <div class="cats__grid">
      @foreach($collections->where('products_count', '>', 0) as $collection)
        <a class="cat" href="{{ $collection->url() }}" data-reveal style="--d: {{ ($loop->index % 4) * 0.08 }}s; --tilt: {{ [-1.4, 1.2, -1, 1.6][$loop->index % 4] }}deg">
          @if($img = $collection->imageUrl(900))<img src="{{ $img }}" alt="{{ $collection->title }}" loading="lazy">@endif
          <span class="cat__title">{{ $collection->title }}</span>
          <span class="cat__cta">{{ trans_choice(':count product|:count producten', $collection->products_count) }} <svg width="16" height="16" aria-hidden="true"><use href="#i-arrow"/></svg></span>
        </a>
      @endforeach
    </div>
  </div>
@endsection
