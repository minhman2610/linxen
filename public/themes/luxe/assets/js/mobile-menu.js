document.addEventListener('DOMContentLoaded', () => {
    const openBtn   = document.querySelector('[data-menu-open]');
    const closeBtn  = document.getElementById('lxMenuClose');
    const menu      = document.getElementById('lxMobileMenu');
    const overlay   = document.getElementById('lxMenuOverlay');
    const toggles   = document.querySelectorAll('.lx-menu-toggle');

    if (!openBtn || !menu || !overlay) {
        console.warn('❌ Mobile menu elements missing');
        return;
    }

    // OPEN MENU
    openBtn.addEventListener('click', () => {
        menu.classList.add('is-open');
        overlay.classList.add('is-active');
        document.body.classList.add('no-scroll');
    });

    // CLOSE MENU
    closeBtn?.addEventListener('click', closeMenu);
    overlay.addEventListener('click', closeMenu);

    function closeMenu() {
        menu.classList.remove('is-open');
        overlay.classList.remove('is-active');
        document.body.classList.remove('no-scroll');
    }

    // SUB MENU TOGGLE
    toggles.forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.has-sub')?.classList.toggle('open');
        });
    });

    console.log('✅ Mobile menu JS loaded');
});
