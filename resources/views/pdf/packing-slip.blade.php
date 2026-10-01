<!doctype html>
<html lang="nl"><head><meta charset="utf-8"><title>Pakbon {{ $order->number }}</title>@include('pdf._style')</head>
<body>
  <table class="top"><tr>
    <td>
      @if(is_file(public_path('storefront/img/logo.png')))<img class="logo" src="{{ public_path('storefront/img/logo.png') }}" alt=""><br><br>@endif
      <h1>Pakbon</h1>
      <div class="muted">Bestelling {{ $order->name }} · {{ $order->placed_at?->format('d-m-Y') }}</div>
      <div class="muted">{{ $order->shipping_method }}</div>
    </td>
    <td style="text-align:right;width:45%">
      <div class="muted">Bezorgadres</div>
      <strong>{!! nl2br(e($order->formattedShippingAddress())) !!}</strong>
    </td>
  </tr></table>
  <table class="lines">
    <thead><tr><th style="width:24px"></th><th>Artikel</th><th>SKU</th><th class="num">Aantal</th></tr></thead>
    <tbody>
      @foreach($order->items as $item)
        <tr><td><span class="check"></span></td><td>{{ $item->title }}@if($item->variant_title) – {{ $item->variant_title }}@endif</td><td>{{ $item->sku }}</td><td class="num"><strong>{{ $item->quantity }}</strong></td></tr>
      @endforeach
    </tbody>
  </table>
  @if($order->customer_note)<p class="box" style="margin-top:20px"><strong>Opmerking van de klant:</strong><br>{{ $order->customer_note }}</p>@endif
  <p style="margin-top:30px;font-family:DejaVu Serif,serif;font-size:14px;color:#52572e">Bedankt voor je bestelling en geniet ervan!</p>
  <div class="foot">{{ settings('store.name') }} · {{ settings('store.email') }} · vragen? stuur ons een bericht via WhatsApp</div>
</body></html>
