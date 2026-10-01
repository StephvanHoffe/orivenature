<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:18px 0;">
  @foreach($order->items as $item)
    <tr>
      <td style="padding:10px 0;border-bottom:1px solid #eee;vertical-align:middle;width:64px;">
        @if($item->image)<img src="{{ $item->imageUrl(120) }}" width="52" alt="" style="display:block;border-radius:10px;background:#c9d6a9;">@endif
      </td>
      <td style="padding:10px 8px;border-bottom:1px solid #eee;font-size:14px;">
        <strong>{{ $item->title }}</strong>@if($item->variant_title)<br><span style="color:#6b6c6b;">{{ $item->variant_title }}</span>@endif
        <br><span style="color:#6b6c6b;">{{ $item->quantity }} × {{ money($item->price) }}</span>
      </td>
      <td align="right" style="padding:10px 0;border-bottom:1px solid #eee;font-size:14px;white-space:nowrap;">{{ money($item->price * $item->quantity) }}</td>
    </tr>
  @endforeach
</table>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.9;">
  <tr><td>{{ __('Subtotaal') }}</td><td align="right">{{ money($order->subtotal) }}</td></tr>
  @if($order->discount_total)<tr><td>{{ __('Korting') }} {{ $order->discount_code ? '('.$order->discount_code.')' : '' }}</td><td align="right">-{{ money($order->discount_total) }}</td></tr>@endif
  <tr><td>{{ __('Verzending') }} ({{ $order->shipping_method }})</td><td align="right">{{ $order->shipping_total ? money($order->shipping_total) : __('Gratis') }}</td></tr>
  @if($order->gift_card_used)<tr><td>{{ __('Cadeaubon') }}</td><td align="right">-{{ money($order->gift_card_used) }}</td></tr>@endif
  @if($order->credit_used)<tr><td>{{ __('Shoptegoed') }}</td><td align="right">-{{ money($order->credit_used) }}</td></tr>@endif
  <tr><td style="font-size:17px;padding-top:6px;"><strong>{{ __('Totaal') }}</strong></td><td align="right" style="font-size:17px;padding-top:6px;"><strong>{{ money($order->total) }}</strong></td></tr>
  <tr><td colspan="2" style="color:#6b6c6b;font-size:12px;">{{ __('Inclusief :amount btw', ['amount' => money($order->tax_total)]) }}</td></tr>
</table>
