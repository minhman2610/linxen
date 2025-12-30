{{-- ===================================================== --}}
{{-- REAL CUSTOMER – EDITORIAL HERO (ENHANCED) --}}
{{-- ===================================================== --}}

@php
    $ugcMedia = is_array($ugcMedia ?? null) ? $ugcMedia : [];
    $ugcCount = count($ugcMedia);

    $main = $ugcMedia[0] ?? null;

    $hasValidMain =
        $main &&
        !empty($main['url']) &&
        in_array($main['type'] ?? null, ['image', 'video'], true);
@endphp

@if($hasValidMain)

<section class="lx-real-editorial">

    {{-- HERO --}}
    <div class="lx-real-hero">

        {{-- MEDIA --}}
        @if($main['type'] === 'image')
            <img
                src="{{ $main['url'] }}"
                alt="LIN XÉN ngoài đời thực"
                loading="lazy"
            >
        @elseif($main['type'] === 'video')
            <video
                src="{{ $main['url'] }}"
                muted
                loop
                playsinline
                preload="metadata"
                @if(!empty($main['poster']))
                    poster="{{ $main['poster'] }}"
                @endif
            ></video>
        @endif

        {{-- HERO CAPTION --}}
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

    {{-- INFO BLOCK --}}
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
                @continue($index === 0)

                @php
                    $isValidThumb =
                        !empty($media['url']) &&
                        in_array($media['type'] ?? null, ['image', 'video'], true);
                @endphp

                @if($isValidThumb)
                    <button
                        class="lx-real-thumb"
                        data-type="{{ $media['type'] }}"
                        data-url="{{ $media['url'] }}"
                        @if(!empty($media['poster']))
                            data-poster="{{ $media['poster'] }}"
                        @endif
                    >
                        <img
                            src="{{ $media['poster'] ?? $media['url'] }}"
                            alt=""
                            loading="lazy"
                        >
                    </button>
                @endif
            @endforeach

        </div>
    @endif

</section>

@endif
