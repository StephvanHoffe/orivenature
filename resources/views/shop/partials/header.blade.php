@php
  $menu = \App\Models\Menu::byHandle('main-menu');
  $highlight = mb_strtolower((string) settings('store.nav_highlight'));
  $cartCount = app(\App\Services\Cart::class)->count();
  $customer = auth('customer')->user();
  $isNew = fn ($item) => $highlight !== '' && mb_strtolower($item->title) === $highlight && settings('store.nav_highlight_label');
@endphp
<div class="announce" role="region" aria-label="{{ __('Aankondiging') }}">
  <p class="announce__text">
    @if(settings('store.announcement_1'))
      <span class="announce__msg is-active" data-announce>
        <span class="announce__dot" aria-hidden="true"></span>{{ settings('store.announcement_1') }}
        @if(settings('store.countdown'))<span class="announce__timer" data-cutoff data-cutoff-hour="{{ (int) settings('store.cutoff_hour', 22) }}" data-label="{{ __('nog [h]u [m]m[sec]') }}" hidden></span>@endif
      </span>
    @endif
    @if(settings('store.announcement_1') && settings('store.announcement_2'))<span class="announce__sep" aria-hidden="true">|</span>@endif
    @if(settings('store.announcement_2'))<span class="announce__msg" data-announce>{{ settings('store.announcement_2') }}</span>@endif
  </p>
</div>

<header class="header" data-header>
  <div class="header__inner">
    <button class="icon-btn header__burger" type="button" data-menu-open aria-label="{{ __('Menu openen') }}" aria-controls="menu" aria-expanded="false">
      <svg width="24" height="24" aria-hidden="true"><use href="#i-menu"/></svg>
    </button>

    <nav class="nav" aria-label="{{ __('Hoofdmenu') }}">
      <ul class="nav__list">
        @foreach($menu?->items ?? [] as $item)
          <li>
            @if($item->children->isNotEmpty())
              <details class="locale nav__dropdown">
                <summary class="nav__link">{{ $item->title }} <svg width="14" height="14" aria-hidden="true"><use href="#i-chevron"/></svg></summary>
                <div class="locale__menu nav__menu">
                  @foreach($item->children as $child)<a href="{{ $child->url }}">{{ $child->title }}</a>@endforeach
                </div>
              </details>
            @else
              <a class="nav__link" href="{{ $item->url }}" @if($item->isCurrent()) aria-current="page" @endif>{{ $item->title }}@if($item->badge || $isNew($item)) <span class="nav__new">{{ $item->badge ?: settings('store.nav_highlight_label') }}</span>@endif</a>
            @endif
          </li>
        @endforeach
      </ul>
    </nav>

    <a class="logo" href="{{ route('home') }}" aria-label="{{ settings('store.name') }} – {{ __('naar de start') }}">
      @include('shop.partials.logo')
    </a>

    <div class="header__tools">
      <a class="icon-btn hide-sm" href="{{ $customer ? route('account.dashboard') : route('account.login') }}" aria-label="{{ $customer ? __('Mijn account') : __('Inloggen') }}">
        <svg width="22" height="22" aria-hidden="true"><use href="#i-user"/></svg>
      </a>
      <a class="icon-btn" href="{{ route('search') }}" aria-label="{{ __('Zoeken') }}">
        <svg width="22" height="22" aria-hidden="true"><use href="#i-search"/></svg>
      </a>
      <a class="icon-btn cart-btn" href="{{ route('cart') }}" data-cart-open aria-label="{{ __('Winkelwagen openen') }}">
        <svg width="22" height="22" aria-hidden="true"><use href="#i-bag"/></svg>
        <span class="cart-btn__count" data-cart-count @if($cartCount === 0) hidden @endif>{{ $cartCount }}</span>
      </a>
    </div>
  </div>
</header>

<dialog class="drawer drawer--left" id="menu" aria-label="{{ __('Menu') }}" data-drawer="menu">
  <div class="drawer__head">
    @include('shop.partials.logo', ['class' => 'drawer__logo'])
    <button class="icon-btn" type="button" data-close aria-label="{{ __('Menu sluiten') }}">
      <svg width="24" height="24" aria-hidden="true"><use href="#i-close"/></svg>
    </button>
  </div>
  <nav class="menu" aria-label="{{ __('Mobiel menu') }}">
    @foreach($menu?->items ?? [] as $item)
      <a href="{{ $item->url }}" style="--i:{{ $loop->index }}">{{ $item->title }}@if($item->badge || $isNew($item)) <span class="nav__new">{{ $item->badge ?: settings('store.nav_highlight_label') }}</span>@endif</a>
      @foreach($item->children as $child)<a class="menu__child" href="{{ $child->url }}" style="--i:{{ $loop->parent->index }}">{{ $child->title }}</a>@endforeach
    @endforeach
  </nav>
  <div class="menu__foot">
    <a class="btn btn--primary btn--block" href="{{ request()->routeIs('home') ? '#essence' : url('/collections/all') }}" @if(request()->routeIs('home')) data-close @endif>{{ __('bekijk onze essence') }} <svg width="18" height="18" aria-hidden="true"><use href="#i-arrow"/></svg></a>
    <a class="menu__account" href="{{ $customer ? route('account.dashboard') : route('account.login') }}"><svg width="20" height="20" aria-hidden="true"><use href="#i-user"/></svg> {{ $customer ? __('Mijn account') : __('Inloggen') }}</a>
  </div>
</dialog>
