@php
    $canonicalSizes = collect(['XS', 'S', 'M', 'L', 'XL']);
    $products = collect((array) ($products ?? []));
    $filterColors = $products
        ->flatMap(fn ($product) => (array) data_get($product, 'colors', []))
        ->filter(fn ($color) => (bool) data_get($color, 'sellable'))
        ->map(fn ($color) => [
            'label' => trim((string) data_get($color, 'label')),
            'hex' => trim((string) data_get($color, 'hex')) ?: '#ead8cf',
        ])
        ->filter(fn ($color) => $color['label'] !== '')
        ->unique('label')
        ->take(8)
        ->values();
    $filterSizes = $products
        ->flatMap(function ($product) {
            return collect((array) data_get($product, 'colors', []))
                ->flatMap(fn ($color) => (array) data_get($color, 'size_options', []));
        })
        ->filter(fn ($option) => (bool) data_get($option, 'in_stock'))
        ->map(fn ($option) => strtoupper(trim((string) data_get($option, 'size'))))
        ->filter(fn ($size) => $canonicalSizes->contains($size))
        ->unique()
        ->sortBy(fn ($size) => $canonicalSizes->search($size))
        ->values();
@endphp

<div class="lxcv1-page lxcv1-shop" data-lxcv1-page="shop">
    <section class="lxcv1-shop-intro" data-lxcv1-reveal>
        <div>
            <p class="lxcv1-kicker">LIN XÉN / CURATED WARDROBE</p>
            <h1>Chọn thiết kế hợp nhịp sống của bạn</h1>
            <p>Mỗi thiết kế hiển thị màu, size và tồn kho đang có. Bắt đầu từ nhóm bạn cần, rồi tinh chỉnh nhanh theo màu hoặc kích thước.</p>
        </div>
        <a class="lxcv1-shop-intro__search" href="{{ route('commerce.v2.search') }}">
            <span>Tìm theo tên hoặc mã RS</span>
            <b aria-hidden="true">⌕</b>
        </a>
    </section>

    <section class="lxcv1-shop-layout" data-lxcv1-shop>
        <aside class="lxcv1-shop-rail" aria-label="Khám phá theo nhóm thiết kế">
            <div class="lxcv1-shop-rail__heading">
                <small>KHÁM PHÁ THEO NHÓM</small>
                <strong>Thiết kế dành cho bạn</strong>
            </div>

            <nav class="lxcv1-shop-collections">
                <a class="is-active" href="{{ route('commerce.v2.shop') }}">
                    <span>Tất cả thiết kế</span>
                    <b>{{ $products->count() }}</b>
                </a>
                @foreach((array) ($collections ?? []) as $collection)
                    <a href="{{ data_get($collection, 'url') }}">
                        <span>{{ data_get($collection, 'name') }}</span>
                        <b aria-hidden="true">↗</b>
                    </a>
                @endforeach
            </nav>

            <div class="lxcv1-shop-rail__note">
                <span aria-hidden="true">✦</span>
                <p>Nhóm sản phẩm được lấy từ bộ sưu tập đã biên tập trong hệ thống LIN XÉN.</p>
            </div>
        </aside>

        <div class="lxcv1-shop-results">
            <header class="lxcv1-shop-toolbar">
                <div>
                    <p class="lxcv1-kicker">THIẾT KẾ ĐANG SẴN HÀNG</p>
                    <h2>Tất cả thiết kế</h2>
                    <small data-lxcv1-shop-count>{{ $products->count() }} thiết kế đang hiển thị</small>
                </div>
                <button
                    class="lxcv1-shop-filter-toggle"
                    type="button"
                    data-lxcv1-shop-filter-toggle
                    aria-expanded="false"
                    aria-controls="lxcv1ShopFilters"
                >
                    <span aria-hidden="true">☷</span>
                    Bộ lọc
                </button>
            </header>

            <form
                class="lxcv1-shop-filters"
                id="lxcv1ShopFilters"
                data-lxcv1-shop-filters
                method="get"
                action="{{ route('commerce.v2.shop') }}"
                hidden
            >
                @if(!empty($dnaFacets))
                    <div class="lxcv1-shop-filter-group lxcv1-shop-filter-group--dna">
                        <span>Chọn theo DNA thiết kế</span>
                        <div class="lxcv1-filter-dna-list">
                            @foreach($dnaFacets as $facet)
                                @php
                                    $facetKey = (string) data_get($facet, 'key');
                                    $facetLabel = (string) data_get($facet, 'label');
                                    $activeValue = (string) data_get($activeDnaFilters ?? [], $facetKey);
                                @endphp
                                @if($facetKey !== '')
                                    <fieldset class="lxcv1-filter-dna-group">
                                        <legend>{{ $facetLabel }}</legend>
                                        <div>
                                            <label class="lxcv1-filter-dna-option">
                                                <input
                                                    type="radio"
                                                    name="{{ $facetKey }}"
                                                    value=""
                                                    @checked($activeValue === '')
                                                >
                                                <span>Tất cả</span>
                                            </label>
                                            @foreach((array) data_get($facet, 'options', []) as $option)
                                                @php
                                                    $optionKey = (string) data_get($option, 'key');
                                                @endphp
                                                @if($optionKey !== '')
                                                    <label class="lxcv1-filter-dna-option">
                                                        <input
                                                            type="radio"
                                                            name="{{ $facetKey }}"
                                                            value="{{ $optionKey }}"
                                                            @checked($activeValue === $optionKey)
                                                        >
                                                        <span>{{ data_get($option, 'label') }}</span>
                                                    </label>
                                                @endif
                                            @endforeach
                                        </div>
                                    </fieldset>
                                @endif
                            @endforeach
                        </div>
                        <div class="lxcv1-filter-dna-actions">
                            <button class="lxcv1-filter-dna-apply" type="submit">Tìm thiết kế phù hợp</button>
                            @if(!empty($activeDnaFilters))
                                <a href="{{ route('commerce.v2.shop') }}">Xóa lọc DNA</a>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="lxcv1-shop-filter-group">
                    <span>Trạng thái</span>
                    <label class="lxcv1-filter-option">
                        <input type="checkbox" value="1" data-lxcv1-filter-stock checked>
                        <i aria-hidden="true"></i>
                        Chỉ hiển thị còn hàng
                    </label>
                </div>

                @if($filterSizes->isNotEmpty())
                    <div class="lxcv1-shop-filter-group">
                        <span>Kích thước còn hàng</span>
                        <div class="lxcv1-filter-size-list">
                            @foreach($filterSizes as $size)
                                <label class="lxcv1-filter-size">
                                    <input type="checkbox" value="{{ $size }}" data-lxcv1-filter-size>
                                    <span>{{ $size }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($filterColors->isNotEmpty())
                    <div class="lxcv1-shop-filter-group">
                        <span>Màu sắc đang có</span>
                        <div class="lxcv1-filter-color-list">
                            @foreach($filterColors as $color)
                                <label class="lxcv1-filter-color">
                                    <input type="checkbox" value="{{ $color['label'] }}" data-lxcv1-filter-color>
                                    <i style="--lxcv1-filter-swatch:{{ $color['hex'] }}" aria-hidden="true"></i>
                                    <span>{{ $color['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <button class="lxcv1-shop-filter-reset" type="reset" data-lxcv1-shop-filter-reset>Đặt lại</button>
            </form>

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

            <div class="lxcv1-shop-filter-empty" data-lxcv1-shop-filter-empty hidden>
                <span aria-hidden="true">✦</span>
                <h3>Chưa có thiết kế phù hợp bộ lọc này</h3>
                <p>Hãy thử bỏ bớt một lựa chọn hoặc khám phá một nhóm thiết kế khác.</p>
                <button type="button" data-lxcv1-shop-filter-reset>Đặt lại bộ lọc</button>
            </div>

            @if(!empty($pagination['has_more']) && !empty($pagination['next_cursor']))
                @php
                    $nextQuery = array_filter(array_merge(
                        request()->query(),
                        ['cursor' => $pagination['next_cursor']]
                    ));
                @endphp
                <div
                    class="lxcv1-shop-load-more"
                    data-lxcv1-shop-load-more
                    data-next-url="{{ route('commerce.v2.shop', $nextQuery) }}"
                >
                    <span class="lxcv1-shop-load-more__spinner" aria-hidden="true"></span>
                    <p>Đang chuẩn bị thêm thiết kế cho bạn…</p>
                    <a href="{{ route('commerce.v2.shop', $nextQuery) }}">Xem thêm sản phẩm</a>
                </div>
            @endif
        </div>
    </section>
</div>
