document.getElementById('btn_Login').addEventListener('click', function(event) {
    event.preventDefault(); 

    const loginData = {
        email: document.getElementById('txtEmail').value.trim(), 
        password: document.getElementById('txtPassword').value
    };

    if (!loginData.email || !loginData.password) {
        alert("Please enter both email and password.");
        return;
    }

    fetch('../public/api2.php?action=login', { 
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(loginData)
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(result => {
        if (result.success) {
            localStorage.setItem("isLoggedIn", "true");
            localStorage.setItem("userName", result.user); 
            
            alert("Login successful. Welcome back!");
            window.location.href = 'biluthyrning.html'; 
        } else {
            alert(result.message || "Invalid email or password.");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Unable to connect to the server.");
    });
});