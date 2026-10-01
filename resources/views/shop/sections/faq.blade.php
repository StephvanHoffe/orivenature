<section class="section faq" id="faq" aria-labelledby="faq-title">
  <div class="container faq__inner">
    <div class="faq__aside" data-reveal>
      <h2 class="h2 h2--xl" id="faq-title"><em>{{ $data['heading'] ?? '' }}</em></h2>
      @if(!empty($data['help_title']))
        <div class="help-card">
          <span class="help-card__bubble" aria-hidden="true"><svg width="26" height="26"><use href="#i-phone"/></svg></span>
          <h3 class="help-card__title">{{ $data['help_title'] }}</h3>
          <p>{{ $data['help_text'] ?? '' }}</p>
          <div class="help-card__actions">
            @if($wa = \App\Support\Storefront::whatsappUrl())<a class="btn btn--primary" href="{{ $wa }}" target="_blank" rel="noopener"><svg width="18" height="18" aria-hidden="true"><use href="#i-whatsapp"/></svg> Whatsapp</a>@endif
            <a class="btn btn--ghost" href="mailto:{{ settings('store.email') }}"><svg width="18" height="18" aria-hidden="true"><use href="#i-mail"/></svg> {{ settings('store.email') }}</a>
          </div>
        </div>
      @endif
    </div>
    <div class="accordion" data-reveal>
      @foreach($data['questions'] ?? [] as $q)
        <details class="acc">
          <summary>{{ $q['question'] ?? '' }} <span class="acc__icon" aria-hidden="true"></span></summary>
          <div class="acc__body rte">{!! $q['answer'] ?? '' !!}</div>
        </details>
      @endforeach
    </div>
  </div>
</section>
@push('head')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => collect($data['questions'] ?? [])->map(fn ($q) => ['@type' => 'Question', 'name' => $q['question'] ?? '', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags($q['answer'] ?? ''))]])->values()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush
