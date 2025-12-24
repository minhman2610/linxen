@extends('storefront.luxe.layouts.reels')

@section('content')
<div class="lx-reels-wrapper lx-reels-has-header">

    {{-- MINI LOADING --}}
    <div class="lx-reels-mini-loading" id="lxReelsMiniLoading">
        <span class="lx-reels-loading-icon">⟳</span>
        <span class="lx-reels-loading-text">Đang làm mới…</span>
    </div>

    {{-- SWIPER DỌC --}}
    <div class="swiper reels-vertical">
        <div class="swiper-wrapper">

            @foreach ($products as $p)
                <div class="swiper-slide reels-slide"
                     data-id="{{ $p['id'] }}"
                     data-sku="{{ $p['sku'] }}"
                     data-name="{{ $p['name'] }}"
                     data-price="{{ $p['price'] }}"
                     data-available="{{ $p['available'] }}"
                     data-tag="{{ $p['tag'] }}"
                     data-thumb="{{ $p['thumb'] }}">

                    {{-- SWIPER NGANG – IMAGES --}}
                    <div class="swiper reels-images">
                        <div class="swiper-wrapper">
                            @foreach ($p['images'] as $img)
                                <div class="swiper-slide">
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
