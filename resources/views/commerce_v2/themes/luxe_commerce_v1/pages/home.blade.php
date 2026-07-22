@php
    $productCollection = collect((array) ($products ?? []))
        ->filter(fn ($product) => data_get($product, 'cover_url'))
        ->values();
    $homePagination = (array) ($pagination ?? []);
    $hasMoreProducts = (bool) data_get(
        $homePagination,
        'has_more',
        false
    );
    $nextCursor = (string) data_get(
        $homePagination,
        'next_cursor',
        ''
    );
@endphp

<div
    class="lxcv1-page lxh3-home"
    data-lxcv1-page="home"
    data-lxhome-experience="video-catalog-v1"
>
    <section class="lxh3-video-hero" aria-label="LIN XÉN video">
        <video
            muted
            loop
            playsinline
            preload="none"
            poster="{{ asset('themes/luxe/assets/images/home/hero-poster.webp') }}"
            data-lxhome-hero-video
            aria-label="Bộ sưu tập LIN XÉN"
        >
            <source
                data-src="{{ asset('themes/luxe/assets/images/home/herovideo1.mp4') }}"
                type="video/mp4"
            >
        </video>
    </section>

    <section
        id="lxh3-products"
        class="lxh3-catalog"
        data-lxhome-feed
        data-endpoint="{{ route('commerce.v2.home.products') }}"
        data-next-cursor="{{ $nextCursor }}"
        data-has-more="{{ $hasMoreProducts ? 'true' : 'false' }}"
    >
        <header class="lxh3-catalog__head">
            <h1>Sản phẩm</h1>
            <span data-lxhome-count>{{ $productCollection->count() }} thiết kế</span>
        </header>

        <div class="lxh3-product-table" data-lxhome-grid>
            @forelse($productCollection as $product)
                @include(
                    'commerce_v2.themes.luxe_commerce_v1.partials.product-card',
                    [
                        'product' => $product,
                        'eager' => $loop->index < 2,
                    ]
                )
            @empty
                <div class="lxcv1-empty" data-lxhome-empty>
                    Đang chuẩn bị danh sách sản phẩm…
                </div>
            @endforelse
        </div>

        <div
            class="lxh3-feed-loader"
            data-lxhome-sentinel
            @if(!$hasMoreProducts) hidden @endif
        >
            <span data-lxhome-loader-text>Tải thêm sản phẩm</span>
            <button type="button" data-lxhome-load-more>Tải thêm</button>
        </div>

        <p
            class="lxh3-feed-end"
            data-lxhome-end
            @if($hasMoreProducts) hidden @endif
        >
            Đã hiển thị toàn bộ sản phẩm.
        </p>
    </section>
</div>
