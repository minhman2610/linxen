<section class="lx-slideshow halo-slideshow-wrapper">
    <div class="halo-slideshow slideshow"
         data-autoplay="true"
         data-speed="4500"
         data-fade="true"
         data-arrows="true"
         data-dots="true">

        {{-- SLIDE 1 — ẢNH --}}
        <div class="slideshow__slide">
            <div class="slideshow__image">
                <img src="{{ asset('themes/ella/assets/slide1.jpg') }}"
                     alt="LINXEN High Fashion 01"
                     class="slideshow__media">
            </div>

            <div class="slideshow__content slideshow__content--center">
                <div class="slideshow__text-wrap">
                    <h2 class="slideshow__title">LINXEN COLLECTION</h2>
                    <p class="slideshow__subtitle">High Fashion – Minimal Style</p>
                    <a class="slideshow__btn" href="/c/new">Khám phá ngay</a>
                </div>
            </div>
        </div>

        {{-- SLIDE 2 — VIDEO --}}
        <div class="slideshow__slide">
            <div class="slideshow__video-container">
                <video class="slideshow__media slideshow__video"
                       autoplay
                       muted
                       loop
                       playsinline>
                    <source src="{{ asset('themes/ella/assets/linxen-hero-video.mp4') }}" type="video/mp4">
                </video>
            </div>

            <div class="slideshow__content slideshow__content--left">
                <div class="slideshow__text-wrap">
                    <h2 class="slideshow__title">Video Campaign 2024</h2>
                    <p class="slideshow__subtitle">Tối giản – Sang trọng – Tự tin</p>
                    <a class="slideshow__btn" href="/c/dress">Xem bộ sưu tập</a>
                </div>
            </div>
        </div>

        {{-- SLIDE 3 — ẢNH --}}
        <div class="slideshow__slide">
            <div class="slideshow__image">
                <img src="{{ asset('themes/ella/assets/slide2.jpg') }}"
                     alt="LINXEN High Fashion 02"
                     class="slideshow__media">
            </div>

            <div class="slideshow__content slideshow__content--right">
                <div class="slideshow__text-wrap">
                    <h2 class="slideshow__title">Everyday Elegance</h2>
                    <p class="slideshow__subtitle">Dành cho phụ nữ hiện đại</p>
                    <a class="slideshow__btn" href="/lookbook">Xem Lookbook</a>
                </div>
            </div>
        </div>

    </div>
</section>
@push('styles')
<style>
    .lx-slideshow {
        position: relative;
        overflow: hidden;
        width: 100%;
    }

    .slideshow__slide {
        position: relative;
        width: 100%;
        height: 70vh; 
        max-height: 760px;
    }

    .slideshow__media {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    /* TEXT OVERLAY */
    .slideshow__content {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        padding: 20px;
        z-index: 5;
    }

    .slideshow__content--center {
        justify-content: center;
        text-align: center;
    }

    .slideshow__content--left {
        justify-content: flex-start;
        text-align: left;
    }

    .slideshow__content--right {
        justify-content: flex-end;
        text-align: right;
        padding-right: 10%;
    }

    .slideshow__text-wrap {
        max-width: 340px;
        color: #fff;
    }

    .slideshow__title {
        font-size: 26px;
        font-weight: 600;
        letter-spacing: .05em;
        margin-bottom: 8px;
    }

    .slideshow__subtitle {
        font-size: 14px;
        margin-bottom: 14px;
        opacity: .9;
    }

    .slideshow__btn {
        display: inline-block;
        padding: 10px 20px;
        background: #111;
        color: #fff;
        border-radius: 999px;
        font-size: 12px;
        letter-spacing: .12em;
        text-decoration: none;
    }

    @media (min-width: 768px) {
        .slideshow__slide {
            height: 80vh;
        }
        .slideshow__title { font-size: 40px; }
        .slideshow__subtitle { font-size: 16px; }
    }
</style>
@endpush
