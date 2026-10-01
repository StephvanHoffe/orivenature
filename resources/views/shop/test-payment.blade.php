@extends('shop.layouts.app')
@section('title', __('Testbetaling'))
@section('noindex', true)
@section('content')
  <div class="section">
    <div class="container container--form">
      <div class="account-card test-payment">
        <p class="pill-label">{{ __('Testmodus') }}</p>
        <h1 class="h2">{{ __('Testbetaling voor :number', ['number' => $payment->order->name]) }}</h1>
        <p>{{ __('Er is nog geen Mollie-sleutel ingesteld, dus de winkel draait in testmodus. Kies hieronder wat er met deze betaling gebeurt. Stel in /beheer > Instellingen > Betalingen je Mollie-sleutel in om echte betalingen te ontvangen.') }}</p>
        <p class="qv__price">{{ money($payment->amount) }}</p>
        <form method="post" action="{{ route('payments.test.complete', $payment->provider_id) }}" class="form-stack">
          @csrf
          <button class="btn btn--primary btn--lg btn--block" name="status" value="paid">{{ __('Betaling geslaagd') }}</button>
          <button class="btn btn--ghost btn--block" name="status" value="failed">{{ __('Betaling mislukt') }}</button>
          <button class="btn btn--ghost btn--block" name="status" value="canceled">{{ __('Betaling geannuleerd') }}</button>
        </form>
      </div>
    </div>
  </div>
@endsection
