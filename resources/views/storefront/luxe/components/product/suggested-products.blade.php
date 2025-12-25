{{-- ===================================================== --}}
{{-- SUGGESTED PRODUCTS (FROM 3MG ERP) --}}
{{-- ===================================================== --}}

@php
    $suggestedProducts = is_array($suggestedProducts ?? null)
        ? $suggestedProducts
        : [];

    $count = count($suggestedProducts);
@endphp

<section class="lx-suggested-products">

    {{-- HEADER --}}
    <div class="lx-suggested-head">
        <h3>
            <span>✦</span>
            Gợi ý cho bạn
        </h3>
        <p>
            Những thiết kế LIN XÉN có thể bạn sẽ thích
        </p>
    </div>

    {{-- LIST --}}
    @if($count > 0)
        <div class="lx-suggested-scroll">

            @foreach($suggestedProducts as $item)

                @php
                    $url   = $item['url'] ?? '#';
                    $name  = $item['name'] ?? '';
                    $price = $item['price'] ?? null;

                    $thumb = $item['thumb_mobile']
                        ?? $item['thumb']
                        ?? asset('images/no-image.png');
                @endphp

                <a href="{{ $url }}" class="lx-suggested-card">

                    <div class="lx-suggested-image">
                        <img
                            src="{{ $thumb }}"
                            alt="{{ $name }}"
                            loading="lazy">
                    </div>

                    <div class="lx-suggested-info">
                        <div class="lx-suggested-name">
                            {{ $name }}
                        </div>

                        @if($price)
                            <div class="lx-suggested-price">
                                {{ number_format($price) }}₫
                            </div>
                        @endif
                    </div>

                </a>

            @endforeach

        </div>
    @else
        {{-- EMPTY STATE --}}
        <div class="lx-suggested-empty">
            Sản phẩm gợi ý đang được cập nhật
        </div>
    @endif

</section>
