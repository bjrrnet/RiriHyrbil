document.getElementById('btnResetPassword').addEventListener('click', function() {
    const email = document.getElementById('txtEmail').value;

    if (!email.includes('@')) {
        alert("Enter a valid email address.");
        return;
    }

    fetch('../php/backend.php?action=forgot_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email })
    })
    .then(response => response.json())
    .then(result => {
        alert("If your email address is registered with us, you will receive a password reset link shortly.");        window.location.href = 'bil_login.html'; });
    });