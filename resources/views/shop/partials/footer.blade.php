@php
  $footerMenus = collect(['ons-bedrijf' => __('Ons bedrijf'), 'klantenservice' => __('Klantenservice'), 'ons-assortiment' => __('Ons assortiment')])
    ->map(fn ($title, $handle) => ['title' => \App\Models\Menu::byHandle($handle)?->name ?? $title, 'menu' => \App\Models\Menu::byHandle($handle)])
    ->filter(fn ($m) => $m['menu']);
  $social = (array) settings('store.social');
@endphp
<footer class="footer">
  <ul class="usps container">
    <li class="usp" data-reveal style="--d:0s">
      <span class="usp__icon"><svg width="26" height="26" aria-hidden="true"><use href="#i-leaf"/></svg></span>
      <h3>{{ __('100% Biologisch') }}</h3>
      <p>{{ __('Puur gecertificeerd biologisch, zonder vulstoffen of kunstmatige toevoegingen. Precies zoals het hoort.') }}</p>
    </li>
    <li class="usp" data-reveal style="--d:.1s">
      <span class="usp__icon"><svg width="26" height="26" aria-hidden="true"><use href="#i-earth"/></svg></span>
      <h3>{{ __('Transparante Herkomst') }}</h3>
      <p>{{ __('Rechtstreeks van onze boeren naar jou. Transparant en puur van oorsprong.') }}</p>
    </li>
    <li class="usp" data-reveal style="--d:.2s">
      <span class="usp__icon"><svg width="26" height="26" aria-hidden="true"><use href="#i-phone"/></svg></span>
      <h3>{{ __('Hulp Nodig?') }}</h3>
      <p>{{ __('Heb je een vraag? Ons team is 24/7 bereikbaar voor al jouw vragen over bestellingen en ons assortiment.') }}</p>
    </li>
  </ul>

  <div class="footer__main container">
    <div class="footer__brand">
      @include('shop.partials.logo', ['class' => 'footer__logo'])
      <p class="footer__news-title">{{ __('Nieuwsbrief') }}</p>
      <p>{{ __('Meld je aan en ontvang 10% korting op je eerste bestelling, en word lid van onze community.') }}</p>
      <form class="subscribe subscribe--small" method="post" action="{{ route('newsletter') }}" data-subscribe data-source="footer">
        @csrf
        <label class="visually-hidden" for="footer-email">{{ __('E-mailadres') }}</label>
        <input class="subscribe__input" id="footer-email" type="email" name="email" placeholder="{{ __('E-mailadres') }}" autocomplete="email" required>
        <button class="btn btn--cream" type="submit">{{ __('abonneren') }}</button>
      </form>
      <p class="footer__legal">{!! __('Door je aan te melden ga je akkoord met ons <a href=":url">privacybeleid</a>.', ['url' => url('/pages/'.settings('checkout.privacy_page'))]) !!}</p>
    </div>

    @foreach($footerMenus as $col)
      <nav class="footer__col" aria-label="{{ $col['title'] }}">
        <h3>{{ $col['title'] }}</h3>
        <ul>
          @foreach($col['menu']->items as $item)
            <li><a href="{{ $item->url }}" @if(str_starts_with($item->url, 'http')) target="_blank" rel="noopener" @endif>{{ $item->title }}</a></li>
          @endforeach
        </ul>
      </nav>
    @endforeach

    <div class="footer__col">
      <h3>{{ __('Contact') }}</h3>
      <address>
        {{ settings('store.legal_name') }}<br>
        <a href="mailto:{{ settings('store.email') }}">{{ settings('store.email') }}</a><br>
        {!! nl2br(e(settings('store.address'))) !!}
      </address>
      @if($wa = \App\Support\Storefront::whatsappUrl())
        <p>{{ __('Of stuur ons een bericht via') }} <a class="u-link" href="{{ $wa }}" target="_blank" rel="noopener">Whatsapp</a></p>
      @endif
      <p class="footer__ids">KvK: {{ settings('store.kvk') }}<br>BTW: {{ settings('store.vat_number') }}</p>
    </div>
  </div>

  <div class="footer__bottom container">
    <span></span>
    <div class="footer__center">
      <p>&copy; {{ now()->year }} - {{ settings('store.name') }}</p>
      @include('shop.partials.pay-icons', ['all' => true])
    </div>
    <ul class="socials">
      @foreach(['facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'snapchat' => 'Snapchat'] as $key => $label)
        @if(!empty($social[$key]))
          <li><a href="{{ $social[$key] }}" target="_blank" rel="noopener" aria-label="{{ __('Volgen op :platform', ['platform' => $label]) }}"><svg width="22" height="22" aria-hidden="true"><use href="#i-{{ $key }}"/></svg></a></li>
        @endif
      @endforeach
    </ul>
  </div>
</footer>
