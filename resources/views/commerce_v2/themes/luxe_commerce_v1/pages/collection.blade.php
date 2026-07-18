<div class="lxcv1-page" data-lxcv1-page="collection">
    <section
        class="lxcv1-collection-hero"
        @if(data_get($collection, 'hero_image'))
            style="--lxcv1-collection-hero:url('{{ data_get($collection, 'hero_image') }}')"
        @endif
    >
        <div>
            <p class="lxcv1-kicker">BỘ SƯU TẬP</p>
            <h1>{{ data_get($collection, 'name') }}</h1>
            @if(data_get($collection, 'description'))
                <p>{{ data_get($collection, 'description') }}</p>
            @endif
        </div>
    </section>

    <section class="lxcv1-section lxcv1-section--catalog">
        <div class="lxcv1-product-grid" data-lxcv1-product-grid>
            @forelse($products as $product)
                @include(
                    'commerce_v2.themes.luxe_commerce_v1.partials.product-card',
                    ['product' => $product]
                )
            @empty
                <div class="lxcv1-empty">
                    Bộ sưu tập này chưa có sản phẩm sẵn sàng bán.
                </div>
            @endforelse
        </div>

        @include('commerce_v2.partials.pagination', ['pagination' => $pagination ?? []])
    </section>
</div>
