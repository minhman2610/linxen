<div class="lx-announcement-bar">
    <span class="lx-announcement-text">
        Bộ sưu tập chọn lọc • LIN XÉN
    </span>

    @if(!empty($link))
        <a href="{{ $link }}" class="lx-announcement-link">Xem thêm</a>
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

    background: #2a2a2a; /* charcoal */
    color: #f2f2f2;

    font-size: 12px;
    letter-spacing: .4px;
}

.lx-announcement-link {
    color: #f2f2f2;
    text-decoration: none;
    border-bottom: 1px solid rgba(255,255,255,.4);
}
</style>
