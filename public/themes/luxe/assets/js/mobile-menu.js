document.addEventListener("DOMContentLoaded", () => {

    const btnOpen = document.querySelector("[data-menu-open]");
    const btnClose = document.getElementById("lxMenuClose");
    const sidebar = document.getElementById("lxMobileMenu");
    const overlay = document.getElementById("lxMenuOverlay");

    function openMenu() {
        sidebar.classList.add("active");
        overlay.classList.add("active");
        document.body.classList.add("no-scroll");
    }

    function closeMenu() {
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
        document.body.classList.remove("no-scroll");
    }

    btnOpen?.addEventListener("click", openMenu);
    btnClose?.addEventListener("click", closeMenu);
    overlay?.addEventListener("click", closeMenu);

    // Dropdown submenu
    document.querySelectorAll(".has-sub").forEach(item => {
        const toggle = item.querySelector(".lx-menu-toggle");
        const submenu = item.querySelector(".lx-submenu");

        toggle.addEventListener("click", () => {
            const isOpen = item.classList.contains("open");
            document.querySelectorAll(".has-sub").forEach(i => {
                i.classList.remove("open");
                i.querySelector(".lx-submenu").classList.remove("show");
            });

            if (!isOpen) {
                item.classList.add("open");
                submenu.classList.add("show");
            }
        });
    });

});
