/**
 * =====================================================
 * REAL CUSTOMER – HERO SWITCH + TYPEWRITER
 * =====================================================
 */
(function () {

    /* =====================================================
     * HERO SWITCH (CLICK THUMB)
     * ===================================================== */

    const hero = document.querySelector('.lx-real-hero');
    const thumbs = document.querySelectorAll('.lx-real-thumb');

    if (!hero || thumbs.length === 0) return;

    function renderHero({ type, url, poster }) {

        // Xóa media cũ (chỉ xóa img/video, giữ caption)
        hero.querySelectorAll('img, video').forEach(el => el.remove());

        let media;

        if (type === 'video') {
            media = document.createElement('video');
            media.src = url;
            media.muted = true;
            media.loop = true;
            media.playsInline = true;
            media.autoplay = true;
            if (poster) media.poster = poster;
        } else {
            media = document.createElement('img');
            media.src = url;
            media.alt = 'LIN XÉN ngoài đời thực';
        }

        hero.prepend(media);
    }

    thumbs.forEach((btn, idx) => {

        btn.addEventListener('click', () => {

            thumbs.forEach(t => t.classList.remove('is-active'));
            btn.classList.add('is-active');

            renderHero({
                type: btn.dataset.type,
                url: btn.dataset.url,
                poster: btn.dataset.poster
            });
        });

        // set thumb đầu tiên active
        if (idx === 0) btn.classList.add('is-active');
    });

    /* =====================================================
     * TYPEWRITER – LOOP FOREVER
     * ===================================================== */

    document.querySelectorAll('.lx-real-typewriter').forEach(el => {

        const text = el.dataset.text || '';
        let index = 0;
        let isDeleting = false;

        const typingSpeed   = 70;
        const deletingSpeed = 40;
        const holdAfterType = 1600;
        const holdAfterDelete = 600;

        function loop() {

            if (!isDeleting) {
                el.textContent = text.substring(0, index + 1);
                index++;

                if (index === text.length) {
                    setTimeout(() => isDeleting = true, holdAfterType);
                }
            } else {
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
