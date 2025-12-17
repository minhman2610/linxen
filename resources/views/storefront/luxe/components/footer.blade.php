<footer class="lx-footer">

    {{-- BRAND --}}
    <div class="lx-footer-brand">
        LIN XÉN
    </div>

    {{-- QUICK LINKS --}}
    <nav class="lx-footer-links">
        <a href="/linxen/about">Về chúng tôi</a>
        <a href="/linxen/contact">Liên hệ</a>
        <a href="/linxen/policy">Chính sách</a>
        <a href="/linxen/returns">Đổi trả & Hoàn tiền</a>
    </nav>

    {{-- SOCIAL --}}
    <div class="lx-footer-social">
        <a href="#" class="social-item" aria-label="Facebook">
            <img src="/themes/luxe/assets/icons/icon-facebook.svg" alt="">
        </a>
        <a href="#" class="social-item" aria-label="Instagram">
            <img src="/themes/luxe/assets/icons/icon-instagram.svg" alt="">
        </a>
        <a href="#" class="social-item" aria-label="TikTok">
            <img src="/themes/luxe/assets/icons/icon-tiktok.svg" alt="">
        </a>
    </div>

    {{-- COPYRIGHT --}}
    <div class="lx-footer-copy">
        © {{ date('Y') }} LIN XÉN · Powered by 3MG
    </div>

</footer>
<style>
    /* ---------------------------------------------------
   LUXE FOOTER — Minimal, Mobile-first (UPDATED)
----------------------------------------------------*/

.lx-footer {
    padding: 32px 16px;
    background: #fafafa;
    color: #000;
    text-align: center;

    /* chừa chỗ cho bottom nav mobile */
    margin-bottom: 80px;
}

.lx-footer-brand {
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 2px;
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
    gap: 16px;
    margin-bottom: 20px;
}

.lx-footer-social img {
    width: 20px;
    height: 20px;
    opacity: .85;
}

.lx-footer-social a:hover img {
    opacity: 1;
}

/* COPYRIGHT */
.lx-footer-copy {
    font-size: 12px;
    opacity: 0.7;
}

@media (min-width: 768px) {
    .lx-footer {
        padding: 50px 20px;
        margin-bottom: 0; /* desktop không cần chừa bottom nav */
    }

    .lx-footer-brand {
        font-size: 26px;
    }

    .lx-footer-links a {
        font-size: 14px;
    }
}

</style>