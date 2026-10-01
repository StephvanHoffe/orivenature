@php($tilts = [-1.6, 1.4, -1.2, 1.7])
<section class="section section--tight cats" aria-label="{{ __('Shop per categorie') }}">
  <div class="cats__grid">
    @foreach($data['tiles'] ?? [] as $tile)
      <a class="cat" href="{{ $tile['link'] ?? '#' }}" data-reveal style="--d: {{ $loop->index * 0.08 }}s; --tilt: {{ $tilts[$loop->index % 4] }}deg">
        @if(!empty($tile['image']))<img src="{{ \App\Support\Media::url($tile['image'], 900) }}" alt="{{ $tile['title'] ?? '' }}" width="800" height="1000" loading="lazy">@endif
        <span class="cat__title">{{ $tile['title'] ?? '' }}</span>
        @if(!empty($tile['cta']))<span class="cat__cta">{{ $tile['cta'] }} <svg width="16" height="16" aria-hidden="true"><use href="#i-arrow"/></svg></span>@endif
      </a>
    @endforeach
  </div>
</section>
