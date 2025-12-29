@extends('storefront.luxe.layouts.app')

@section('content')

{{-- ===================================================== --}}
{{-- CATEGORY HERO --}}
{{-- ===================================================== --}}
<section class="lx-category-hero">

    @if(!empty($collection['hero']))
        <img src="{{ $collection['hero'] }}"
             alt="{{ $collection['name'] }}">
    @endif

    <div class="lx-category-hero-text">
        <h1>{{ $collection['name'] }}</h1>

        @if(!empty($collection['description']))
            <p>{{ $collection['description'] }}</p>
        @endif
    </div>

</section>
