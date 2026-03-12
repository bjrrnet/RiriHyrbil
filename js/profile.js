 window.addEventListener('DOMContentLoaded', function() {

    fetch('../public/api.php?action=getUserDetails')
        .then(response => {
            if (!response.ok) {
                throw new Error("User not logged in");
            }
            return response.json();
            })
        .then(result => {
            if (result.success) {
                const user = result.data;

                document.getElementById('user-full-name').textContent = user.first_name + " " + user.last_name;

                document.getElementById('user-email-address').textContent = user.email;

                document.getElementById('user-phone-number').textContent = user.phone_number ? user.phone_number : "Not added yet";
            } else {
                window.location.href = '../html/bil_login.html';
                }
            })
        .catch(error => {
            console.log("Error:", error);
            window.location.href = '../html/bil_login.html';
            });

    document.getElementById('logout-btn-profile').addEventListener('click', function() {
        fetch('../public/api.php?action=logout')
            .then(() => {
                window.location.href = '../html/bil_login.html';
            });
    });
});
