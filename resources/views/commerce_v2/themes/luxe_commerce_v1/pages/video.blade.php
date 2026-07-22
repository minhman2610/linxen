@php
    $storyProducts = collect((array) ($products ?? []))
        ->filter(fn ($product) => data_get($product, 'cover_url'))
        ->values();
@endphp

<div
    class="lxstory-page"
    data-lxcv1-page="video"
    data-lxstory-experience="image-stories-v1"
>
    <header class="lxstory-header">
        <a href="{{ route('commerce.v2.home') }}" aria-label="Về trang chủ LIN XÉN">
            <span>LX</span>
            <strong>LIN XÉN</strong>
        </a>
        <div>
            <small>IMAGE STORIES</small>
            <b>Vuốt để khám phá</b>
        </div>
        <a class="lxstory-header__cart" href="{{ route('commerce.v2.cart.index') }}" aria-label="Mở giỏ hàng">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M5 7h14l-1 13H6L5 7Z"></path>
                <path d="M9 7a3 3 0 0 1 6 0"></path>
            </svg>
        </a>
    </header>

    @if(!empty($videoError))
        <div class="lxstory-notice">{{ $videoError }}</div>
    @endif

    @if($storyProducts->isNotEmpty())
        <section
            class="lxstory-feed"
            data-lxstory-feed
            aria-label="LIN XÉN Image Stories"
        >
            @foreach($storyProducts as $storyIndex => $product)
                @php
                    $storyImages = collect((array) data_get($product, 'colors', []))
                        ->pluck('cover_url')
                        ->filter()
                        ->prepend(data_get($product, 'cover_url'))
                        ->unique()
                        ->take(5)
                        ->values();
                    $storyImages = $storyImages->isNotEmpty()
                        ? $storyImages
                        : collect([data_get($product, 'cover_url')]);
                @endphp

                <article
                    class="lxstory-item"
                    data-lxstory-item
                    data-story-index="{{ $storyIndex }}"
                    aria-label="{{ data_get($product, 'name') }}"
                >
                    <div class="lxstory-media" aria-live="off">
                        @foreach($storyImages as $imageIndex => $image)
                            <img
                                src="{{ $image }}"
                                alt="{{ data_get($product, 'cover_alt', data_get($product, 'name')) }}"
                                width="900"
                                height="1200"
                                @class(['is-current' => $imageIndex === 0])
                                data-lxstory-frame
                                data-frame-index="{{ $imageIndex }}"
                                @if($storyIndex === 0 && $imageIndex === 0)
                                    fetchpriority="high"
                                @else
                                    loading="lazy"
                                @endif
                                decoding="async"
                            >
                        @endforeach
                    </div>

                    <div class="lxstory-shade" aria-hidden="true"></div>

                    <div class="lxstory-progress" aria-hidden="true">
                        @foreach($storyImages as $imageIndex => $image)
                            <span @class(['is-current' => $imageIndex === 0]) data-lxstory-progress></span>
                        @endforeach
                    </div>

                    <button
                        class="lxstory-pause"
                        type="button"
                        data-lxstory-toggle
                        aria-label="Tạm dừng chuyển ảnh"
                        aria-pressed="false"
                    >
                        <span data-lxstory-toggle-icon>Ⅱ</span>
                    </button>

                    <div class="lxstory-count" aria-hidden="true">
                        {{ str_pad((string) ($storyIndex + 1), 2, '0', STR_PAD_LEFT) }}
                        <span>/</span>
                        {{ str_pad((string) $storyProducts->count(), 2, '0', STR_PAD_LEFT) }}
                    </div>

                    <div class="lxstory-product">
                        <p>{{ data_get($product, 'code') }} · LIN XÉN EDIT</p>
                        <h1>{{ data_get($product, 'short_name') ?: data_get($product, 'name') }}</h1>
                        <div class="lxstory-product__price">
                            <strong>{{ number_format((float) data_get($product, 'price_min'), 0, ',', '.') }}₫</strong>
                            @if(
                                data_get($product, 'has_sale')
                                && data_get($product, 'original_min') > data_get($product, 'price_min')
                            )
                                <del>{{ number_format((float) data_get($product, 'original_min'), 0, ',', '.') }}₫</del>
                            @endif
                        </div>
                        <div class="lxstory-product__actions">
                            <a href="{{ data_get($product, 'url') }}">Xem thiết kế</a>
                            <span>Chạm để xem màu và size</span>
                        </div>
                    </div>

                    <div class="lxstory-swipe-cue" aria-hidden="true">
                        <span></span>
                        Vuốt lên
                    </div>
                </article>
            @endforeach
        </section>
    @else
        <section class="lxstory-empty">
            <span>LX</span>
            <h1>Stories đang được chuẩn bị.</h1>
            <p>Trong lúc chờ, anh có thể khám phá những thiết kế đang sẵn hàng.</p>
            <a href="{{ route('commerce.v2.shop') }}">Xem sản phẩm</a>
        </section>
    @endif
</div>
