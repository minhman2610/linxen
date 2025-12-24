@extends('storefront.luxe.layouts.reels')

@section('content')
<div class="lx-reels-wrapper">

    {{-- SWIPER DỌC – SẢN PHẨM --}}
    <div class="swiper reels-vertical">
        <div class="swiper-wrapper">

            @foreach ($products as $index => $p)
                <div class="swiper-slide reels-slide"
                     data-id="{{ $p['id'] }}"
                     data-name="{{ $p['name'] }}"
                     data-price="{{ $p['price'] }}"
                     data-image="{{ $p['images'][0] ?? '' }}">

                    {{-- SWIPER NGANG – ẢNH SẢN PHẨM --}}
                    <div class="swiper reels-images">
                        <div class="swiper-wrapper">
                            @foreach ($p['images'] as $img)
                                <div class="swiper-slide reels-image-item">
                                    <img src="{{ $img }}"
                                         alt="{{ $p['name'] }}"
                                         loading="lazy">
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            @endforeach

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
/**
 * =====================================================
 * INIT PRODUCT BAR DEFAULT (FIRST SLIDE)
 * =====================================================
 */
document.addEventListener('DOMContentLoaded', function () {

    const firstSlide = document.querySelector('.reels-slide');
    if (!firstSlide) return;

    const nameEl  = document.getElementById('lxReelsName');
    const priceEl = document.getElementById('lxReelsPrice');
    const btn     = document.getElementById('lxReelsAddCart');

    if (!nameEl || !priceEl || !btn) return;

    nameEl.innerText  = firstSlide.dataset.name;
    priceEl.innerText = Number(firstSlide.dataset.price)
        .toLocaleString('vi-VN') + '₫';

    btn.dataset.id    = firstSlide.dataset.id;
    btn.dataset.name  = firstSlide.dataset.name;
    btn.dataset.price = firstSlide.dataset.price;
    btn.dataset.image = firstSlide.dataset.image;
});
</script>
@endpush
