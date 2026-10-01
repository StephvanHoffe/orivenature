@extends('shop.layouts.app')
@section('template', 'index')
@section('content')
  @foreach($sections as $section)
    @includeIf('shop.sections.'.$section['type'], ['data' => $section['data'] ?? []])
  @endforeach
@endsection
