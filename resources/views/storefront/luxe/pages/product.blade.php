@extends('storefront.luxe.layouts.app')

@section('content')
<section class="lx-product-detail lx-pdp">

    <div class="lx-product-content lx-pdp-content">

        {{-- ================= PRODUCT LAYOUT ================= --}}
        <div class="lx-product-layout">

            {{-- LEFT: GALLERY --}}
            <div class="lx-product-gallery-wrap">
                @include('storefront.luxe.components.product.gallery', [
                    'images'  => $images,
                    'product' => $product
                ])
            </div>

            {{-- RIGHT: INFO --}}
            <div class="lx-product-info">

                {{-- ================= BREADCRUMB ================= --}}
                @include('storefront.luxe.components.product.breadcrumb', [
                    'breadcrumbs' => $breadcrumbs
                ])

                {{-- BRAND HERO --}}
                <div class="lx-brand-hero">
                    <div
                        class="lx-brand-typewriter"
                        data-text="LIN XÉN — CHÚNG TÔI ĐAM MÊ VÁY"
                    >
                        LIN XÉN — CHÚNG TÔI ĐAM MÊ VÁY
                    </div>

                    <h1 class="lx-product-title luxury-title">
                        <span class="lx-title-icon">✦</span>
                        {{ $product['name'] ?? '' }}
                    </h1>
                </div>

                {{-- META --}}
                <div class="lx-product-meta">
                    @if(!empty($product['code']))
                        <span class="lx-meta-tag">
                            MÃ {{ $product['code'] }}
                        </span>
                    @endif
                    <span class="lx-meta-tag accent">
                        THIẾT KẾ LIN XÉN
                    </span>
                </div>

                {{-- PRICE --}}
                <div class="lx-product-price-wrap">
                    <span class="lx-product-price">
                        {{ number_format($product['price'] ?? 0) }}₫
                    </span>
                    <span class="lx-price-note">
                        ĐÃ BAO GỒM VAT
                    </span>
                </div>

                {{-- DESCRIPTION --}}
                <div class="lx-product-description">
                    {!! nl2br(e(
                        $product['description']
                        ?? 'Thiết kế tinh tế, phom dáng chuẩn, dễ mặc trong nhiều hoàn cảnh.'
                    )) !!}
                </div>

                {{-- ================= VARIANTS ================= --}}
                @include('storefront.luxe.components.product.variants', [
                    'attributes' => $attributes,
                    'variants'   => $variants
                ])

                {{-- ================= SIZE GUIDE (CTA + MODAL) ================= --}}
                @include('storefront.luxe.components.product.size-guide')

                {{-- ================= PDP BOTTOM GROUP ================= --}}
                <div class="lx-pdp-bottom">

                    {{-- ACTIONS --}}
                    @include('storefront.luxe.components.product.actions')

                    {{-- TRUST --}}
                    @include('storefront.luxe.components.product.trust')

                    {{-- REAL CUSTOMER GALLERY (FROM 3MG ERP) --}}
                    @include('storefront.luxe.components.product.real-gallery', [
                        'ugcMedia' => $ugcMedia ?? []
                    ])

                    {{-- SUGGESTED PRODUCTS (FROM 3MG ERP) --}}
                    @if(($suggestedCount ?? 0) > 0)
                        @include('storefront.luxe.components.product.suggested-products', [
                            'suggestedProducts' => $suggestedProducts ?? []
                        ])
                    @endif

                </div>

            </div>
        </div>
    </div>

</section>
@endsection

{{-- ================= SIZE GUIDE JS ================= --}}
@push('scripts')
<script>
document.addEventListener('click', function (e) {
    const overlay = document.querySelector('[data-size-guide-overlay]');
    if (!overlay) return;

    // OPEN
    if (e.target.closest('[data-size-guide-open]')) {
        overlay.hidden = false;
        document.body.style.overflow = 'hidden'; // lock scroll
        return;
    }

    // CLOSE bằng nút X
    if (e.target.closest('[data-size-guide-close]')) {
        overlay.hidden = true;
        document.body.style.overflow = '';
        return;
    }

    // CLOSE khi click ra ngoài modal
    if (
        e.target.closest('[data-size-guide-overlay]') &&
        !e.target.closest('.lx-size-guide-modal')
    ) {
        overlay.hidden = true;
        document.body.style.overflow = '';
        return;
    }
});

document.getElementById('size-submit')?.addEventListener('click', function () {

    const h = +document.getElementById('size-height').value;
    const w = +document.getElementById('size-weight').value;
    const bust  = +document.getElementById('size-bust').value;
    const waist = +document.getElementById('size-waist').value;
    const hip   = +document.getElementById('size-hip').value;

    const resultBox = document.getElementById('size-result');

    if (!h || !w || !bust || !waist || !hip) {
        resultBox.hidden = false;
        resultBox.innerHTML = 'Vui lòng nhập đầy đủ số đo để gợi ý size.';
        return;
    }

    // RULESET LIN XÉN (dễ chỉnh)
    const sizes = [
        {
            size: 'S',
            height: [153,160],
            weight: [43,49],
            bust:   [82,85],
            waist:  [64,67],
            hip:    [88,91],
        },
        {
            size: 'M',
            height: [155,165],
            weight: [50,56],
            bust:   [86,89],
            waist:  [68,71],
            hip:    [92,95],
        },
        {
            size: 'L',
            height: [160,170],
            weight: [57,62],
            bust:   [90,93],
            waist:  [72,75],
            hip:    [96,99],
        },
        {
            size: 'XL',
            height: [160,170],
            weight: [63,68],
            bust:   [94,97],
            waist:  [76,79],
            hip:    [100,103],
        },
    ];

    const match = sizes.find(s =>
        h >= s.height[0] && h <= s.height[1] &&
        w >= s.weight[0] && w <= s.weight[1] &&
        bust  >= s.bust[0]  && bust  <= s.bust[1] &&
        waist >= s.waist[0] && waist <= s.waist[1] &&
        hip   >= s.hip[0]   && hip   <= s.hip[1]
    );

    resultBox.hidden = false;

    if (match) {
        resultBox.innerHTML = `
            ✨ LIN XÉN gợi ý bạn nên chọn size
            <strong style="font-size:20px"> ${match.size} </strong>
        `;
    } else {
        resultBox.innerHTML = `
            Số đo của bạn nằm giữa các size.
            <br>LIN XÉN gợi ý <strong>chọn size lớn hơn</strong> hoặc liên hệ stylist để được tư vấn.
        `;
    }
});
</script>

@endpush

