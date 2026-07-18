@php
    $ruleItems = collect((array) ($rules ?? []));
    $productItems = collect((array) data_get($result ?? [], 'items', []));
    $discoverPagination = (array) data_get($result ?? [], 'pagination', []);
@endphp

<div class="lxcv1-page" data-lxcv1-page="discover">
    <section class="lxcv1-catalog-hero lxcv1-catalog-hero--discover">
        <div>
            <p class="lxcv1-kicker">DISCOVER</p>
            <h1>{{ data_get($result, 'feed.name', 'Khám phá') }}</h1>
            <p>Đề xuất dựa trên catalog, tồn kho và dữ liệu mua hàng chính thức.</p>
        </div>
    </section>

    <nav class="lxcv1-feed-tabs" aria-label="Discover feeds">
        @foreach($ruleItems as $rule)
            <a
                href="{{ route('commerce.v2.discover', ['feed' => data_get($rule, 'code')]) }}"
                @class(['is-active' => data_get($rule, 'code') === $activeFeed])
            >
                {{ data_get($rule, 'name') }}
            </a>
        @endforeach
    </nav>

    @if(!empty($discoverError))
        <div class="lxcv1-alert lxcv1-alert--error">{{ $discoverError }}</div>
    @endif

    <section class="lxcv1-section lxcv1-section--catalog">
        <div class="lxcv1-product-grid" data-lxcv1-product-grid>
            @forelse($productItems as $product)
                @include(
                    'commerce_v2.themes.luxe_commerce_v1.partials.product-card',
                    ['product' => $product]
                )
            @empty
                <div class="lxcv1-empty">
                    Feed đang được cập nhật.
                </div>
            @endforelse
        </div>

        @if(data_get($discoverPagination, 'has_more') && data_get($discoverPagination, 'next_cursor'))
            <div class="lxcv1-pagination">
                <a
                    class="lxcv1-button lxcv1-button--dark"
                    href="{{ route('commerce.v2.discover', [
                        'feed' => $activeFeed,
                        'cursor' => data_get($discoverPagination, 'next_cursor'),
                    ]) }}"
                >
                    Xem thêm
                </a>
            </div>
        @endif
    </section>
</div>
