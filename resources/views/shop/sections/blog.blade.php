@if($articles->isNotEmpty())
<section class="section blog" aria-labelledby="blog-title">
  <div class="container">
    <header class="section-head" data-reveal>
      <h2 class="h2" id="blog-title">{{ $data['heading'] ?? '' }} @if(!empty($data['heading_italic']))<em>{{ $data['heading_italic'] }}</em>@endif</h2>
      @if(!empty($data['button_label']))<a class="btn btn--ghost" href="{{ url('/blogs/'.($data['blog'] ?? 'news')) }}">{{ $data['button_label'] }} <svg width="16" height="16" aria-hidden="true"><use href="#i-arrow"/></svg></a>@endif
    </header>
    <div class="blog__grid">
      @foreach($articles as $article)
        @include('shop.partials.article-card', ['article' => $article, 'tag' => $data['tag'] ?? null, 'index' => $loop->index])
      @endforeach
    </div>
  </div>
</section>
@endif
