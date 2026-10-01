<section class="section story" aria-label="{{ $data['eyebrow'] ?? __('Ons verhaal') }}">
  <span class="story__leaf story__leaf--1" data-parallax="0.18" aria-hidden="true"><svg width="64" height="64"><use href="#i-leaf"/></svg></span>
  <span class="story__leaf story__leaf--2" data-parallax="-0.12" aria-hidden="true"><svg width="44" height="44"><use href="#i-leaf"/></svg></span>
  <span class="story__leaf story__leaf--3" data-parallax="0.1" aria-hidden="true"><svg width="30" height="30"><use href="#i-sparkle"/></svg></span>
  <div class="container story__inner">
    @if(!empty($data['eyebrow']))<p class="eyebrow" data-reveal>{{ $data['eyebrow'] }}</p>@endif
    <p class="story__text" data-words>{{ $data['text'] ?? '' }}</p>
    @if(!empty($data['button_label']))<a class="btn btn--ghost" href="{{ $data['button_link'] ?? '#' }}" data-reveal>{{ $data['button_label'] }} <svg width="16" height="16" aria-hidden="true"><use href="#i-arrow"/></svg></a>@endif
  </div>
</section>
