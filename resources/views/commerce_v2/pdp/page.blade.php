@extends('commerce_v2.layouts.app')

@section('og_type', 'product')

@if(data_get($presentation, 'is_preview'))
    @section('robots', 'noindex,nofollow,noarchive')
@endif

@push('head')
    @foreach((array) data_get($presentation, 'assets.styles', []) as $style)
        <link rel="stylesheet" href="{{ asset(preg_replace('/\?.*$/', '', $style)) }}{{ str_contains($style, '?') ? '?'.str($style)->after('?') : '' }}">
    @endforeach
@endpush

@section('content')
@php
    $identity = (array) data_get($pdp, 'identity', []);
    $commerce = (array) data_get($pdp, 'commerce', []);
    $sizeAdvisor = (array) data_get($pdp, 'fit.advisor', []);
@endphp

<article
    class="lxpdp lxpdp-engine"
    data-lxpdp
    data-pdp-engine="linxen_pdp_presentation_engine_v1"
    data-pdp-variant="{{ data_get($presentation, 'key') }}"
    data-pdp-variant-version="{{ data_get($presentation, 'version') }}"
    data-pdp-resolved-source="{{ data_get($presentation, 'resolved_source') }}"
    data-size-advice-url="{{ data_get($sizeAdvisor, 'endpoint_url') }}"
>
    @if(data_get($presentation, 'is_preview'))
        <aside class="lxpdp-preview-banner" role="status">
            <div>
                <strong>Đang xem bản preview</strong>
                <span>{{ data_get($presentation, 'label') }} · {{ data_get($presentation, 'version') }}</span>
            </div>
            <a href="{{ route('commerce.v2.product', ['slug' => data_get($identity, 'slug')]) }}">Mở giao diện đang chạy</a>
        </aside>
    @endif

    <nav class="lxpdp__breadcrumb" aria-label="Đường dẫn">
        <a href="{{ route('commerce.v2.home') }}">Trang chủ</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('commerce.v2.shop') }}">Sản phẩm</a>
        <span aria-hidden="true">/</span>
        <span>{{ data_get($identity, 'short_name') ?: data_get($identity, 'name') }}</span>
    </nav>

    @foreach((array) data_get($presentation, 'sections', []) as $section)
        <section
            class="lxpdp-engine-section lxpdp-engine-section--{{ data_get($section, 'key') }}"
            data-pdp-section="{{ data_get($section, 'key') }}"
        >
            @include(data_get($section, 'view'), [
                'pdp' => $pdp,
                'product' => $product,
                'presentation' => $presentation,
                'section' => $section,
            ])
        </section>
    @endforeach

    <div class="lxpdp-mobile-buy" data-lxpdp-mobile-buy>
        <div>
            <strong>{{ number_format((float) data_get($commerce, 'price.min'), 0, ',', '.') }}₫</strong>
            <span data-lxpdp-mobile-selection>Chọn màu và size</span>
        </div>
        <button type="button" data-lxpdp-mobile-submit disabled>
            Thêm vào giỏ
        </button>
    </div>

    @include('commerce_v2.pdp.sections.shared-size-advisor', [
        'pdp' => $pdp,
        'product' => $product,
    ])
</article>

<script type="application/json" id="lxv2ProductData">{!! $productPayloadJson !!}</script>
@endsection

@push('scripts')
    @foreach((array) data_get($presentation, 'assets.scripts', []) as $script)
        <script type="module" src="{{ asset(preg_replace('/\?.*$/', '', $script)) }}{{ str_contains($script, '?') ? '?'.str($script)->after('?') : '' }}"></script>
    @endforeach
@endpush
