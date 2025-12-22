<div class="lx-announcement-bar">
    <span class="lx-brand">LIN XÉN</span>
    <span id="lxTypeTarget" class="lx-type-text"></span>
</div>

<style>
/* ---------------------------------------------
   Announcement Bar — LIN XÉN
   Deep Brown Luxe • Q&A Typing Motion
----------------------------------------------*/
.lx-announcement-bar {
    /* 🔑 Header auto offset */
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
    overflow: hidden;
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
    min-width: 360px;
    white-space: nowrap;
    position: relative;
}

/* Caret — nhẹ, sang */
.lx-type-text::after {
    content: '';
    display: inline-block;
    width: 1px;
    height: 1em;
    background: rgba(243,238,233,.55);
    margin-left: 4px;
    animation: blink 1.4s infinite;
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
    const qaPairs = [
        {
            q: 'bán gì?',
            a: '— Váy BASIC mặc lên không cần suy nghĩ'
        },
        {
            q: 'Mặc khi nào?',
            a: '— Đi làm, đi chơi che hết khuyết điểm'
        },
        {
            q: 'Hàng Trung Quốc hay Việt Nam ?',
            a: '— Tự hào sản xuất tỉ mỉ bởi Lin Xén Việt Nam'
        },
        {
            q: 'Có đắt không?',
            a: '— Vừa đủ dễ chịu để mặc mỗi ngày'
        }
    ];

    const target = document.getElementById('lxTypeTarget');

    let pairIndex = 0;
    let charIndex = 0;
    let phase = 'question'; // question | answer | hold | clear

    function loop() {
        const pair = qaPairs[pairIndex];
        const question = pair.q;
        const fullText = pair.q + ' ' + pair.a;

        // 1️⃣ Gõ câu hỏi
        if (phase === 'question') {
            if (charIndex < question.length) {
                target.textContent += question.charAt(charIndex);
                charIndex++;
                setTimeout(loop, 40);
            } else {
                phase = 'answer';
                charIndex = question.length;
                setTimeout(loop, 500);
            }
        }

        // 2️⃣ Gõ câu trả lời
        else if (phase === 'answer') {
            if (charIndex < fullText.length) {
                target.textContent = fullText.substring(0, charIndex + 1);
                charIndex++;
                setTimeout(loop, 28);
            } else {
                phase = 'hold';
                setTimeout(loop, 2400);
            }
        }

        // 3️⃣ Giữ để đọc
        else if (phase === 'hold') {
            phase = 'clear';
            setTimeout(loop, 600);
        }

        // 4️⃣ Xóa và chuyển Q&A tiếp theo
        else if (phase === 'clear') {
            if (target.textContent.length > 0) {
                target.textContent = target.textContent.slice(0, -1);
                setTimeout(loop, 18);
            } else {
                pairIndex = (pairIndex + 1) % qaPairs.length;
                charIndex = 0;
                phase = 'question';
                setTimeout(loop, 500);
            }
        }
    }

    loop();
});
</script>
