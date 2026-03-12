document.addEventListener("DOMContentLoaded", () => {

    const form = document.getElementById('contact-form');
    const messageArea = document.getElementById('message-area');
    const radioButtons = document.querySelectorAll('input[name="subject"]');
    const userText = document.getElementById('user-text');

    radioButtons.forEach(radio => {
        radio.addEventListener('change', () => {
            messageArea.style.display = 'block';
        });
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const selectedRadio = document.querySelector('input[name="subject"]:checked');

        if (!selectedRadio) {
            alert("Please select a subject.");
            return;
        }

        const selectedSubject = selectedRadio.value;
        const messageText = userText.value.trim();

        if (messageText === "") {
            alert("Please write a message.");
            return;
        }

        if (!confirm("Do you want to send this request?")) {
            return;
        }

        try {
            const response = await fetch('../public/api.php?action=submitRequest', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    subject: selectedSubject,
                    message: messageText
                })
            });

            const result = await response.json();

            if (result.success) {
                alert("Request sent successfully!");
                window.location.href = "../html/bil_requests.html"; 
            } else {
                alert("Error: " + (result.message || "Something went wrong"));
            }

        } catch (error) {
            alert("Network error. Please try again.");
            console.error(error);
        }
    });
});