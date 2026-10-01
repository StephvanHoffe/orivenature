<x-filament-widgets::widget>
  <x-filament::section heading="Verkooptrechter" description="Laatste 30 dagen">
    <div class="ob-funnel">
      @foreach($steps as $label => $count)
        <div class="ob-funnel__row">
          <span>{{ $label }}</span>
          <span class="ob-funnel__bar"><span style="width: {{ max(2, round($count / $max * 100)) }}%"></span></span>
          <strong>{{ number_format($count, 0, ',', '.') }}</strong>
        </div>
      @endforeach
    </div>
  </x-filament::section>
</x-filament-widgets::widget>
