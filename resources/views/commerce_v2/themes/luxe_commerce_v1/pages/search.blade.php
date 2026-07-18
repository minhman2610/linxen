<div class="lxcv1-page" data-lxcv1-page="search">
    <section class="lxcv1-search-hero" data-lxcv1-reveal>
        <p class="lxcv1-kicker">TÌM KIẾM</p>
        <h1>Tìm đúng thiết kế bạn đang nghĩ tới.</h1>

        <form class="lxcv1-search-box" method="get" action="{{ route('commerce.v2.search') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="11" cy="11" r="6"></circle>
                <path d="m16 16 4 4"></path>
            </svg>
            <input
                name="q"
                value="{{ $query }}"
                placeholder="Tên váy, mã RS, SKU hoặc màu..."
                autofocus
                autocomplete="off"
            >
            <button type="submit">Tìm sản phẩm</button>
        </form>

        @if(!empty($validationMessage))
            <p class="lxcv1-form-message">{{ $validationMessage }}</p>
        @elseif($query !== '')
            <p class="lxcv1-search-hero__result">Kết quả cho “{{ $query }}”</p>
        @endif
    </section>

    @if($query !== '')
        <section class="lxcv1-section lxcv1-section--catalog">
            <div class="lxcv1-product-grid" data-lxcv1-product-grid>
                @forelse($products as $product)
                    @include(
                        'commerce_v2.themes.luxe_commerce_v1.partials.product-card',
                        ['product' => $product]
                    )
                @empty
                    <div class="lxcv1-empty">
                        Không tìm thấy sản phẩm phù hợp. Hãy thử mã RS, SKU hoặc từ khóa khác.
                    </div>
                @endforelse
            </div>

            @include('commerce_v2.partials.pagination', ['pagination' => $pagination ?? []])
        </section>
    @endif
</div>
