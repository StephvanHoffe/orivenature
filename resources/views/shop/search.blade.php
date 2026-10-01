@extends('shop.layouts.app')
@section('title', $q ? __('Zoeken: :q', ['q' => $q]) : __('Zoeken'))
@section('noindex', true)
@section('content')
  <div class="section search-page">
    <div class="container">
      <header class="page-head" data-reveal>
        <h1 class="h2 h2--xl">{{ __('Zoeken') }}</h1>
        <form class="subscribe search-form" action="{{ route('search') }}" method="get" role="search">
          <label class="visually-hidden" for="search-q">{{ __('Waar ben je naar op zoek?') }}</label>
          <input class="subscribe__input" id="search-q" type="search" name="q" value="{{ $q }}" placeholder="{{ __('Waar ben je naar op zoek?') }}" autofocus>
          <button class="btn btn--cream" type="submit"><svg width="16" height="16" aria-hidden="true"><use href="#i-search"/></svg> {{ __('Zoeken') }}</button>
        </form>
        @if($q !== '')
          @php($count = $products->count() + $articles->count() + $pages->count())
          <p class="page-head__text">{{ $count ? trans_choice(':count resultaat voor “:q”|:count resultaten voor “:q”', $count, ['q' => $q]) : __('Geen resultaten voor “:q”. Probeer een andere zoekterm.', ['q' => $q]) }}</p>
        @endif
      </header>
      @if($products->isNotEmpty())
        <div class="product-grid product-grid--4">
          @foreach($products as $product)@include('shop.partials.product-card', ['product' => $product, 'index' => $loop->index])@endforeach
        </div>
      @endif
      @if($articles->isNotEmpty() || $pages->isNotEmpty())
        <div class="blog__grid blog__grid--page" style="margin-top:3rem">
          @foreach($articles as $article)@include('shop.partials.article-card', ['article' => $article, 'index' => $loop->index])@endforeach
          @foreach($pages as $page)
            <a class="search-page__result" href="{{ $page->url() }}"><h3 class="post__title">{{ $page->title }}</h3><p class="post__excerpt">{{ \Illuminate\Support\Str::words(strip_tags((string) $page->body), 24) }}</p></a>
          @endforeach
        </div>
      @endif
    </div>
  </div>
@endsection
