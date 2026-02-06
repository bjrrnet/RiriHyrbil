<!DOCTYPE html>
<html>
<head>
    <title>Riris Hyrbilar</title>
</head>

<body>
    <h1>Tillgängliga bilar</h1>
    <button id="loadCars">Våra Bilar</button>

    <ul id="bilarLista"></ul>

    <script>
        document.getElementById('loadCars').addEventListener('click', () => {
            fetch('api.php?action=bilar')
                .then(response => response.json())
                .then(bilar => {
                    const list = document.getElementById('bilarLista');
                    list.innerHTML = '';

                    bilar.forEach(bil => {
                        const li = document.createElement('li');
                        li.textContent = `${bil.id} ${bil.name} ${bil.type}`;
                        list.appendChild(li);
                    });
                });
        });
    </script>
</body>
</html>

