@extends('commerce_v2.layouts.app')

@section('content')
@php
    $rules = collect((array) $rules);
    $items = collect((array) data_get($result, 'items', []));
    $pagination = (array) data_get($result, 'pagination', []);
@endphp

<section class="lxv2-page-head">
    <p class="lxv2-eyebrow">Discover</p>
    <h1>{{ data_get($result, 'feed.name', 'Khám phá') }}</h1>
    <p>Sắp xếp từ canonical catalog, tồn kho và local order facts.</p>
</section>

<nav class="lxv2-discover-tabs" aria-label="Discover feeds">
    @foreach($rules as $rule)
        <a
            href="{{ route(
                'commerce.v2.discover',
                ['feed' => data_get($rule, 'code')]
            ) }}"
            @class([
                'active' => data_get($rule, 'code') === $activeFeed,
            ])
        >
            {{ data_get($rule, 'name') }}
        </a>
    @endforeach
</nav>

@if(!empty($discoverError))
    <div class="lxv2-alert lxv2-alert--error">
        {{ $discoverError }}
    </div>
@endif

@if($items->isEmpty())
    <section class="lxv2-empty">
        <h2>Feed đang được cập nhật</h2>
        <p>Discover index chưa có sản phẩm phù hợp.</p>
    </section>
@else
    <section class="lxv2-grid">
        @foreach($items as $product)
            @include(
                'commerce_v2.partials.product-card',
                ['product' => $product]
            )
        @endforeach
    </section>

    @if(data_get($pagination, 'has_more') && data_get($pagination, 'next_cursor'))
        <div class="lxv2-pagination">
            <a
                class="lxv2-button"
                href="{{ route('commerce.v2.discover', [
                    'feed' => $activeFeed,
                    'cursor' => data_get($pagination, 'next_cursor'),
                ]) }}"
            >
                Xem thêm
            </a>
        </div>
    @endif
@endif
@endsection
