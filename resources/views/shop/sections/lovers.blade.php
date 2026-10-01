@php($rotations = [-4, 3, -2, 4, -3])
<section class="section lovers" aria-labelledby="lovers-title">
  <div class="container">
    <header class="section-head" data-reveal>
      <h2 class="h2" id="lovers-title"><em>{{ $data['heading'] ?? '' }}</em></h2>
      @if(!empty($data['button_label']) && !empty($data['button_link']))<a class="btn btn--ghost" href="{{ $data['button_link'] }}" @if(str_starts_with($data['button_link'], 'http')) target="_blank" rel="noopener" @endif>{{ $data['button_label'] }} <svg width="16" height="16" aria-hidden="true"><use href="#i-arrow"/></svg></a>@endif
    </header>
  </div>
  <div class="polaroids" data-reveal>
    <div class="polaroids__track" data-marquee>
      @foreach($data['photos'] ?? [] as $photo)
        <figure class="polaroid" style="--r:{{ $rotations[$loop->index % 5] }}deg"><img src="{{ \App\Support\Media::url($photo, 600) }}" alt="" width="600" height="800" loading="lazy"></figure>
      @endforeach
    </div>
  </div>
</section>
