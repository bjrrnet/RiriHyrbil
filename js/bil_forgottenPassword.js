document.getElementById('btnResetPassword').addEventListener('click', async function() {
    const email = document.getElementById('txtEmail').value.trim();
    
    if (!email || !email.includes('@')) {
        alert("Enter a valid email address.");
        return;
    }

    const response = await fetch('../public/api2.php?action=forgot_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email })
    });

    const result = await response.json();
    console.log("API result:", result);

    if (result.success) {
        alert("If your email is registered, you will receive a reset link.");
        window.location.href = '../html/bil_login.html';
    } else {
        alert(result.message || "Something went wrong.");
    }
});