{{-- ===================================================== --}}
{{-- SUGGESTED PRODUCTS (FROM 3MG ERP) --}}
{{-- ===================================================== --}}

@php
    $count = is_array($suggestedProducts ?? null) ? count($suggestedProducts) : 0;
@endphp

<section class="lx-suggested-products">

    <div class="lx-suggested-head">
        <h3>
            <span>✦</span>
            Gợi ý cho bạn
        </h3>
        <p>
            Những thiết kế LIN XÉN có thể bạn sẽ thích
        </p>
    </div>

    @if($count > 0)
        <div class="lx-suggested-scroll">

            @foreach($suggestedProducts as $item)
                <a href="{{ $item['url'] ?? '#' }}" class="lx-suggested-card">

                    <div class="lx-suggested-image">
                        <img
                            src="{{ $item['thumb_mobile'] ?? $item['thumb'] ?? '' }}"
                            alt="{{ $item['name'] ?? '' }}"
                            loading="lazy">
                    </div>

                    <div class="lx-suggested-info">
                        <div class="lx-suggested-name">
                            {{ $item['name'] ?? '' }}
                        </div>

                        @if(!empty($item['price']))
                            <div class="lx-suggested-price">
                                {{ number_format($item['price']) }}₫
                            </div>
                        @endif
                    </div>

                </a>
            @endforeach

        </div>
    @else
        <div class="lx-suggested-empty">
            Sản phẩm gợi ý đang được cập nhật
        </div>
    @endif

</section>
