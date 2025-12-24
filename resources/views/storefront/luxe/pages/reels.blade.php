@extends('storefront.luxe.layouts.reels')

@section('content')
<div class="lx-reels-wrapper">

    {{-- SWIPER DỌC --}}
    <div class="swiper reels-vertical">
        <div class="swiper-wrapper">

            @foreach ($products as $p)
                <div class="swiper-slide reels-slide"
                     data-id="{{ $p['id'] }}"
                     data-name="{{ $p['name'] }}"
                     data-price="{{ $p['price'] }}"
                     data-image="{{ $p['images'][0] ?? '' }}">

                    {{-- SWIPER NGANG --}}
                    <div class="swiper reels-images">
                        <div class="swiper-wrapper">
                            @foreach ($p['images'] as $img)
                                <div class="swiper-slide reels-image-item">
                                    <img src="{{ $img }}" alt="{{ $p['name'] }}" loading="lazy">
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            @endforeach

        </div>
    </div>

    {{-- LOADING OVERLAY --}}
    <div class="lx-reels-loading" id="lxReelsLoading">
        <div class="lx-loading-spinner"></div>
        <div class="lx-loading-text">Đang làm mới…</div>
    </div>

</div>
@endsection
