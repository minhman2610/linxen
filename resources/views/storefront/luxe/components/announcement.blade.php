<div class="lx-announcement-bar">
    <span class="lx-announcement-text">
        Thiết kế tinh giản cho nhịp sống hiện đại
    </span>

    @if(!empty($link))
        <a href="{{ $link }}" class="lx-announcement-link">Khám phá</a>
    @endif
</div>

<style>
.lx-announcement-bar {
    --announcement-height: 38px;

    height: var(--announcement-height);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;

    background: #f4efe9; /* warm beige */
    color: #2b2b2b;

    font-size: 12px;
    letter-spacing: .35px;
}

.lx-announcement-text {
    opacity: .85;
}

.lx-announcement-link {
    font-weight: 500;
    color: #2b2b2b;
    text-decoration: none;
    border-bottom: 1px solid rgba(0,0,0,.35);
}
</style>
