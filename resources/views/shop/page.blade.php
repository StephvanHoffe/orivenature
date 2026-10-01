@extends('shop.layouts.app')
@section('title', $page->seo_title ?: $page->title)
@section('description', $page->seo_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $page->body), 160))
@section('content')
  <div class="section page">
    <div class="container container--narrow">
      <header class="page-head" data-reveal><h1 class="h2 h2--xl">{{ $page->title }}</h1></header>
      <div class="rte" data-reveal>{!! $page->body !!}</div>
      @if(in_array($page->template, ['contact', 'wholesale'], true))
        @include('shop.partials.contact-form', ['type' => $page->template])
      @endif
    </div>
  </div>
@endsection
