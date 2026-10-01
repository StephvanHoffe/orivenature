<section class="ribbons" aria-label="{{ __('Onze beloftes') }}">
  @if(!empty($data['top']))
    <div class="ribbon ribbon--olive"><div class="ribbon__track" data-marquee>
      @foreach($data['top'] as $text)<span>{{ $text }}</span><svg width="22" height="22" aria-hidden="true"><use href="#i-sparkle"/></svg>@endforeach
    </div></div>
  @endif
  @if(!empty($data['bottom']))
    <div class="ribbon ribbon--sage" aria-hidden="true"><div class="ribbon__track ribbon__track--reverse" data-marquee>
      @foreach($data['bottom'] as $text)<span>{{ $text }}</span><svg width="18" height="18"><use href="#i-leaf"/></svg>@endforeach
    </div></div>
  @endif
</section>
