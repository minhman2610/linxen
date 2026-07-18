@php
    $productCollection = collect((array) ($products ?? []))
        ->filter(fn ($product) => data_get($product, 'cover_url'))
        ->values();
    $collectionItems = collect((array) ($collections ?? []))
        ->filter(fn ($collection) => data_get($collection, 'url'))
        ->take(6)
        ->values();

    $heroProduct = (array) ($productCollection->first() ?: []);
    $heroSupportProducts = $productCollection->skip(1)->take(2)->values();
    $saleProducts = $productCollection
        ->filter(fn ($product) => (bool) data_get($product, 'has_sale'))
        ->values();
    $pulseProducts = ($saleProducts->isNotEmpty()
        ? $saleProducts
        : $productCollection
    )->take(6)->values();
    $featuredProducts = $productCollection->take(12)->values();
    $storyProducts = $productCollection->take(3)->values();

    $heroColors = collect((array) data_get($heroProduct, 'colors', []))
        ->filter(fn ($color) => (bool) data_get($color, 'sellable'))
        ->values();
    $heroSizes = $heroColors
        ->flatMap(fn ($color) => (array) data_get($color, 'available_sizes', []))
        ->filter()
        ->unique()
        ->values();

    $pulseKicker = $saleProducts->isNotEmpty()
        ? 'Giá tốt đang áp dụng'
        : 'Đang được quan tâm';
@endphp

<div
    class="lxcv1-page lxh2-home"
    data-lxcv1-page="home"
    data-lxcv1-home-experience="editorial-commerce-v2"
