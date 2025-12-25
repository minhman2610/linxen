{{-- ===================================================== --}}
{{-- REAL CUSTOMER – EDITORIAL HERO --}}
{{-- ===================================================== --}}

@php
    $ugcCount = is_array($ugcMedia ?? null) ? count($ugcMedia) : 0;
    $main = $ugcMedia[0] ?? null;
@endphp

<section class="lx-real-editorial">

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
            <h3>LIN XÉN</h3>
            <span>ngoài đời thực</span>
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
