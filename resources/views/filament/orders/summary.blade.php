@php($order = $getRecord())
<dl class="ob-sum">
  <div><dt>Subtotaal</dt><dd>{{ money($order->subtotal) }}</dd></div>
  @if($order->discount_total)<div><dt>Korting @if($order->discount_code)<span class="ob-chip">{{ $order->discount_code }}</span>@endif</dt><dd>−{{ money($order->discount_total) }}</dd></div>@endif
  <div><dt>Verzending <span class="ob-muted">{{ $order->shipping_method }}</span></dt><dd>{{ $order->shipping_total ? money($order->shipping_total) : 'Gratis' }}</dd></div>
  <div class="ob-muted"><dt>Waarvan btw</dt><dd>{{ money($order->tax_total) }}</dd></div>
  <div class="ob-sum__total"><dt>Totaal</dt><dd>{{ money($order->grandTotal()) }}</dd></div>
  @if($order->gift_card_used)<div><dt>Betaald met cadeaubon</dt><dd>{{ money($order->gift_card_used) }}</dd></div>@endif
  @if($order->credit_used)<div><dt>Betaald met tegoed</dt><dd>{{ money($order->credit_used) }}</dd></div>@endif
  @foreach($order->payments as $p)
    <div><dt>{{ $p->provider === 'mollie' ? 'Mollie' : ucfirst($p->provider) }} @if($p->method)<span class="ob-muted">{{ $p->method }}</span>@endif <span class="ob-chip {{ $p->status === 'paid' ? 'ob-chip--ok' : '' }}">{{ $p->status }}</span></dt><dd>{{ money($p->amount) }}</dd></div>
  @endforeach
  @foreach($order->refunds as $r)
    <div><dt>Terugbetaald {{ $r->created_at->translatedFormat('j M') }} @if($r->reason)<span class="ob-muted">{{ $r->reason }}</span>@endif</dt><dd>−{{ money($r->amount) }}</dd></div>
  @endforeach
  @if($order->refunded_total)<div class="ob-sum__total"><dt>Netto</dt><dd>{{ money($order->grandTotal() - $order->refunded_total) }}</dd></div>@endif
</dl>
