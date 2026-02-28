document.getElementById('btn_Login').addEventListener('click', function() {
    const loginData = {
        username: document.getElementById('txtUserName').value,
        password: document.getElementById('txtPassword').value
    };

    fetch('../php/backend.php?action=login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(loginData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert("Login successful. Welcome back!");            window.location.href = 'biluthyrning.html'; // حولي المستخدم للصفحة الرئيسية
        } else {
            alert("Invalid username or password. Please try again.");        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Unable to connect to the server.");
    });
});