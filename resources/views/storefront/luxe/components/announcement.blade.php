<div class="lx-announcement-bar">
    <span class="lx-brand">LIN XÉN</span>

    <span class="lx-dynamic-wrap">
        <span>Váy nữ thanh lịch</span>
        <span>Váy nữ basic</span>
        <span>Váy nữ tinh giản</span>
    </span>

    <span class="lx-tail">
        cho nhịp sống hiện đại
    </span>
</div>

<style>
/* ---------------------------------------------
   Announcement Bar — Deep Brown Luxe (Dynamic)
----------------------------------------------*/
.lx-announcement-bar {
    /* 🔑 giữ cho header tự né */
    --announcement-height: 38px;

    height: var(--announcement-height);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;

    background: #3b2a22; /* deep brown / leather */
    color: #f3eee9;     /* ivory */

    font-size: 12px;
    letter-spacing: .45px;
    font-weight: 400;

    padding: 0 16px;
    box-sizing: border-box;
    overflow: hidden;
}

/* BRAND */
.lx-brand {
    font-weight: 600;
    letter-spacing: 1.2px;
}

/* DYNAMIC WORDS */
.lx-dynamic-wrap {
    position: relative;
    height: 1.2em;
    overflow: hidden;
}

.lx-dynamic-wrap span {
    display: block;
    animation: lxSwap 6s infinite;
}

.lx-dynamic-wrap span:nth-child(2) {
    animation-delay: 2s;
}

.lx-dynamic-wrap span:nth-child(3) {
    animation-delay: 4s;
}

/* TAIL TEXT */
.lx-tail {
    opacity: .85;
    white-space: nowrap;
}

/* ANIMATION */
@keyframes lxSwap {
    0%   { transform: translateY(0); opacity: 1; }
    28%  { opacity: 1; }
    33%  { opacity: 0; }
    36%  { transform: translateY(-100%); opacity: 0; }
    39%  { opacity: 1; }
    100% { opacity: 1; }
}

/* Mobile */
@media (max-width: 480px) {
    .lx-announcement-bar {
        --announcement-height: 34px;
        font-size: 11px;
        padding: 0 12px;
        gap: 6px;
    }

    .lx-tail {
        display: none; /* mobile chỉ giữ thông tin cốt lõi */
    }
}
</style>
