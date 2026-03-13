const hamburger = document.getElementById("hamburger-menu");
        const sideMenu = document.getElementById("menu-options");
        const overlay = document.getElementById("overlay");

        hamburger.addEventListener("click", () => {
            sideMenu.classList.toggle("show");
            overlay.classList.toggle("show");
        });

        overlay.addEventListener("click", () => {
            sideMenu.classList.remove("show");
            overlay.classList.remove("show");
        });