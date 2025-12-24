@extends("storefront.luxe.layouts.app")

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
<link rel="stylesheet" href="/themes/luxe/assets/css/reels.css">
@endpush

@section('content')
<div class="lx-reels">

    <!-- VERTICAL PRODUCTS -->
    <div class="swiper reels-vertical">
        <div class="swiper-wrapper">

            @foreach ($products as $p)
                <div class="swiper-slide reels-product">

                    <!-- HORIZONTAL IMAGES -->
                    <div class="swiper reels-images">
                        <div class="swiper-wrapper">
                            @foreach ($p['images'] as $img)
                                <div class="swiper-slide">
                                    <img src="{{ $img }}" alt="{{ $p['name'] }}">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- OVERLAY INFO -->
                    <div class="reels-overlay">
                        <h3>{{ $p['name'] }}</h3>
                        <div class="reels-price">
                            {{ number_format($p['price']) }}₫
                        </div>

                        <button class="lx-btn-add-cart"
                                data-id="{{ $p['id'] }}"
                                data-name="{{ $p['name'] }}"
                                data-price="{{ $p['price'] }}"
                                data-image="{{ $p['images'][0] }}">
                            Thêm vào giỏ
                        </button>
                    </div>

                </div>
            @endforeach

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="/themes/luxe/assets/js/reels.js"></script>
@endpush
