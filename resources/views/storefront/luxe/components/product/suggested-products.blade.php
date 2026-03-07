{{-- =====================================================
   AI SUGGESTED PRODUCTS – CONVERSION BLOCK
===================================================== --}}

@php
$suggestedProducts = is_array($suggestedProducts ?? null)
? $suggestedProducts
: [];

$count = count($suggestedProducts);
@endphp


<section class="lx-ai-suggested">

    {{-- HEADER --}}
    <div class="lx-ai-head">

        <h3>
            Có thể bạn sẽ thích chiếc váy này hơn
        </h3>

        <p>
            Những thiết kế được nhiều khách LIN XÉN chọn khi xem mẫu này
        </p>

    </div>


    {{-- LIST --}}
    @if($count > 0)

    <div class="lx-ai-scroll">

        @foreach($suggestedProducts as $item)

        @php
        $url   = $item['url'] ?? '#';
        $name  = $item['name'] ?? '';
        $price = $item['price'] ?? null;

        $thumb = $item['thumb_mobile']
        ?? $item['thumb']
        ?? asset('images/no-image.png');
        @endphp


        <a href="{{ $url }}" class="lx-ai-card">

            <div class="lx-ai-image">

                <img
                src="{{ $thumb }}"
                alt="{{ $name }}"
                loading="lazy">

            </div>


            <div class="lx-ai-info">

                <div class="lx-ai-name">
                    {{ $name }}
                </div>

                @if($price)
                <div class="lx-ai-price">
                    {{ number_format($price) }}₫
                </div>
                @endif

            </div>

        </a>

        @endforeach

    </div>

    @else

    <div class="lx-ai-empty">
        LIN XÉN đang chọn thêm thiết kế phù hợp cho bạn
    </div>

    @endif

</section>