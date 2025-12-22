<div class="lx-announcement-bar">
    <span class="lx-announcement-text">
        LIN XÉN • Thanh lịch được định hình từ thiết kế
    </span>

    @if(!empty($link))
        <a href="{{ $link }}" class="lx-announcement-link">
            Khám phá
        </a>
    @endif
</div>

<style>
/* ---------------------------------------------
   Announcement Bar — Deep Brown Luxe
----------------------------------------------*/
.lx-announcement-bar {
    /* giữ cho header tự né */
    --announcement-height: 38px;

    height: var(--announcement-height);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;

    background: #3b2a22; /* deep brown / leather */
    color: #f3eee9;     /* ivory */

    font-size: 12px;
    letter-spacing: .45px;
    font-weight: 400;

    padding: 0 16px;
    box-sizing: border-box;
}

.lx-announcement-text {
    opacity: .9;
    white-space: nowrap;
}

.lx-announcement-link {
    color: #f3eee9;
    text-decoration: none;
    font-weight: 500;
    position: relative;
}

.lx-announcement-link::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -2px;
    width: 100%;
    height: 1px;
    background: rgba(243,238,233,.6);
}

.lx-announcement-link:hover {
    opacity: .8;
}

/* Mobile */
@media (max-width: 480px) {
    .lx-announcement-bar {
        --announcement-height: 34px;
        font-size: 11px;
        padding: 0 12px;
    }
}
</style>
