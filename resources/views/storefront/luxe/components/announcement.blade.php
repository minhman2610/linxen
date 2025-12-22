<div class="lx-announcement-bar">
    <span class="lx-announcement-text">
        Thanh lịch mỗi ngày cùng LIN XÉN
    </span>

    @if(!empty($link))
        <a href="{{ $link }}" class="lx-announcement-link">
            Khám phá
        </a>
    @endif
</div>

<style>
.lx-announcement-bar {
    --announcement-height: 36px;

    height: var(--announcement-height);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;

    background: #fdf8f6;
    color: #3a3a3a;

    font-size: 12px;
    letter-spacing: .3px;
}

.lx-announcement-link {
    font-weight: 500;
    color: #000;
    text-decoration: none;
    padding-bottom: 1px;
    border-bottom: 1px solid rgba(0,0,0,.3);
}
</style>
