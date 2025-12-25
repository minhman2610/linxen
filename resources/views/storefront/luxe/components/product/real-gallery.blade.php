{{-- ===================================================== --}}
{{-- REAL CUSTOMER – EDITORIAL HERO (ENHANCED) --}}
{{-- ===================================================== --}}

@php
    $ugcCount = is_array($ugcMedia ?? null) ? count($ugcMedia) : 0;
    $main = $ugcMedia[0] ?? null;
@endphp

<section class="lx-real-editorial">

    {{-- HEADER / TITLE --}}
    <div class="lx-real-editorial-head">

        <div class="lx-real-typewriter"
             data-text="LIN XÉN — CHÚNG TÔI ĐAM MÊ VÁY">
            LIN XÉN — CHÚNG TÔI ĐAM MÊ VÁY
        </div>

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

    {{-- HERO --}}
    <div class="lx-real-hero">

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

        <div class="lx-real-hero-overlay">
            <span>Ảnh khách hàng thật</span>
        </div>
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
