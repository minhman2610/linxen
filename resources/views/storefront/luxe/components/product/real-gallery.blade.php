{{-- ===================================================== --}}
{{-- REAL CUSTOMER GALLERY / VIDEO (FROM 3MG ERP) --}}
{{-- ===================================================== --}}

@if(!empty($ugcMedia) && count($ugcMedia) > 0)

<section class="lx-real-gallery">

    <div class="lx-real-head">
        <h3>
            <span>✦</span>
            Khách hàng thật – Trải nghiệm thật
        </h3>
        <p>
            Hình ảnh & video thực tế từ khách hàng LIN XÉN
        </p>
    </div>

    <div class="lx-real-scroll">

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

    </div>

    <div class="lx-real-note">
        ✦ Nội dung do khách hàng cung cấp – được LIN XÉN tuyển chọn
    </div>

</section>

@endif
