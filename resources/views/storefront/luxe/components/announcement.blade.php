<div class="lx-announcement-bar">
    <span class="lx-brand">LIN XÉN</span>

    <span class="lx-editorial-sequence">
        <span>Váy nữ thanh lịch cho nhịp sống hiện đại</span>
        <span>Váy nữ basic, dễ mặc mỗi ngày</span>
        <span>Thiết kế tinh giản dành cho phụ nữ bận rộn</span>
    </span>
</div>

<style>
/* ---------------------------------------------
   Announcement Bar — Deep Brown Luxe
   Editorial Fade Sequence
----------------------------------------------*/
.lx-announcement-bar {
    --announcement-height: 38px;

    height: var(--announcement-height);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;

    background: #3b2a22; /* deep brown */
    color: #f3eee9;     /* ivory */

    font-size: 12px;
    letter-spacing: .45px;
    font-weight: 400;

    padding: 0 16px;
    box-sizing: border-box;
}

/* BRAND */
.lx-brand {
    font-weight: 600;
    letter-spacing: 1.4px;
    opacity: .9;
    white-space: nowrap;
}

/* SEQUENCE */
.lx-editorial-sequence {
    position: relative;
    width: max-content;
    min-width: 320px;
    height: 1.4em;
}

.lx-editorial-sequence span {
    position: absolute;
    left: 0;
    top: 0;

    opacity: 0;
    white-space: nowrap;

    animation: lxEditorialFade 12s infinite;
}

/* Delay từng câu */
.lx-editorial-sequence span:nth-child(1) { animation-delay: 0s; }
.lx-editorial-sequence span:nth-child(2) { animation-delay: 4s; }
.lx-editorial-sequence span:nth-child(3) { animation-delay: 8s; }

/* ANIMATION — rất nhẹ, rất sang */
@keyframes lxEditorialFade {
    0%   { opacity: 0; }
    10%  { opacity: 1; }
    30%  { opacity: 1; }
    40%  { opacity: 0; }
    100% { opacity: 0; }
}

/* Mobile */
@media (max-width: 480px) {
    .lx-announcement-bar {
        --announcement-height: 34px;
        font-size: 11px;
        gap: 8px;
    }

    .lx-editorial-sequence {
        min-width: auto;
    }
}
</style>