>
    <section class="lxh2-hero" data-lxcv1-reveal>
        <div class="lxh2-hero__copy">
            <p class="lxcv1-kicker">LIN XÉN · THE DAILY EDIT</p>
            <h1>Mặc đẹp<br>không cần ồn ào.</h1>
            <p class="lxh2-hero__lead">
                Những thiết kế có đường nét rõ ràng, dễ mặc và đủ khác biệt
                để bạn được nhớ đến theo cách riêng.
            </p>

            <div class="lxcv1-actions lxh2-hero__actions">
                @if(data_get($heroProduct, 'url'))
                    <a
                        class="lxcv1-button lxcv1-button--dark"
                        href="{{ data_get($heroProduct, 'url') }}"
                    >
                        Xem thiết kế mở màn
                    </a>
                @endif
                <a
                    class="lxcv1-button lxcv1-button--text"
                    href="{{ route('commerce.v2.shop') }}"
                >
                    Khám phá tất cả →
                </a>
            </div>

            @if($heroProduct !== [])
                <div class="lxh2-hero__truth" aria-label="Thông tin thiết kế mở màn">
                    <span>
                        <small>Mã thiết kế</small>
                        <strong>{{ data_get($heroProduct, 'code') }}</strong>
                    </span>
                    <span>
                        <small>Màu đang có</small>
                        <strong>{{ $heroColors->count() ?: '—' }}</strong>
                    </span>
                    <span>
                        <small>Size đang có</small>
                        <strong>{{ $heroSizes->isNotEmpty() ? $heroSizes->join(' · ') : '—' }}</strong>
                    </span>
                </div>
            @endif
        </div>

        <div class="lxh2-hero__visual" aria-label="Thiết kế nổi bật">
            @if(data_get($heroProduct, 'cover_url'))
                <a
                    class="lxh2-hero__main"
                    href="{{ data_get($heroProduct, 'url') }}"
                >
                    <img
                        src="{{ data_get($heroProduct, 'cover_url') }}"
                        alt="{{ data_get($heroProduct, 'cover_alt', data_get($heroProduct, 'name')) }}"
                        width="960"
                        height="1200"
                        fetchpriority="high"
                        decoding="async"
                    >
                    <span class="lxh2-hero__caption">
                        <small>{{ data_get($heroProduct, 'code') }}</small>
                        <strong>{{ data_get($heroProduct, 'short_name') ?: data_get($heroProduct, 'name') }}</strong>
                        <b>
                            {{ number_format((float) data_get($heroProduct, 'price_min'), 0, ',', '.') }}₫
                        </b>
                    </span>
                </a>
            @else
                <div class="lxh2-hero__placeholder" aria-hidden="true">
                    <span>LIN</span><span>XÉN</span>
                </div>
            @endif

            @foreach($heroSupportProducts as $index => $supportProduct)
                <a
                    class="lxh2-hero__support lxh2-hero__support--{{ $index + 1 }}"
                    href="{{ data_get($supportProduct, 'url') }}"
                    aria-label="Xem {{ data_get($supportProduct, 'name') }}"
                >
                    <img
                        src="{{ data_get($supportProduct, 'cover_url') }}"
                        alt="{{ data_get($supportProduct, 'cover_alt', data_get($supportProduct, 'name')) }}"
                        width="360"
                        height="450"
                        loading="lazy"
                        decoding="async"
                    >
                    <span>{{ data_get($supportProduct, 'short_name') ?: data_get($supportProduct, 'name') }}</span>
                </a>
            @endforeach

            <div class="lxh2-hero__signal" aria-hidden="true">
                <span>NEW</span>
                <strong>26</strong>
            </div>
        </div>
    </section>

    @if($pulseProducts->isNotEmpty())
        <section
            class="lxh2-pulse"
            data-lxcv1-reveal
            data-lxh2-commercial-rail
        >
            <header class="lxh2-section-head">
                <div>
                    <p class="lxcv1-kicker">{{ $pulseKicker }}</p>
                    <h2>Một lượt xem nhanh,<br>nhiều lý do để dừng lại.</h2>
                </div>
                <a href="{{ route('commerce.v2.discover') }}">Xem đề xuất →</a>
            </header>

            <div class="lxh2-pulse__rail" aria-label="{{ $pulseKicker }}">
                @foreach($pulseProducts as $index => $product)
                    <article class="lxh2-pulse-card">
                        <a href="{{ data_get($product, 'url') }}">
                            <figure>
                                <img
                                    src="{{ data_get($product, 'cover_url') }}"
                                    alt="{{ data_get($product, 'cover_alt', data_get($product, 'name')) }}"
                                    width="520"
                                    height="650"
                                    loading="lazy"
                                    decoding="async"
                                >
                                <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            </figure>
                            <div>
                                <small>{{ data_get($product, 'code') }}</small>
                                <h3>{{ data_get($product, 'short_name') ?: data_get($product, 'name') }}</h3>
                                <p>
                                    <strong>{{ number_format((float) data_get($product, 'price_min'), 0, ',', '.') }}₫</strong>
                                    @if(
                                        data_get($product, 'has_sale')
                                        && data_get($product, 'original_min') > data_get($product, 'price_min')
                                    )
                                        <del>{{ number_format((float) data_get($product, 'original_min'), 0, ',', '.') }}₫</del>
                                    @endif
                                </p>
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if($collectionItems->isNotEmpty())
        <section
            class="lxh2-collections"
            data-lxcv1-reveal
            data-lxh2-collection-journey
        >
            <header class="lxh2-section-head lxh2-section-head--compact">
                <div>
                    <p class="lxcv1-kicker">Chọn theo cảm hứng</p>
                    <h2>Không cần biết chính xác mình tìm gì.</h2>
                </div>
                <p>Hãy bắt đầu từ cảm giác bạn muốn mang theo hôm nay.</p>
            </header>

            <div class="lxh2-collection-grid">
                @foreach($collectionItems as $index => $collection)
                    <a
                        class="lxh2-collection-card lxh2-collection-card--{{ ($index % 4) + 1 }}"
                        href="{{ data_get($collection, 'url') }}"
                        @if(data_get($collection, 'hero_image'))
                            style="--lxh2-collection-image:url('{{ data_get($collection, 'hero_image') }}')"
                        @endif
                    >
                        <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <div>
                            <strong>{{ data_get($collection, 'name') }}</strong>
                            @if(data_get($collection, 'description'))
                                <small>{{ \Illuminate\Support\Str::limit(data_get($collection, 'description'), 90) }}</small>
                            @endif
                        </div>
                        <b>Khám phá ↗</b>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="lxh2-featured" data-lxcv1-reveal>
        <header class="lxh2-section-head">
            <div>
                <p class="lxcv1-kicker">The current selection</p>
                <h2>Thiết kế đáng để<br>xem kỹ hơn.</h2>
            </div>
            <a href="{{ route('commerce.v2.shop') }}">Xem toàn bộ →</a>
        </header>

        <div class="lxcv1-product-grid lxh2-product-grid" data-lxcv1-product-grid>
            @forelse($featuredProducts as $product)
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

    @if($storyProducts->isNotEmpty())
        <section
            class="lxh2-story"
            data-lxcv1-reveal
            data-lxh2-editorial-story
        >
            <div class="lxh2-story__intro">
                <p class="lxcv1-kicker">LIN XÉN POINT OF VIEW</p>
                <h2>Đẹp không phải là thêm nhiều.<br>Đẹp là chọn đúng.</h2>
                <p>
                    Chúng tôi bắt đầu từ tỷ lệ, đường nét và cảm giác khi mặc.
                    Mỗi thiết kế dưới đây mở ra một cách khác để bạn bước vào ngày mới.
                </p>
                <a href="{{ route('commerce.v2.discover') }}">Mở trang Khám phá →</a>
            </div>

            <div class="lxh2-story__cards">
                @foreach($storyProducts as $index => $product)
                    <a
                        class="lxh2-story-card lxh2-story-card--{{ $index + 1 }}"
                        href="{{ data_get($product, 'url') }}"
                    >
                        <img
                            src="{{ data_get($product, 'cover_url') }}"
                            alt="{{ data_get($product, 'cover_alt', data_get($product, 'name')) }}"
                            width="640"
                            height="800"
                            loading="lazy"
                            decoding="async"
                        >
                        <span>
                            <small>0{{ $index + 1 }} · {{ data_get($product, 'code') }}</small>
                            <strong>{{ data_get($product, 'short_name') ?: data_get($product, 'name') }}</strong>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section
        class="lxh2-trust"
        data-lxcv1-reveal
        data-lxh2-trust
    >
        <div class="lxh2-trust__title">
            <p class="lxcv1-kicker">Mua đẹp, mua rõ ràng</p>
            <h2>Cảm xúc ở phía trước.<br>Sự thật ở phía sau.</h2>
        </div>
        <div class="lxh2-trust__items">
            <article>
                <span>01</span>
                <strong>Ảnh đã duyệt</strong>
                <p>Chỉ dùng hình ảnh được phép phục vụ bán hàng.</p>
            </article>
            <article>
                <span>02</span>
                <strong>Đúng màu, đúng size</strong>
                <p>Mỗi lựa chọn gắn với exact sellable SKU.</p>
            </article>
            <article>
                <span>03</span>
                <strong>Giá và tồn được kiểm tra</strong>
                <p>ERP xác nhận lại trước khi đặt hàng.</p>
            </article>
            <article>
                <span>04</span>
                <strong>Thanh toán COD</strong>
                <p>Thanh toán khi nhận hàng theo khả năng hiện hành.</p>
            </article>
        </div>
    </section>
</div>
