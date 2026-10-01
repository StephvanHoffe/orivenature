@if($paginator->hasPages())
  <nav class="pagination" aria-label="{{ __('Paginering') }}">
    @if(! $paginator->onFirstPage())<a class="btn btn--ghost" href="{{ $paginator->previousPageUrl() }}">{{ __('Vorige') }}</a>@endif
    <span class="pagination__info">{{ __('Pagina :current van :total', ['current' => $paginator->currentPage(), 'total' => $paginator->lastPage()]) }}</span>
    @if($paginator->hasMorePages())<a class="btn btn--ghost" href="{{ $paginator->nextPageUrl() }}">{{ __('Volgende') }}</a>@endif
  </nav>
@endif
