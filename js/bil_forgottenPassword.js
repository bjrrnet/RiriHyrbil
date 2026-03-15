document.getElementById('btnResetPassword').addEventListener('click', function() {
    const emailInput = document.getElementById('txtEmail');
    const email = emailInput.value.trim();
    if (!email || !email.includes('@')) {
        alert("Enter a valid email address.");
        return;
    }

    fetch('../public/api2.php?action=forgot_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email })
    })
    .then(response => response.json())
    .then(result => {
        alert("If your email address is registered with us, you will receive a password reset link shortly.");        window.location.href = 'bil_login.html'; });
    });
