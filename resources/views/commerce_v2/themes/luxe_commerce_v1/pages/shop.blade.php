<div class="lxcv1-page" data-lxcv1-page="shop">
    <section class="lxcv1-catalog-hero" data-lxcv1-reveal>
        <div>
            <p class="lxcv1-kicker">LIN XÉN COLLECTION</p>
            <h1>Tất cả thiết kế</h1>
            <p>Chọn sản phẩm đang sẵn sàng theo dữ liệu giá và tồn kho hiện tại.</p>
        </div>
        <a class="lxcv1-round-link" href="{{ route('commerce.v2.search') }}">
            Tìm theo mã RS
            <span>↗</span>
        </a>
    </section>

    <section class="lxcv1-section lxcv1-section--catalog">
        <div class="lxcv1-product-grid" data-lxcv1-product-grid>
            @forelse($products as $product)
                @include(
                    'commerce_v2.themes.luxe_commerce_v1.partials.product-card',
                    ['product' => $product]
                )
            @empty
                <div class="lxcv1-empty">Chưa tìm thấy sản phẩm sẵn sàng bán.</div>
            @endforelse
        </div>

        @include('commerce_v2.partials.pagination', ['pagination' => $pagination ?? []])
    </section>
</div>
