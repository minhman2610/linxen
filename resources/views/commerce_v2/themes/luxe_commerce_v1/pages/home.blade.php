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
    <section class="lxh3-ticker" aria-label="Thông tin mua hàng">
        <div class="lxh3-ticker__track">
            @foreach([false, true] as $duplicate)
                <div @if($duplicate) aria-hidden="true" @endif>
                    <span>Ảnh sản phẩm đã duyệt</span>
                    <i aria-hidden="true"></i>
                    <span>Giá và tồn kho từ hệ thống chính thức</span>
                    <i aria-hidden="true"></i>
                    <span>Chọn đúng màu · đúng size · đúng SKU</span>
                    <i aria-hidden="true"></i>
                    <span>Giao hàng toàn quốc</span>
                    <i aria-hidden="true"></i>
                </div>
            @endforeach
        </div>
    </section>

    <section class="lxh3-video-hero" aria-label="LIN XÉN video">
        <video
            autoplay
            muted
            loop
            playsinline
            preload="metadata"
            poster="{{ asset('themes/luxe/assets/images/home/hero-poster.webp') }}"
            data-lxhome-hero-video
        >
            <source
                src="{{ asset('themes/luxe/assets/images/home/herovideo1.mp4') }}"
                type="video/mp4"
            >
        </video>

        <div class="lxh3-video-hero__shade" aria-hidden="true"></div>
        <div class="lxh3-video-hero__mark">
            <small>LIN XÉN · DAILY FORM</small>
            <strong>Thiết kế cho nhịp sống hiện đại.</strong>
            <a href="#lxh3-products">Xem sản phẩm</a>
        </div>
        <button
            class="lxh3-video-hero__sound"
            type="button"
            data-lxhome-video-sound
            aria-label="Bật âm thanh video"
            aria-pressed="false"
        >
            <span data-lxhome-sound-icon>Âm thanh tắt</span>
        </button>
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
                        'eager' => $loop->index < 4,
                    ]
                )
            @empty
                <div class="lxcv1-empty">
                    Sản phẩm đang được cập nhật.
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
