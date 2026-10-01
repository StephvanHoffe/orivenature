<section class="origin" aria-label="{{ $data['heading'] ?? '' }}">
  <div class="origin__frame" data-reveal="zoom">
    @if(!empty($data['image']))
      <div class="origin__media" data-parallax="0.1">
        <img class="origin__img" src="{{ \App\Support\Media::url($data['image'], 1600) }}" srcset="{{ \App\Support\Media::srcset($data['image'], [900, 1200, 1600]) }}" sizes="100vw" alt="" loading="lazy">
      </div>
    @endif
    <div class="origin__content">
      <h2 class="h2 h2--xl">{{ $data['heading'] ?? '' }} @if(!empty($data['heading_italic']))<em>{{ $data['heading_italic'] }}</em>@endif</h2>
      @if(!empty($data['text']))<p>{{ $data['text'] }}</p>@endif
      @if(!empty($data['button_label']))<a class="btn btn--cream btn--lg" href="{{ $data['button_link'] ?? '#essence' }}">{{ $data['button_label'] }} <svg width="18" height="18" aria-hidden="true"><use href="#i-arrow"/></svg></a>@endif
    </div>
  </div>
</section>
