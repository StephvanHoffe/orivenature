@php($order = $getRecord())
@php($customer = $order->customer)
<div class="ob-customer">
  @if($customer)
    <a href="{{ \App\Filament\Resources\Customers\CustomerResource::getUrl('edit', ['record' => $customer]) }}"><strong>{{ $customer->name ?: $order->email }}</strong></a>
    <span class="ob-muted">{{ $customer->orders()->count() }} {{ $customer->orders()->count() === 1 ? 'bestelling' : 'bestellingen' }} · {{ $customer->hasAccount() ? 'heeft een account' : 'gast' }}</span>
  @else
    <strong>{{ $order->customerName() }}</strong>
  @endif
  <a href="mailto:{{ $order->email }}">{{ $order->email }}</a>
  @if($order->phone)<a href="tel:{{ $order->phone }}">{{ $order->phone }}</a>@endif
  @if($order->accepts_marketing)<span class="ob-chip ob-chip--ok">ontvangt nieuwsbrief</span>@endif
</div>
