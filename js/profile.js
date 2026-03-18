window.addEventListener('DOMContentLoaded', function() {

    fetch('../public/api2.php?action=getUserDetails', {
        credentials: 'include'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error("User not logged in");
        }
        return response.json();
    })
    .then(result => {
        if (result.success) {
            const user = result.data;
            document.getElementById('user-full-name').textContent = user.full_name;
            document.getElementById('user-email-address').textContent = user.email;
            document.getElementById('license-status').textContent = (user.national_id && user.national_id !== "") 
                ? user.national_id : "Not added yet";
            const phoneInput = document.getElementById('user-phone-number');
            phoneInput.value = user.phone ? user.phone : "";
            document.getElementById('user-phone-number').textContent = user.phone ? user.phone : "Not added yet";
        } else {
            window.location.href = '../html/bil_login.html';
        }
    })
    .catch(error => {
        console.log("Error:", error);
        window.location.href = '../html/bil_login.html';
    });

    document.getElementById('save-phone').addEventListener('click', function () {
        let phone = document.getElementById('user-phone-number').value.trim();
        phone = phone.replace(/[^0-9]/g, '');
        if (phone.length < 7 || phone.length > 15) {
            alert("Please enter a valid phone number (7-15 digits).");
            return;
        }

        fetch('../public/api2.php?action=updatePhone', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ phone: phone })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert("Phone number updated successfully!");
            } else {
                alert("Failed to update phone number.");
            }
        });
    });

    document.getElementById('logout-btn-profile').addEventListener('click', function() {
        fetch('../public/api2.php?action=logout', {
            credentials: 'include'
        })
        .then(() => {
            window.location.href = '../html/bil_login.html';
        });
    });

    document.getElementById('delete-account-btn').addEventListener('click', function() {
        if (confirm("Are you sure you want to delete your account? This action cannot be undone.")) {
            fetch('../public/api2.php?action=deleteAccount', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert("Your account has been deleted successfully.");
                    window.location.href = '../html/bil_login.html';
                } else {
                    alert("Error: " + (result.message || "Failed to delete account."));
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred. Please try again.");
            });
        }
    });

});