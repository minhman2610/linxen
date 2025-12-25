/**
 * =====================================================
 * REAL CUSTOMER GALLERY – VIDEO CONTROL
 * Source: 3MG ERP (UGC / Research Media)
 * =====================================================
 */

(function () {

    /**
     * Pause all videos except the current one
     */
    function pauseOtherVideos(currentVideo) {
        document
            .querySelectorAll('.lx-real-item.video video')
            .forEach(video => {
                if (video !== currentVideo) {
                    video.pause();
                }
            });
    }

    /**
     * Toggle play / pause on click
     */
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

    /**
     * Auto pause video when out of viewport (mobile friendly)
     */
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

})();
document.querySelectorAll('.lx-real-typewriter').forEach(el => {
    const text = el.dataset.text;
    let i = 0;
    el.textContent = '';

    const timer = setInterval(() => {
        el.textContent += text[i];
        i++;
        if (i >= text.length) clearInterval(timer);
    }, 60);
});
