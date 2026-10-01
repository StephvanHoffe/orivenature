@php($product = $products->get($data['product'] ?? ''))
<section class="section launch" aria-labelledby="launch-title">
  <div class="container launch__inner">
    <div class="launch__visual" data-reveal="zoom">
      <span class="launch__disc" aria-hidden="true"></span>
      @if(!empty($data['badge_text']))
        <div class="spin-badge spin-badge--new" aria-hidden="true">
          <svg viewBox="0 0 200 200"><defs><path id="new-circle" d="M100,100 m-78,0 a78,78 0 1,1 156,0 a78,78 0 1,1 -156,0"/></defs><text><textPath href="#new-circle" textLength="486">{{ $data['badge_text'] }}</textPath></text></svg>
          <svg class="spin-badge__icon" width="40" height="40"><use href="#i-sparkle"/></svg>
        </div>
      @endif
      <div class="phone" data-parallax="-0.06">
        <div class="phone__screen">
          @if(!empty($data['video']))
            <video class="phone__video" data-lazy-video muted loop playsinline preload="none" @if(!empty($data['poster'])) poster="{{ \App\Support\Media::url($data['poster'], 600) }}" @endif data-src="{{ str_starts_with($data['video'], 'http') ? $data['video'] : asset('uploads/'.$data['video']) }}" aria-label="{{ __('Video: :title', ['title' => $data['heading_italic'] ?? '']) }}"></video>
          @elseif(!empty($data['poster']))
            <img class="phone__video" src="{{ \App\Support\Media::url($data['poster'], 600) }}" alt="" loading="lazy">
          @endif
        </div>
        <span class="phone__notch" aria-hidden="true"></span>
      </div>
      @if(!empty($data['floaty']))
        <div class="floaty floaty--launch" data-parallax="0.08"><img src="{{ \App\Support\Media::url($data['floaty'], 600) }}" alt="{{ $product?->title }}" width="300" height="400" loading="lazy"></div>
      @endif
      <span class="powder" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></span>
    </div>
    <div class="launch__copy" data-reveal>
      @if(!empty($data['eyebrow']))<p class="pill-label pill-label--mauve"><svg width="14" height="14" aria-hidden="true"><use href="#i-sparkle"/></svg> {{ $data['eyebrow'] }}</p>@endif
      <h2 class="h2 h2--xl" id="launch-title">{{ $data['heading_before'] ?? '' }} @if(!empty($data['heading_italic']))<em>{{ $data['heading_italic'] }}</em>@endif {{ $data['heading_after'] ?? '' }}</h2>
      @if(!empty($data['text']))<p class="launch__text">{{ $data['text'] }}</p>@endif
      <div class="launch__actions">
        @if(!empty($data['button_label']))
          <a class="btn btn--primary btn--lg" href="{{ $product?->url() ?? ($data['button_link'] ?? '/collections/all') }}" @if($product) data-quickview="{{ route('products.quick', $product->handle) }}" @endif>{{ $data['button_label'] }} <svg width="18" height="18" aria-hidden="true"><use href="#i-arrow"/></svg></a>
        @endif
        @if($product)<span class="launch__price">{{ __('vanaf') }} <strong>{{ money($product->minPrice()) }}</strong></span>@endif
      </div>
    </div>
  </div>
</section>
