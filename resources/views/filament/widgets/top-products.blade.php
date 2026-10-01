<x-filament-widgets::widget>
  <x-filament::section heading="Best verkocht" description="Laatste 30 dagen">
    @if($rows->isEmpty())
      <p class="ob-muted">Nog geen verkopen in deze periode.</p>
    @else
      <table class="ob-table">
        <thead><tr><th>Product</th><th class="num">Aantal</th><th class="num">Omzet</th></tr></thead>
        <tbody>
          @foreach($rows as $row)
            <tr><td>{{ $row->title }}@if($row->variant_title) <span class="ob-muted">{{ $row->variant_title }}</span>@endif</td><td class="num">{{ $row->qty }}</td><td class="num">{{ money((int) $row->revenue) }}</td></tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </x-filament::section>
</x-filament-widgets::widget>
