@extends('storefront.luxe.layouts.app')

@section('content')

{{-- ===================================================== --}}
{{-- CATEGORY HERO – LIN XÉN --}}
{{-- ===================================================== --}}
<section class="lx-category-hero {{ empty($collection['hero']) ? 'no-hero' : '' }}">

    {{-- HERO IMAGE (OPTIONAL) --}}
    @if(!empty($collection['hero']))
        <img
            src="{{ $collection['hero'] }}"
            alt="{{ $collection['name'] }} LIN XÉN"
            loading="eager">
    @endif

    {{-- HERO TEXT --}}
    <div class="lx-category-hero-text">

        {{-- 🔑 H1 – SEO PRIMARY --}}
        <h1 title="{{ $collection['name'] }} LIN XÉN">
            {{ $collection['name'] }}
        </h1>

        {{-- DESCRIPTION (OPTIONAL) --}}
        @if(!empty($collection['description']))
            <p class="lx-category-desc">
                {{ $collection['description'] }}
            </p>
        @else
            {{-- FALLBACK COPY – KHÔNG ẢNH HƯỞNG SEO --}}
            <p class="lx-category-desc muted">
                Khám phá các thiết kế nữ tính, dễ mặc và tinh tế từ LIN XÉN.
            </p>
        @endif

    </div>

</section>
