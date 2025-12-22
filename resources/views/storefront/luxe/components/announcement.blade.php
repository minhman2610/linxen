<div class="lx-announcement-bar">
    {{ $message ?? 'End of season sale — up to 50% OFF!' }}

    @if(!empty($link))
        <a href="{{ $link }}" class="lx-announcement-link">
            {{ $link_text ?? 'Xem ngay' }}
        </a>
    @endif
</div>

<style>
/* ---------------------------------------------
   Announcement Bar — LUXE Theme
   👉 Defines --announcement-height for header
----------------------------------------------*/
.lx-announcement-bar {
    /* 🔑 Chiều cao chuẩn để header tự né */
    --announcement-height: 32px;

    width: 100%;
    height: var(--announcement-height);
    line-height: var(--announcement-height);

    background: #000;
    color: #fff;

    text-align: center;
    font-size: 12px;
    font-weight: 400;
    letter-spacing: 0.3px;

    padding: 0 12px;
    box-sizing: border-box;
}

.lx-announcement-bar a {
    color: #fff;
    text-decoration: underline;
    margin-left: 4px;
}

.lx-announcement-bar a:hover {
    opacity: 0.8;
}

/* Mobile optimization */
@media (max-width: 480px) {
    .lx-announcement-bar {
        --announcement-height: 28px;
        font-size: 11px;
        padding: 0 8px;
    }
}
</style>
