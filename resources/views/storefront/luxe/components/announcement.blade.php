<div class="lx-announcement-bar">
    <span class="lx-announcement-text">
        {{ $message ?? 'Bộ sưu tập mới • Thiết kế tinh giản cho nhịp sống hiện đại' }}
    </span>

    @if(!empty($link))
        <a href="{{ $link }}" class="lx-announcement-link">
            {{ $link_text ?? 'Khám phá' }}
        </a>
    @endif
</div>

<style>
/* ---------------------------------------------
   Announcement Bar — LUXE / LIN XÉN
   Premium • Minimal • Elegant
----------------------------------------------*/
.lx-announcement-bar {
    /* 🔑 chiều cao để header tự né */
    --announcement-height: 36px;

    width: 100%;
    height: var(--announcement-height);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    background: linear-gradient(
        180deg,
        #fafafa 0%,
        #f3f3f3 100%
    );

    color: #111;
    font-size: 12px;
    font-weight: 400;
    letter-spacing: 0.4px;

    border-bottom: 1px solid #eaeaea;
    padding: 0 14px;
    box-sizing: border-box;
}

.lx-announcement-text {
    opacity: 0.85;
    white-space: nowrap;
}

.lx-announcement-link {
    font-weight: 500;
    color: #000;
    text-decoration: none;
    position: relative;
}

.lx-announcement-link::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -2px;
    width: 100%;
    height: 1px;
    background: #000;
    opacity: 0.6;
}

.lx-announcement-link:hover {
    opacity: 0.75;
}

/* Mobile */
@media (max-width: 480px) {
    .lx-announcement-bar {
        --announcement-height: 32px;
        font-size: 11px;
        padding: 0 10px;
    }
}
</style>
