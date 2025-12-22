<div class="lx-announcement-bar">
    <span class="lx-announcement-eyebrow">LIN XÉN EDIT</span>
    <span class="lx-announcement-text">
        Thiết kế tinh giản cho nhịp sống hiện đại
    </span>

    @if(!empty($link))
        <a href="{{ $link }}" class="lx-announcement-link">
            Xem bộ sưu tập
        </a>
    @endif
</div>

<style>
.lx-announcement-bar {
    --announcement-height: 38px;

    height: var(--announcement-height);
    display: flex;
    align-items: center;
    gap: 10px;

    background: #f7f7f7;
    border-bottom: 1px solid #eaeaea;

    font-size: 12px;
    letter-spacing: .4px;
    padding: 0 16px;
}

.lx-announcement-eyebrow {
    font-size: 10px;
    letter-spacing: 1.5px;
    opacity: .6;
}

.lx-announcement-text {
    opacity: .85;
}

.lx-announcement-link {
    margin-left: auto;
    font-weight: 500;
    text-decoration: none;
    color: #000;
    border-bottom: 1px solid rgba(0,0,0,.4);
}
</style>
