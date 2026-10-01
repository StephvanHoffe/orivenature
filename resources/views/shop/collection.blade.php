@extends('shop.layouts.app')
@section('title', $collection->seo_title ?: $collection->title)
@section('description', $collection->seo_description ?: strip_tags((string) $collection->description))
@section('template', 'collection')
@section('content')
  <div class="section collection-page">
    <div class="container">
      <header class="page-head" data-reveal>
        <h1 class="h2 h2--xl">{{ $collection->title }}</h1>
        @if($collection->description)<div class="page-head__text rte">{!! $collection->description !!}</div>@endif
        @if($products->total() > 1)
          <form class="sort" method="get">
            <label class="visually-hidden" for="sort-by">{{ __('Sorteren') }}</label>
            <select class="field__input" id="sort-by" name="sort_by" data-autosubmit>
              @foreach(\App\Models\Collection::SORT_ORDERS as $value => $label)
                <option value="{{ $value }}" @selected($value === $sort)>{{ $value === 'manual' ? __('Uitgelicht') : $label }}</option>
              @endforeach
            </select>
          </form>
        @endif
      </header>
      @if($products->isEmpty())
        <p class="empty-note">{{ __('Er zijn nog geen producten in deze collectie.') }}</p>
      @else
        <div class="product-grid product-grid--4">
          @foreach($products as $product)
            @include('shop.partials.product-card', ['product' => $product, 'index' => $loop->index])
          @endforeach
        </div>
        @include('shop.partials.pagination', ['paginator' => $products])
      @endif
    </div>
  </div>
@endsection
