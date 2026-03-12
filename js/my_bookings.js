document.addEventListener("DOMContentLoaded", () => {

    const wrapper = document.getElementById("bookings-list-wrapper");

    fetch("../public/api.php?action=myBookings")
    .then(response => response.json())
    .then(bookings => {

        if (!bookings || bookings.length === 0) {
            wrapper.innerHTML = "<p>No bookings found.</p>";
            return;
        }

        bookings.forEach(booking => {
            const card = document.createElement("div");
            card.classList.add("booking-card");
            card.innerHTML = `
                <h3>${booking.car_name}</h3>
                <p>Location: ${booking.location}</p>
                <p>From: ${booking.start_date}</p>
                <p>To: ${booking.end_date}</p>
            `;
            wrapper.appendChild(card);
        });

    })
    .catch(error => {
        console.error("Error fetching bookings:", error);
        wrapper.innerHTML = "<p>Error loading bookings.</p>";
    });

});