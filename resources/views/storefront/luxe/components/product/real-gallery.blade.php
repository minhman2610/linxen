{{-- ===================================================== --}}
{{-- REAL CUSTOMER GALLERY / VIDEO (FROM 3MG ERP) --}}
{{-- ===================================================== --}}

@php
    $ugcCount = is_array($ugcMedia ?? null) ? count($ugcMedia) : 0;
@endphp

<section class="lx-real-gallery {{ $ugcCount === 0 ? 'is-empty' : '' }}">

    <div class="lx-real-head">
        <h3>
            <span>✦</span>
            Khách hàng thật – Trải nghiệm thật
            <small class="lx-real-count">
                ({{ $ugcCount }})
            </small>
        </h3>

        <p>
            {{ $ugcCount > 0
                ? 'Hình ảnh & video thực tế từ khách hàng LIN XÉN'
                : 'Sản phẩm đang được khách hàng trải nghiệm, hình ảnh sẽ được cập nhật sớm'
            }}
        </p>
    </div>

    <div class="lx-real-scroll">

        @if($ugcCount > 0)

            @foreach($ugcMedia as $media)

                {{-- IMAGE --}}
                @if(($media['type'] ?? null) === 'image' && !empty($media['url']))
                    <div class="lx-real-item image">
                        <img
                            src="{{ $media['url'] }}"
                            alt="Khách hàng mặc sản phẩm LIN XÉN"
                            loading="lazy">
                    </div>
                @endif

                {{-- VIDEO --}}
                @if(($media['type'] ?? null) === 'video' && !empty($media['url']))
                    <div class="lx-real-item video">
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
                        <span class="lx-play-icon">▶</span>
                    </div>
                @endif

            @endforeach

        @else
            {{-- EMPTY STATE --}}
            <div class="lx-real-empty">
                <div class="lx-real-empty-box">
                    <span class="lx-real-empty-icon">📸</span>
                    <p>
                        Chưa có hình ảnh & video thực tế cho sản phẩm này
                    </p>
                    <small>
                        LIN XÉN sẽ cập nhật ngay khi có trải nghiệm từ khách hàng
                    </small>
                </div>
            </div>
        @endif

    </div>

    <div class="lx-real-note">
        ✦ Nội dung do khách hàng cung cấp – được LIN XÉN tuyển chọn
    </div>

</section>
