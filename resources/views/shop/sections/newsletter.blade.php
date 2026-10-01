<section class="section newsletter" id="nieuwsbrief" aria-labelledby="newsletter-title">
  <div class="container">
    <div class="newsletter__card" data-reveal="zoom">
      <div class="newsletter__media">
        @if(!empty($data['image']))<img src="{{ \App\Support\Media::url($data['image'], 900) }}" alt="" width="900" height="1200" loading="lazy">@endif
        @if(!empty($data['sticker_big']))<div class="sticker" aria-hidden="true"><strong>{{ $data['sticker_big'] }}</strong><span>{{ $data['sticker_small'] ?? '' }}</span></div>@endif
      </div>
      <div class="newsletter__copy">
        <h2 class="h2 h2--xl" id="newsletter-title">@if(!empty($data['heading_italic']))<em>{{ $data['heading_italic'] }}</em> @endif{{ $data['heading'] ?? '' }}</h2>
        @if(!empty($data['text']))<p>{{ $data['text'] }}</p>@endif
        <form class="subscribe" method="post" action="{{ route('newsletter') }}" data-subscribe data-source="homepage">
          @csrf
          <label class="visually-hidden" for="nl-email">{{ __('E-mailadres') }}</label>
          <input class="subscribe__input" id="nl-email" type="email" name="email" placeholder="{{ __('E-mailadres') }}" autocomplete="email" required>
          <button class="btn btn--cream" type="submit">{{ __('abonneer') }} <svg width="16" height="16" aria-hidden="true"><use href="#i-arrow"/></svg></button>
        </form>
        <p class="newsletter__legal">{!! __('Door je aan te melden ga je akkoord met ons <a href=":url">privacybeleid</a>.', ['url' => url('/pages/'.settings('checkout.privacy_page'))]) !!}</p>
      </div>
    </div>
  </div>
</section>
