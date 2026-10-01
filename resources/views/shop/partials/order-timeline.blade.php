{{-- Statusverloop van een bestelling met track & trace --}}
<ol class="otl" aria-label="{{ __('Status van je bestelling') }}">
  @foreach($order->timeline() as $step)
    <li class="otl__step otl__step--{{ $step['state'] }}" @if($step['state'] === 'current') aria-current="step" @endif>
      <span class="otl__dot" aria-hidden="true">
        @if($step['state'] === 'done')<svg width="14" height="14"><use href="#i-check"/></svg>@elseif($step['state'] === 'off')<svg width="12" height="12"><use href="#i-close"/></svg>@endif
      </span>
      <div class="otl__body">
        <p class="otl__label">{{ $step['label'] }}@if($step['date'])<time datetime="{{ $step['date']->toIso8601String() }}">{{ $step['date']->translatedFormat('j M, H:i') }}</time>@endif</p>
        @if($step['text'])<p class="otl__text">{{ $step['text'] }}</p>@endif
      </div>
    </li>
  @endforeach
</ol>
@foreach($order->fulfillments->sortBy('shipped_at') as $fulfillment)
  <div class="otrack">
    <span class="otrack__icon" aria-hidden="true"><svg width="22" height="22"><use href="#i-truck"/></svg></span>
    <div class="otrack__body">
      <strong>{{ $fulfillment->tracking_company ?: __('Pakket') }}@if($order->fulfillments->count() > 1) {{ $loop->iteration }}@endif</strong>
      <span>{{ __('Verzonden op :date', ['date' => $fulfillment->shipped_at?->translatedFormat('l j F')]) }}@if($fulfillment->tracking_number) · {{ $fulfillment->tracking_number }}@endif</span>
    </div>
    @if($link = $fulfillment->trackingLink())
      <a class="btn btn--primary otrack__btn" href="{{ $link }}" target="_blank" rel="noopener">{{ __('Volg je pakket') }} <svg width="16" height="16" aria-hidden="true"><use href="#i-arrow"/></svg></a>
    @endif
  </div>
@endforeach
