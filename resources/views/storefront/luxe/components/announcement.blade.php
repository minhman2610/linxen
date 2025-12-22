<div class="lx-announcement-bar">
    <span class="lx-announcement-text">
        Thanh lịch, dễ mặc, không lỗi mốt
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
    gap: 12px;

    background: #b8a89a; /* taupe */
    color: #1f1f1f;

    font-size: 12px;
    letter-spacing: .35px;
}

.lx-announcement-link {
    color: #1f1f1f;
    text-decoration: none;
    border-bottom: 1px solid rgba(0,0,0,.4);
}
</style>
