@php($icons = ($all ?? false)
  ? ['amex' => 'American Express', 'apple-pay' => 'Apple Pay', 'google-pay' => 'Google Pay', 'ideal-wero' => 'iDEAL | Wero', 'klarna' => 'Klarna', 'maestro' => 'Maestro', 'mastercard' => 'Mastercard', 'paypal' => 'PayPal', 'visa' => 'Visa']
  : ['ideal-wero' => 'iDEAL | Wero', 'klarna' => 'Klarna', 'apple-pay' => 'Apple Pay', 'paypal' => 'PayPal', 'visa' => 'Visa', 'mastercard' => 'Mastercard'])
<ul class="pay-icons {{ $class ?? '' }}" aria-label="{{ __('Betaalmethoden') }}">
  @foreach($icons as $file => $label)
    <li><img src="{{ asset('storefront/img/payment/'.$file.'.svg') }}" alt="{{ $label }}" width="38" height="24" loading="lazy"></li>
  @endforeach
</ul>
