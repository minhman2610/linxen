/**
 * =====================================================
 * REAL CUSTOMER – VIDEO CONTROL + TYPEWRITER LOOP
 * Source: 3MG ERP (UGC)
 * =====================================================
 */
(function () {

    /* =====================================================
     * VIDEO CONTROL (GIỮ NGUYÊN LOGIC CŨ)
     * ===================================================== */

    function pauseOtherVideos(currentVideo) {
        document
            .querySelectorAll('.lx-real-item.video video')
            .forEach(video => {
                if (video !== currentVideo) {
                    video.pause();
                }
            });
    }

    document.addEventListener('click', function (e) {

        const item = e.target.closest('.lx-real-item.video');
        if (!item) return;

        const video = item.querySelector('video');
        const icon  = item.querySelector('.lx-play-icon');

        if (!video) return;

        pauseOtherVideos(video);

        if (video.paused) {
            video.play().catch(() => {});
            if (icon) icon.style.opacity = 0;
        } else {
            video.pause();
            if (icon) icon.style.opacity = 1;
        }
    });

    if ('IntersectionObserver' in window) {

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const video = entry.target;
                const icon  = video.closest('.lx-real-item')?.querySelector('.lx-play-icon');

                if (!entry.isIntersecting) {
                    video.pause();
                    if (icon) icon.style.opacity = 1;
                }
            });
        }, {
            threshold: 0.4
        });

        document
            .querySelectorAll('.lx-real-item.video video')
            .forEach(video => observer.observe(video));
    }

    /* =====================================================
     * TYPEWRITER – LOOP FOREVER (GÕ → XOÁ → LẶP)
     * ===================================================== */

    document.querySelectorAll('.lx-real-typewriter').forEach(el => {

        const text = el.dataset.text || '';
        let index = 0;
        let isDeleting = false;

        const typingSpeed  = 70;   // tốc độ gõ
        const deletingSpeed = 40;  // tốc độ xoá
        const holdAfterType = 1600; // dừng sau khi gõ xong
        const holdAfterDelete = 600; // dừng sau khi xoá xong

        function loop() {

            if (!isDeleting) {
                // GÕ CHỮ
                el.textContent = text.substring(0, index + 1);
                index++;

                if (index === text.length) {
                    setTimeout(() => isDeleting = true, holdAfterType);
                }
            } else {
                // XOÁ CHỮ
                el.textContent = text.substring(0, index - 1);
                index--;

                if (index === 0) {
                    isDeleting = false;
                    setTimeout(() => {}, holdAfterDelete);
                }
            }

            setTimeout(
                loop,
                isDeleting ? deletingSpeed : typingSpeed
            );
        }

        loop();
    });

})();
