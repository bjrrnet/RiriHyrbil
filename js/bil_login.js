document.getElementById('btn_Login').addEventListener('click', function(event) {
    event.preventDefault(); 

    const loginData = {
        username: document.getElementById('txtUserName').value,
        password: document.getElementById('txtPassword').value
    };

    fetch('../public/api.php?action=login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(loginData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            localStorage.setItem("isLoggedIn", "true");
            
            alert("Login successful. Welcome back!");
            window.location.href = 'biluthyrning.html'; 
        } else {
            alert("Invalid username or password.");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Unable to connect to the server.");
    });
});