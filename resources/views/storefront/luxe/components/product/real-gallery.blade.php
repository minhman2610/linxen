{{-- ===================================================== --}}
{{-- REAL CUSTOMER – EDITORIAL HERO (ENHANCED) --}}
{{-- ===================================================== --}}

@php
    $ugcCount = is_array($ugcMedia ?? null) ? count($ugcMedia) : 0;
    $main = $ugcMedia[0] ?? null;
@endphp

<section class="lx-real-editorial">

    {{-- HERO --}}
    <div class="lx-real-hero">

        {{-- MEDIA --}}
        @if($main && $main['type'] === 'image')
            <img src="{{ $main['url'] }}" alt="LIN XÉN ngoài đời thực">
        @endif

        @if($main && $main['type'] === 'video')
            <video
                src="{{ $main['url'] }}"
                muted
                loop
                playsinline
                preload="metadata"
                @if(!empty($main['poster'])) poster="{{ $main['poster'] }}" @endif
            ></video>
        @endif

        {{-- HERO CAPTION (SLOGAN) --}}
        <div class="lx-real-hero-caption">

            <div class="lx-real-typewriter"
                 data-text="LIN XÉN — CHÚNG TÔI ĐAM MÊ VÁY">
                LIN XÉN — CHÚNG TÔI ĐAM MÊ VÁY
            </div>

            <span class="lx-real-hero-tag">
                Ảnh khách hàng thật
            </span>

        </div>
    </div>

    {{-- INFO BLOCK (DƯỚI ẢNH) --}}
    <div class="lx-real-info">

        <h3 class="lx-real-product-title">
            @if(!empty($product['code']))
                <span class="lx-real-code">MÃ {{ $product['code'] }}</span>
            @endif
            {{ $product['name'] ?? '' }}
        </h3>

        <p class="lx-real-desc">
            Hình ảnh & video thực tế từ khách hàng
            <strong>({{ $ugcCount }})</strong>
        </p>

    </div>

    {{-- THUMB STRIP --}}
    @if($ugcCount > 1)
        <div class="lx-real-strip">

            @foreach($ugcMedia as $index => $media)
                @if($index > 0)
                    <button
                        class="lx-real-thumb"
                        data-type="{{ $media['type'] }}"
                        data-url="{{ $media['url'] }}"
                        @if(!empty($media['poster']))
                            data-poster="{{ $media['poster'] }}"
                        @endif
                    >
                        <img src="{{ $media['poster'] ?? $media['url'] }}" alt="">
                    </button>
                @endif
            @endforeach

        </div>
    @endif

</section>

