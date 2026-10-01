@if(\App\Services\Loyalty::enabled())
@php
  $customer = auth('customer')->user();
  $replace = fn ($text) => str_replace(['[points]', '[rate]', '[bonus]'], [settings('loyalty.points_per_euro'), \App\Services\Loyalty::rateText(), settings('loyalty.signup_bonus')], (string) $text);
@endphp
<section class="section loyalty" id="sparen" aria-labelledby="loyalty-title">
  <div class="container">
    <div class="loyalty__card" data-reveal="zoom">
      <div class="loyalty__intro">
        @if(!empty($data['eyebrow']))<p class="pill-label"><svg width="16" height="16" aria-hidden="true"><use href="#i-gift"/></svg> {{ $data['eyebrow'] }}</p>@endif
        <h2 class="h2 h2--xl" id="loyalty-title">{{ $data['heading'] ?? '' }} @if(!empty($data['heading_italic']))<em>{{ $data['heading_italic'] }}</em>@endif</h2>
        @if(!empty($data['text']))<p class="loyalty__text">{{ $data['text'] }}</p>@endif
        @if($customer)
          <div class="loyalty__balance">
            <p class="loyalty__hello">{{ __('Hoi :name!', ['name' => $customer->first_name ?: $customer->name]) }}</p>
            <p class="loyalty__points"><strong>{{ $customer->points_balance }}</strong> {{ __('punten') }} · {{ money($customer->credit_balance) }} {{ __('tegoed') }}</p>
            <a class="btn btn--primary" href="{{ route('account.dashboard') }}#sparen">{{ __('bekijk mijn punten') }} <svg width="18" height="18" aria-hidden="true"><use href="#i-arrow"/></svg></a>
          </div>
        @else
          <div class="loyalty__actions">
            <a class="btn btn--primary btn--lg" href="{{ route('account.register') }}">{{ __('word gratis lid') }} <svg width="18" height="18" aria-hidden="true"><use href="#i-arrow"/></svg></a>
            <a class="btn btn--ghost" href="{{ route('account.login') }}">{{ __('Inloggen') }}</a>
          </div>
        @endif
      </div>
      <ol class="loyalty__steps">
        @foreach($data['steps'] ?? [] as $step)
          <li class="loyalty__step" style="--i: {{ $loop->iteration }}">
            <span class="loyalty__icon" aria-hidden="true"><svg width="28" height="28"><use href="#i-{{ $step['icon'] ?? 'star' }}"/></svg></span>
            <h3>{{ $step['title'] ?? '' }}</h3>
            <p>{{ $replace($step['text'] ?? '') }}</p>
          </li>
        @endforeach
      </ol>
    </div>
  </div>
</section>
@endif
