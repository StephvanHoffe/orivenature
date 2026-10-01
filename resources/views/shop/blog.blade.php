@extends('shop.layouts.app')
@section('title', __('Blog'))
@section('template', 'blog')
@section('content')
  <div class="section blog blog-page">
    <div class="container">
      <header class="page-head" data-reveal>
        <h1 class="h2 h2--xl">{{ __('Blog') }} <em>{{ __('posts') }}</em></h1>
        @if($allTags->isNotEmpty())
          <nav class="tag-list" aria-label="{{ __('Onderwerpen') }}">
            <a class="tag-list__item" @if(! $tag) aria-current="true" @endif href="{{ url('/blogs/'.$blog) }}">{{ __('Alles') }}</a>
            @foreach($allTags as $t)
              <a class="tag-list__item" @if($tag === \Illuminate\Support\Str::slug($t)) aria-current="true" @endif href="{{ url('/blogs/'.$blog.'/tagged/'.\Illuminate\Support\Str::slug($t)) }}">{{ $t }}</a>
            @endforeach
          </nav>
        @endif
      </header>
      <div class="blog__grid blog__grid--page">
        @forelse($articles as $article)
          @include('shop.partials.article-card', ['article' => $article, 'tag' => __('recept'), 'index' => $loop->index])
        @empty
          <p>{{ __('Er staan nog geen berichten op de blog.') }}</p>
        @endforelse
      </div>
      @include('shop.partials.pagination', ['paginator' => $articles])
    </div>
  </div>
@endsection
