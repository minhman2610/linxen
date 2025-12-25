{{-- ===================================================== --}}
{{-- REAL CUSTOMER STORY STRIP --}}
{{-- ===================================================== --}}

@php
    $ugcCount = is_array($ugcMedia ?? null) ? count($ugcMedia) : 0;
@endphp

<section class="lx-real-story">

    {{-- HEADER --}}
    <div class="lx-real-story-head">
        <h3>LIN XÉN trong đời sống thật</h3>
        <p>
            {{ $ugcCount > 0
                ? 'Hình ảnh & video do khách hàng ghi lại khi sử dụng sản phẩm'
                : 'Khách hàng đang trải nghiệm sản phẩm, nội dung sẽ sớm được cập nhật'
            }}
        </p>
    </div>

    {{-- STORY LIST --}}
    <div class="lx-real-story-list">

        @if($ugcCount > 0)

            @foreach($ugcMedia as $index => $media)

                <article class="lx-real-story-item">

                    {{-- MEDIA --}}
                    <div class="lx-real-story-media">

                        @if($media['type'] === 'image')
                            <img
                                src="{{ $media['url'] }}"
                                alt="Khách hàng mặc LIN XÉN"
                                loading="lazy">
                        @endif

                        @if($media['type'] === 'video')
                            <video
                                src="{{ $media['url'] }}"
                                muted
                                loop
                                playsinline
                                preload="metadata"
                                @if(!empty($media['poster']))
                                    poster="{{ $media['poster'] }}"
                                @endif
                            ></video>

                            <span class="lx-story-play">▶</span>
                        @endif

                    </div>

                    {{-- CAPTION --}}
                    <div class="lx-real-story-caption">
                        <span class="lx-story-index">
                            {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                        </span>

                        <p>
                            Khách hàng LIN XÉN chia sẻ trải nghiệm khi mặc thiết kế này
                        </p>
                    </div>

                </article>

            @endforeach

        @else
            {{-- EMPTY --}}
            <div class="lx-real-story-empty">
                <p>Chưa có hình ảnh & video thực tế cho sản phẩm này</p>
                <small>LIN XÉN sẽ cập nhật ngay khi có trải nghiệm từ khách hàng</small>
            </div>
        @endif

    </div>

</section>
