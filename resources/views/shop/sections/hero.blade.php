@php
  $words = fn ($text) => array_values(array_filter(explode(' ', (string) $text)));
  $before = $words($data['heading_before'] ?? '');
  $after = $words($data['heading_after'] ?? '');
  $full = trim(($data['heading_before'] ?? '').' '.($data['heading_highlight'] ?? '').' '.($data['heading_after'] ?? ''));
  $w = 0;
  $positions = ['left' => '6% 50%', 'center' => '50% 50%', 'right' => '100% 50%'];
  $tones = ['var(--sage)', 'var(--mauve)', 'var(--mauve-soft)'];
  $floatNames = ['matcha', 'ube', 'dragon'];
  $depths = [26, -34, 18];
@endphp
<section class="hero" data-hero>
  <div class="hero__bg" aria-hidden="true"><span class="blob blob--sage"></span><span class="blob blob--mauve"></span></div>
  <div class="hero__inner container">
    <div class="hero__copy">
      @if(!empty($data['eyebrow']))<p class="pill-label" data-reveal><svg width="16" height="16" aria-hidden="true"><use href="#i-leaf"/></svg> {{ $data['eyebrow'] }}</p>@endif
      <h1 class="hero__title" aria-label="{{ $full }}">
        @foreach($before as $word)<span class="w" aria-hidden="true" style="--wd: {{ 0.05 + 0.07 * $w++ }}s"><span>{{ $word }}</span></span> @endforeach
        @if(!empty($data['heading_highlight']))<span class="w w--mark" aria-hidden="true" style="--wd: {{ 0.05 + 0.07 * $w++ }}s"><span><em>{{ $data['heading_highlight'] }}</em><svg class="scribble" viewBox="0 0 200 40" preserveAspectRatio="none"><path d="M4 28c30-10 58-14 96-12 34 2 62 6 96-4" fill="none" stroke="currentColor" stroke-width="12" stroke-linecap="round"/></svg></span></span> @endif
        @foreach($after as $word)<span class="w" aria-hidden="true" style="--wd: {{ 0.05 + 0.07 * $w++ }}s"><span>{{ $word }}</span></span>@if(! $loop->last) @endif @endforeach
      </h1>
      @if(!empty($data['text']))<p class="hero__lead" data-reveal style="--d:.5s">{{ $data['text'] }}</p>@endif

      <div class="hero__actions" data-reveal style="--d:.6s">
        @if(!empty($data['button_label']))<a class="btn btn--primary btn--lg" href="{{ $data['button_link'] ?? '#essence' }}">{{ $data['button_label'] }} <svg width="18" height="18" aria-hidden="true"><use href="#i-arrow"/></svg></a>@endif
        @php($flavours = collect($data['flavours'] ?? [])->map(fn ($h) => $products->get($h))->filter())
        @if($flavours->isNotEmpty())
          <div class="flavour-pick" role="group" aria-label="{{ $data['flavour_label'] ?? '' }}">
            <span class="flavour-pick__label">{{ $data['flavour_label'] ?? '' }}</span>
            @foreach($flavours as $p)
              <a class="flavour-pick__dot" href="{{ $p->url() }}" data-jump="{{ $p->handle }}" style="--c:{{ $p->tone ?: $tones[$loop->index % 3] }}" aria-label="{{ $p->title }}">
                @if($img = $p->imageUrl(120))<img src="{{ $img }}" alt="" width="40" height="53">@endif
              </a>
            @endforeach
          </div>
        @endif
      </div>

      @if(!empty($data['trust']))
        <ul class="trust-row" data-reveal style="--d:.7s">
          @foreach($data['trust'] as $item)<li><svg width="18" height="18" aria-hidden="true"><use href="#i-{{ $item['icon'] ?? 'check' }}"/></svg> {{ $item['text'] ?? '' }}</li>@endforeach
        </ul>
      @endif
    </div>

    <div class="hero__visual" data-reveal="zoom" style="--d:.15s">
      <span class="hero__blob" aria-hidden="true"></span>
      @foreach([1, 2] as $side)
        @php($image = $data['image_'.$side] ?? null)
        <figure class="hero__card hero__card--{{ $side === 1 ? 'a' : 'b' }}" style="--depth:{{ $side === 1 ? -10 : 12 }}">
          @if($image)
            <img class="hero__photo" src="{{ \App\Support\Media::url($image, 1200) }}" srcset="{{ \App\Support\Media::srcset($image, [600, 900, 1200, 1600]) }}" sizes="(min-width: 960px) 45vw, 90vw" alt="{{ $full }}" width="1200" height="800" style="object-position: {{ $positions[$data['image_'.$side.'_position'] ?? 'center'] ?? '50% 50%' }}" @if($side === 1) fetchpriority="high" @endif>
          @endif
        </figure>
      @endforeach
      @foreach(array_slice((array) ($data['floaties'] ?? []), 0, 3) as $i => $floaty)
        @if($floaty)
          <div class="floaty floaty--{{ $floatNames[$i] }}" style="--depth:{{ $depths[$i] }}" aria-hidden="true"><img src="{{ \App\Support\Media::url($floaty, 400) }}" alt="" width="300" height="400"></div>
        @endif
      @endforeach
      @if(!empty($data['badge_text']))
        <div class="spin-badge" style="--depth:-14" aria-hidden="true">
          <svg viewBox="0 0 200 200"><defs><path id="badge-circle" d="M100,100 m-78,0 a78,78 0 1,1 156,0 a78,78 0 1,1 -156,0"/></defs><text><textPath href="#badge-circle" textLength="486">{{ $data['badge_text'] }}</textPath></text></svg>
          <svg class="spin-badge__icon" width="44" height="44"><use href="#i-leaf"/></svg>
        </div>
      @endif
      <span class="sparkle sparkle--1" aria-hidden="true"><svg width="28" height="28"><use href="#i-sparkle"/></svg></span>
      <span class="sparkle sparkle--2" aria-hidden="true"><svg width="18" height="18"><use href="#i-sparkle"/></svg></span>
      <span class="sparkle sparkle--3" aria-hidden="true"><svg width="22" height="22"><use href="#i-sparkle"/></svg></span>
    </div>
  </div>
</section>
