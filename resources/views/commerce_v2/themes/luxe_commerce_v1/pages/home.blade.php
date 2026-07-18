@php
    $productCollection = collect((array) ($products ?? []))->values();
    $heroProduct = (array) ($productCollection->first() ?: []);
    $supportProduct = (array) ($productCollection->skip(1)->first() ?: []);
    $collectionItems = collect((array) ($collections ?? []))->values();
@endphp

<div class="lxcv1-page" data-lxcv1-page="home">
    <section class="lxcv1-home-hero" data-lxcv1-reveal>
        <div class="lxcv1-home-hero__copy">
            <p class="lxcv1-kicker">LIN XÉN · NEW COMMERCE EXPERIENCE</p>
            <h1>Thiết kế để bạn nổi bật theo cách riêng.</h1>
            <p>
                Khám phá váy thiết kế với màu, size, giá và tồn kho được cập nhật từ hệ thống chính thức.
            </p>
            <div class="lxcv1-actions">
                <a class="lxcv1-button lxcv1-button--dark" href="{{ route('commerce.v2.shop') }}">
                    Xem sản phẩm
                </a>
                <a class="lxcv1-button lxcv1-button--text" href="{{ route('commerce.v2.discover') }}">
                    Khám phá đề xuất
                </a>
            </div>
            <div class="lxcv1-home-hero__facts">
                <span><strong>Exact SKU</strong><small>Đúng màu và size</small></span>
                <span><strong>COD</strong><small>Thanh toán khi nhận</small></span>
                <span><strong>ERP Truth</strong><small>Giá và tồn được kiểm tra</small></span>
            </div>
        </div>

        <div class="lxcv1-home-hero__visual">
            @if(data_get($heroProduct, 'cover_url'))
                <a class="lxcv1-home-hero__main" href="{{ data_get($heroProduct, 'url') }}">
                    <img
                        src="{{ data_get($heroProduct, 'cover_url') }}"
                        alt="{{ data_get($heroProduct, 'name') }}"
                        width="900"
                        height="1125"
                        fetchpriority="high"
                    >
                    <span>
                        <small>{{ data_get($heroProduct, 'code') }}</small>
                        <strong>{{ data_get($heroProduct, 'short_name') ?: data_get($heroProduct, 'name') }}</strong>
                    </span>
                </a>
            @else
                <div class="lxcv1-home-hero__placeholder">
                    <span>LIN</span><span>XÉN</span>
                </div>
            @endif

            @if(data_get($supportProduct, 'cover_url'))
                <a class="lxcv1-home-hero__support" href="{{ data_get($supportProduct, 'url') }}">
                    <img
                        src="{{ data_get($supportProduct, 'cover_url') }}"
                        alt="{{ data_get($supportProduct, 'name') }}"
                        width="360"
                        height="450"
                    >
                </a>
            @endif
        </div>
    </section>

    @if($collectionItems->isNotEmpty())
        <section class="lxcv1-section" data-lxcv1-reveal>
            <header class="lxcv1-section__head">
                <div>
                    <p class="lxcv1-kicker">Chọn theo cảm hứng</p>
                    <h2>Bộ sưu tập</h2>
                </div>
                <a href="{{ route('commerce.v2.shop') }}">Xem toàn bộ →</a>
            </header>

            <div class="lxcv1-collection-rail">
                @foreach($collectionItems as $index => $collection)
                    <a
                        class="lxcv1-collection-card lxcv1-collection-card--{{ ($index % 3) + 1 }}"
                        href="{{ data_get($collection, 'url') }}"
                        @if(data_get($collection, 'hero_image'))
                            style="--lxcv1-collection-image:url('{{ data_get($collection, 'hero_image') }}')"
                        @endif
                    >
                        <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <div>
                            <strong>{{ data_get($collection, 'name') }}</strong>
                            @if(data_get($collection, 'description'))
                                <small>{{ \Illuminate\Support\Str::limit(data_get($collection, 'description'), 80) }}</small>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="lxcv1-section" data-lxcv1-reveal>
        <header class="lxcv1-section__head">
            <div>
                <p class="lxcv1-kicker">Mới cập nhật</p>
                <h2>Thiết kế nổi bật</h2>
            </div>
            <a href="{{ route('commerce.v2.shop') }}">Xem tất cả →</a>
        </header>

        <div class="lxcv1-product-grid" data-lxcv1-product-grid>
            @forelse($productCollection as $product)
                @include(
                    'commerce_v2.themes.luxe_commerce_v1.partials.product-card',
                    ['product' => $product]
                )
            @empty
                <div class="lxcv1-empty">
                    Chưa có sản phẩm sẵn sàng hiển thị.
                </div>
            @endforelse
        </div>
    </section>

    <section class="lxcv1-brand-strip" data-lxcv1-reveal>
        <div>
            <p class="lxcv1-kicker">Mua hàng rõ ràng</p>
            <h2>Hình ảnh đẹp cần đi cùng dữ liệu đúng.</h2>
        </div>
        <div>
            <article><strong>01</strong><span>Ảnh sản phẩm đã duyệt</span></article>
            <article><strong>02</strong><span>SKU đúng theo màu và size</span></article>
            <article><strong>03</strong><span>Giá và tồn được kiểm tra lại</span></article>
        </div>
    </section>
</div>
