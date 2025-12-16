/* ==========================================================================
   LUXE THEME — GLOBAL JS
   Mobile-first interactions
   ========================================================================== */

/**
 * 1) AUTO UPDATE CART BADGE (from localStorage or API)
 */
function updateCartBadge() {
    try {
        let count = localStorage.getItem('cart_count');
        if (!count) count = 0;

        const badge = document.querySelector('.cart-count');
        if (badge) {
            badge.textContent = count;
        }
    } catch (e) {
        console.error('Cart badge update error:', e);
    }
}
updateCartBadge();


/**
 * 2) MOBILE BOTTOM BAR — MORE TOGGLE
 */
document.addEventListener("DOMContentLoaded", () => {
    const moreBtn = document.querySelector(".lx-bottom-nav-more-btn");
    const moreMenu = document.querySelector(".lx-bottom-more-menu");

    if (moreBtn && moreMenu) {
        moreBtn.addEventListener("click", () => {
            moreMenu.classList.toggle("active");
        });
    }
});


/**
 * 3) MOBILE MENU (Hamburger) — BASIC OPEN/CLOSE LOGIC
 */
const menuButton = document.querySelector(".lx-header-btn-menu");
const menuDrawer = document.querySelector(".lx-menu-drawer");
const menuClose = document.querySelector(".lx-menu-close");

if (menuButton && menuDrawer) {
    menuButton.addEventListener("click", () => {
        menuDrawer.classList.add("open");
    });
}

if (menuClose && menuDrawer) {
    menuClose.addEventListener("click", () => {
        menuDrawer.classList.remove("open");
    });
}


/**
 * 4) Optional: Smooth scroll for internal links
 */
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener("click", function (e) {
        const target = document.querySelector(this.getAttribute("href"));
        if (!target) return;

        e.preventDefault();
        target.scrollIntoView({
            behavior: "smooth"
        });
    });
});
