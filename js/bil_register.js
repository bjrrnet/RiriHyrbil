document.getElementById('btn_Register').addEventListener('click', function() {
    const userData = {
        username: document.getElementById('txtUserName').value,
        first_name: document.getElementById('txtFName').value,
        last_name: document.getElementById('txtLName').value,
        email: document.getElementById('txtEmail').value,
        password: document.getElementById('txtPassword').value
    };

    const confirmPassword = document.getElementById('txtConfirmPassword').value;
    
    if (userData.password !== confirmPassword) {
        alert("Passwords do not match. Please try again.");        
        return; 
    }

    fetch('../php/backend.php?action=register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(userData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Account created successfully. Welcome!");
            window.location.href = 'bil_login.html';
        } else {
            alert("An error occurred during registration. Perhaps the username already exists?");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Unable to connect to the server.");});
});