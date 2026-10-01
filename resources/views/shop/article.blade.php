@extends('shop.layouts.app')
@section('title', $article->seo_title ?: $article->title)
@section('description', $article->seo_description ?: $article->excerptText(30))
@section('image', $article->imageUrl(1200))
@section('og_type', 'article')
@section('template', 'article')
@section('content')
  <article class="section article-page">
    <div class="container container--narrow">
      <header class="page-head" data-reveal>
        <a class="article-page__back" href="{{ url('/blogs/'.$article->blog) }}"><svg width="16" height="16" aria-hidden="true"><use href="#i-arrow"/></svg> {{ __('Terug naar de blog') }}</a>
        <h1 class="h2 h2--xl">{{ $article->title }}</h1>
        @if($article->published_at)<p class="article-page__date"><time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->translatedFormat('j F Y') }}</time></p>@endif
      </header>
      @if($img = $article->imageUrl(1600))<div class="article-page__image" data-reveal="zoom"><img src="{{ $img }}" alt="{{ $article->title }}"></div>@endif
      <div class="rte" data-reveal>{!! $article->body !!}</div>
      @if($products->isNotEmpty())
        <aside class="article-page__shop">
          <h2 class="h2">{{ __('Maak het zelf met') }}</h2>
          <div class="product-grid product-grid--3">
            @foreach($products as $product)@include('shop.partials.product-card', ['product' => $product, 'index' => $loop->index])@endforeach
          </div>
        </aside>
      @endif
    </div>
  </article>
@endsection
