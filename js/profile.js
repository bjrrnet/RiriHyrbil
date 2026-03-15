 window.addEventListener('DOMContentLoaded', function() {

    fetch('../public/api2.php?action=getUserDetails')
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
            const Phone = document.getElementById('user-phone-number').value.trim();
            let phonePattern = /^[0-9]+$/;
            phone = phone.replace(/[^0-9]/g, '');
                if (phone.length < 7 || phone.length > 15) {
                    alert("Please enter a valid phone number (7-15 digits).");
                    return;
                }
            
            fetch('../public/api2.php?action=updatePhone', {
                method: 'POST',
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
        fetch('../public/api2.php?action=logout')
            .then(() => {
                window.location.href = '../html/bil_login.html';
            });
    });
});
