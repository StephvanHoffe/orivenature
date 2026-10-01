@php($logo = settings('store.logo'))
<img src="{{ $logo ? \App\Support\Media::url($logo, 400) : asset('storefront/img/logo.png') }}" alt="{{ settings('store.name') }}" width="90" height="28" class="{{ $class ?? '' }}">
