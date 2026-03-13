document.addEventListener("DOMContentLoaded", () => {

    const loginLinks = document.querySelectorAll('a[href="../html/bil_login.html"]');
    const heroLoginBtn = document.querySelector('.btn-main.btn-white');

    fetch("../public/api.php?action=checkLogin")
    .then(response => response.json())
    .then(data => {

        if (data.loggedIn) {

            loginLinks.forEach(link => {

                const span = link.querySelector("span");
                if (span) {
                    span.innerText = "My Account";
                } else {
                    link.innerText = "My Account";
                }

                link.addEventListener("click", function(e){
                    e.preventDefault();
                    window.location.href = "../html/my_bookings.html";
                });

            });

            if (heroLoginBtn) {
                heroLoginBtn.innerText = "MY ACCOUNT";

                heroLoginBtn.addEventListener("click", function(e){
                    e.preventDefault();
                    window.location.href = "../html/my_bookings.html";
                });
            }

        } else {

            loginLinks.forEach(link => {
                link.addEventListener("click", function(e){
                    e.preventDefault();
                    window.location.href = "../html/bil_login.html";
                });
            });

            if (heroLoginBtn) {
                heroLoginBtn.addEventListener("click", function(e){
                    e.preventDefault();
                    window.location.href = "../html/bil_login.html";
                });
            }

        }

    })
    .catch(error => console.error("Error checking auth:", error));

});
