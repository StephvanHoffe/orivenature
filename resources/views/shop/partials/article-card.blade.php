<a class="post" href="{{ $article->url() }}" data-reveal style="--d: {{ ($index ?? 0) * 0.08 }}s">
  <div class="post__media">
    @if($img = $article->imageUrl(600))<img src="{{ $img }}" alt="{{ $article->title }}" width="600" height="760" loading="lazy">@endif
    @if(!empty($tag))<span class="post__tag">{{ $tag }}</span>@endif
  </div>
  <h3 class="post__title">{{ $article->title }}</h3>
  <p class="post__excerpt">{{ $article->excerptText(30) }}</p>
</a>
