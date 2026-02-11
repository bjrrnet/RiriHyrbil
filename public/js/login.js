fetch('api.php?action=checkLogin')
    .then(res => res.json())
    .then(data => {
        if (data.inloggad) {
            document.getElementById('inloggningsFormulaer').style.display = 'none';
            document.getElementById('loadCars').style.display = 'block';
        }
    });


document.getElementById('loginButton').addEventListener('click', () => {
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    fetch('api.php?action=login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password })
    })
    .then(res => res.json())    //
    .then(data => {
        const status = document.getElementById('loginStatus');
        if (data.success) {
            status.textContent = "Login successful";
        } else {
            status.textContent = "Wrong username or password";
        }
    });
});
