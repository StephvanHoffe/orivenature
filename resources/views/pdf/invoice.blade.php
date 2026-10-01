<!doctype html>
<html lang="nl"><head><meta charset="utf-8"><title>Factuur {{ $order->number }}</title>@include('pdf._style')</head>
<body>
  <table class="top"><tr>
    <td>
      @if(is_file(public_path('storefront/img/logo.png')))<img class="logo" src="{{ public_path('storefront/img/logo.png') }}" alt=""><br><br>@endif
      <h1>Factuur</h1>
      <div class="muted">Factuurnummer F{{ $order->number }} · Bestelling {{ $order->name }}</div>
      <div class="muted">Factuurdatum {{ ($order->paid_at ?? $order->placed_at)?->format('d-m-Y') }}</div>
    </td>
    <td style="text-align:right">
      <strong>{{ settings('store.legal_name') ?: settings('store.name') }}</strong><br>
      {!! nl2br(e(settings('store.address'))) !!}<br>
      {{ settings('store.email') }}<br>
      @if(settings('store.kvk'))KvK {{ settings('store.kvk') }}<br>@endif
      @if(settings('store.vat_number'))Btw {{ settings('store.vat_number') }}@endif
    </td>
  </tr></table>

  <table style="width:100%"><tr>
    <td style="width:50%;vertical-align:top"><div class="muted">Factuuradres</div>{!! nl2br(e($order->billing_address ? $order->formattedBillingAddress() : $order->formattedShippingAddress())) !!}<br>{{ $order->email }}</td>
    <td style="width:50%;vertical-align:top"><div class="muted">Bezorgadres</div>{!! nl2br(e($order->formattedShippingAddress())) !!}</td>
  </tr></table>

  <table class="lines">
    <thead><tr><th>Omschrijving</th><th class="num">Aantal</th><th class="num">Prijs</th><th class="num">Btw</th><th class="num">Totaal</th></tr></thead>
    <tbody>
      @foreach($order->items as $item)
        <tr>
          <td>{{ $item->title }}@if($item->variant_title) – {{ $item->variant_title }}@endif @if($item->sku)<br><span class="muted">{{ $item->sku }}</span>@endif</td>
          <td class="num">{{ $item->quantity }}</td>
          <td class="num">{{ money($item->price) }}</td>
          <td class="num">{{ rtrim(rtrim(number_format((float) $item->tax_rate, 2, ',', ''), '0'), ',') }}%</td>
          <td class="num">{{ money($item->price * $item->quantity) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <table class="totals">
    <tr><td>Subtotaal</td><td class="num">{{ money($order->subtotal) }}</td></tr>
    @if($order->discount_total)<tr><td>Korting @if($order->discount_code)({{ $order->discount_code }})@endif</td><td class="num">−{{ money($order->discount_total) }}</td></tr>@endif
    <tr><td>Verzending</td><td class="num">{{ $order->shipping_total ? money($order->shipping_total) : 'Gratis' }}</td></tr>
    <tr class="grand"><td>Totaal (incl. btw)</td><td class="num">{{ money($order->grandTotal()) }}</td></tr>
    @foreach($taxes as $rate => $amount)
      <tr class="muted"><td>Waarvan btw {{ rtrim(rtrim(number_format((float) $rate, 2, ',', ''), '0'), ',') }}%</td><td class="num">{{ money($amount) }}</td></tr>
    @endforeach
    @if($order->gift_card_used)<tr><td>Betaald met cadeaubon</td><td class="num">{{ money($order->gift_card_used) }}</td></tr>@endif
    @if($order->credit_used)<tr><td>Betaald met tegoed</td><td class="num">{{ money($order->credit_used) }}</td></tr>@endif
    @if($order->refunded_total)<tr><td>Terugbetaald</td><td class="num">−{{ money($order->refunded_total) }}</td></tr>@endif
  </table>

  <p style="margin-top:28px" class="box">
    @if($order->paid_at)Deze factuur is voldaan op {{ $order->paid_at->format('d-m-Y') }}. Bedankt voor je bestelling!@else Deze bestelling is nog niet betaald.@endif
  </p>
  <div class="foot">{{ settings('store.name') }} · {{ str_replace("\n", ' · ', (string) settings('store.address')) }} · {{ settings('store.email') }}</div>
</body></html>
