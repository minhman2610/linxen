<div class="lx-announcement-bar">
    <span class="lx-brand">LIN XÉN</span>
    <span id="lxTypeTarget" class="lx-type-text"></span>
</div>
<style>
/* ---------------------------------------------
   Announcement Bar — Deep Brown Luxe
   Editorial Typing Motion
----------------------------------------------*/
.lx-announcement-bar {
    --announcement-height: 38px;

    height: var(--announcement-height);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;

    background: #3b2a22;
    color: #f3eee9;

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

/* TYPE TEXT */
.lx-type-text {
    min-width: 320px;
    white-space: nowrap;
    position: relative;
}

/* caret rất nhẹ */
.lx-type-text::after {
    content: '';
    display: inline-block;
    width: 1px;
    height: 1em;
    background: rgba(243,238,233,.6);
    margin-left: 4px;
    animation: blink 1.2s infinite;
}

@keyframes blink {
    0%,50%,100% { opacity: 0; }
    25%,75% { opacity: 1; }
}

/* Mobile */
@media (max-width: 480px) {
    .lx-announcement-bar {
        --announcement-height: 34px;
        font-size: 11px;
        gap: 8px;
    }

    .lx-type-text {
        min-width: auto;
    }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const texts = [
        'Váy nữ thanh lịch cho nhịp sống hiện đại',
        'Váy nữ basic, dễ mặc mỗi ngày',
        'Thiết kế tinh giản dành cho phụ nữ bận rộn'
    ];

    const target = document.getElementById('lxTypeTarget');
    let textIndex = 0;
    let charIndex = 0;
    let typing = true;

    function typeLoop() {
        const current = texts[textIndex];

        if (typing) {
            if (charIndex < current.length) {
                target.textContent += current.charAt(charIndex);
                charIndex++;
                setTimeout(typeLoop, 40); // tốc độ đánh máy
            } else {
                // giữ lại để đọc
                setTimeout(() => typing = false, 2200);
                setTimeout(typeLoop, 2200);
            }
        } else {
            if (charIndex > 0) {
                target.textContent = current.substring(0, charIndex - 1);
                charIndex--;
                setTimeout(typeLoop, 20); // tốc độ xóa
            } else {
                typing = true;
                textIndex = (textIndex + 1) % texts.length;
                setTimeout(typeLoop, 400);
            }
        }
    }

    typeLoop();
});
</script>

