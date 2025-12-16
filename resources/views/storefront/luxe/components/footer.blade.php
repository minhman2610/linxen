<footer class="lx-footer">

    {{-- BRAND TITLE --}}
    <div class="lx-footer-brand">
        LUXE
    </div>

    {{-- QUICK LINKS --}}
    <div class="lx-footer-links">
        <a href="/linxen/about">Về chúng tôi</a>
        <a href="/linxen/contact">Liên hệ</a>
        <a href="/linxen/policy">Chính sách</a>
        <a href="/linxen/returns">Đổi trả & Hoàn tiền</a>
    </div>

    {{-- SOCIAL ICONS --}}
    <div class="lx-footer-social">
        <a href="#" class="social-item">
            <img src="/themes/luxe/assets/icons/icon-facebook.svg" alt="Facebook">
        </a>
        <a href="#" class="social-item">
            <img src="/themes/luxe/assets/icons/icon-instagram.svg" alt="Instagram">
        </a>
        <a href="#" class="social-item">
            <img src="/themes/luxe/assets/icons/icon-tiktok.svg" alt="TikTok">
        </a>
    </div>

    {{-- COPYRIGHT --}}
    <div class="lx-footer-copy">
        © {{ date('Y') }} LUXE · Powered by 3MG
    </div>

</footer>


<style>
/* ---------------------------------------------------
   LUXE FOOTER — Minimal, Mobile-first
----------------------------------------------------*/

.lx-footer {
    padding: 32px 16px;
    background: #fafafa;
    color: #000;
    text-align: center;
    margin-bottom: 60px; /* chừa khoảng cho bottom nav */
}

.lx-footer-brand {
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 14px;
}

.lx-footer-links {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px 20px;
    margin-bottom: 16px;
}

.lx-footer-links a {
    font-size: 13px;
    color: #000;
    text-decoration: none;
}

.lx-footer-links a:hover {
    text-decoration: underline;
}


/* SOCIAL */
.lx-footer-social {
    display: flex;
    justify-content: center;
    gap: 14px;
    margin-bottom: 20px;
}

.lx-footer-social img {
    width: 20px;
    height: 20px;
}


/* COPYRIGHT */
.lx-footer-copy {
    font-size: 12px;
    opacity: 0.7;
}

@media (min-width: 768px) {
    .lx-footer {
        padding: 50px 20px;
    }

    .lx-footer-brand {
        font-size: 26px;
    }

    .lx-footer-links a {
        font-size: 14px;
    }
}
</style>
