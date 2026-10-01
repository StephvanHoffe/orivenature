<div class="cart-drawer" data-cart-drawer data-count="{{ $count }}">
  <div class="drawer__head">
    <h2 class="cart__title" id="cart-title">{{ __('Winkelwagen') }} @if($count)<span>({{ $count }})</span>@endif</h2>
    <button class="icon-btn" type="button" data-close aria-label="{{ __('Winkelwagen sluiten') }}">
      <svg width="24" height="24" aria-hidden="true"><use href="#i-close"/></svg>
    </button>
  </div>

  @if($perks = array_filter((array) settings('cart.perks')))
    <div class="cart__perks">
      @foreach($perks as $perk)
        <p><svg width="18" height="18" aria-hidden="true"><use href="#i-{{ $loop->first ? 'truck' : 'clock' }}"/></svg> {{ $perk }}</p>
      @endforeach
    </div>
  @endif

  <div class="cart__body">
    @if($lines->isEmpty())
      <div class="cart-empty">
        <div class="cart-empty__pouches" aria-hidden="true">
          @foreach(\App\Models\Product::active()->with('images')->orderBy('position')->take(3)->get() as $p)
            @if($p->imageUrl(200))<img src="{{ $p->imageUrl(200) }}" alt="">@endif
          @endforeach
        </div>
        <h3>{{ __('Je winkelwagen is leeg') }}</h3>
        <a class="btn btn--primary" href="{{ request()->routeIs('home') ? '#essence' : url('/collections/all') }}" data-close>{{ __('Doorgaan met winkelen') }}</a>
      </div>
    @else
      @foreach($lines as $line)
        <div class="line">
          <a class="line__img" href="{{ $line->product()->url($line->variant) }}">
            @if($img = $line->variant->imageUrl(200))<img src="{{ $img }}" alt="" width="60" height="60" loading="lazy">@endif
          </a>
          <div>
            <p class="line__title"><a href="{{ $line->product()->url($line->variant) }}">{{ $line->title() }}</a></p>
            @if($line->variantTitle())<p class="line__variant">{{ $line->variantTitle() }}</p>@endif
            <p class="line__price">{{ money($line->total()) }}</p>
          </div>
          <div class="line__side">
            <div class="qty" role="group" aria-label="{{ __('Aantal :title', ['title' => $line->title()]) }}">
              <button type="button" data-line-variant="{{ $line->variant->id }}" data-line-qty="{{ $line->quantity - 1 }}" aria-label="{{ __('Eén minder') }}"><svg width="14" height="14" aria-hidden="true"><use href="#i-minus"/></svg></button>
              <output>{{ $line->quantity }}</output>
              <button type="button" data-line-variant="{{ $line->variant->id }}" data-line-qty="{{ $line->quantity + 1 }}" aria-label="{{ __('Eén meer') }}"><svg width="14" height="14" aria-hidden="true"><use href="#i-plus"/></svg></button>
            </div>
            <button class="line__remove" type="button" data-line-variant="{{ $line->variant->id }}" data-line-qty="0" aria-label="{{ __('Verwijder :title', ['title' => $line->title()]) }}"><svg width="18" height="18" aria-hidden="true"><use href="#i-trash"/></svg></button>
          </div>
        </div>
      @endforeach

      @foreach($upsells as $upsell)
        @php($p = $upsell['product'])
        <div class="upsell">
          @if($upsell['label'])<p class="upsell__title">{{ $upsell['label'] }}</p>@endif
          <div class="upsell__item">
            @if($p->imageUrl(200))<img src="{{ $p->imageUrl(200) }}" alt="" width="64" height="64" loading="lazy">@endif
            <div>
              <p class="upsell__name"><a href="{{ $p->url() }}" data-quickview="{{ route('products.quick', $p->handle) }}">{{ $p->title }}</a></p>
              <p class="upsell__price">{{ $p->hasOnlyDefaultVariant() ? '' : __('vanaf').' ' }}{{ money($p->minPrice()) }}</p>
            </div>
            <button class="upsell__add" type="button" data-upsell="{{ $p->defaultVariant()->id }}" aria-label="{{ __(':title toevoegen', ['title' => $p->title]) }}"><svg width="18" height="18" aria-hidden="true"><use href="#i-plus"/></svg></button>
          </div>
        </div>
      @endforeach
    @endif
  </div>

  @if($lines->isNotEmpty())
    <div class="cart__foot">
      <div class="cart__total"><span>{{ __('Subtotaal') }}</span><strong>{{ money($subtotal) }}</strong></div>
      @if($earn = \App\Services\Loyalty::earnText($subtotal))
        <p class="loyalty-earn loyalty-earn--cart"><svg width="18" height="18" aria-hidden="true"><use href="#i-coin"/></svg> {{ $earn }}</p>
      @endif
      @if(settings('cart.note'))<p class="cart__note">{{ settings('cart.note') }}</p>@endif
      <a class="btn btn--primary btn--lg btn--block" href="{{ route('checkout') }}">{{ __('afrekenen') }} <svg width="18" height="18" aria-hidden="true"><use href="#i-arrow"/></svg></a>
      @include('shop.partials.pay-icons', ['class' => 'pay-icons--center'])
      <button class="cart__continue" type="button" data-close>{{ __('Doorgaan met winkelen') }}</button>
    </div>
  @endif
</div>
