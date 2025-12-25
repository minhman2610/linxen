<div class="lx-product-gallery">

    <div class="swiper lx-product-main-swiper">
        <div class="swiper-wrapper">
            @foreach($images as $index => $img)
                <div class="swiper-slide">
                    <img
                        src="{{ $img['mobile'] ?? $img['thumb'] }}"
                        data-full="{{ $img['full'] ?? $img['thumb'] }}"
                        alt="{{ $product['name'] ?? '' }}"
                        loading="{{ $index === 0 ? 'eager' : 'lazy' }}">
                </div>
            @endforeach
        </div>

        <div class="swiper-pagination"></div>
    </div>

    @if(count($images) > 1)
        <div class="swiper lx-product-thumb-swiper">
            <div class="swiper-wrapper">
                @foreach($images as $img)
                    <div class="swiper-slide">
                        <img src="{{ $img['thumb'] }}" loading="lazy" alt="">
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
