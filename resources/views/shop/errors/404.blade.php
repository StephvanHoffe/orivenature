@extends('shop.layouts.app')
@section('title', __('Pagina niet gevonden'))
@section('noindex', true)
@section('content')
  <div class="section not-found">
    <div class="container container--narrow not-found__inner">
      <p class="not-found__code" aria-hidden="true">4<span>0</span>4</p>
      <h1 class="h2 h2--xl">{{ __('Pagina niet gevonden') }}</h1>
      <p>{{ __('Deze pagina bestaat niet (meer). Ontdek onze essences of ga terug naar de start.') }}</p>
      <a class="btn btn--primary btn--lg" href="{{ url('/collections/all') }}">{{ __('Naar de winkel') }} <svg width="18" height="18" aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </div>
  </div>
@endsection
