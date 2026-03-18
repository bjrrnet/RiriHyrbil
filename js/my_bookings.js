document.addEventListener("DOMContentLoaded", () => {
    const wrapper = document.getElementById("bookings-list-wrapper");
    const template = document.getElementById("booking-card-template");
    let bookingIdToDelete = null;

    const formatDate = (dateString) => {
        if (!dateString) return "N/A";
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString; 
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    };

    const loadBookings = () => {
        wrapper.innerHTML = "<p class='loading-text'>Loading your bookings...</p>";
        
        fetch("../public/api2.php?action=myBookings")
            .then(res => res.json())
            .then(bookings => {
                wrapper.innerHTML = ""; 
            
                if (!bookings || bookings.length === 0) {
                    wrapper.innerHTML = "<div class='no-data-text'><h3>No bookings found yet.</h3></div>";
                    return;
                }

                bookings.forEach(b => {
                    const clone = template.content.cloneNode(true);

                    clone.querySelector(".car-name").textContent = b.car_name || `${b.brand} ${b.model}`;
                    clone.querySelector(".loc-val").textContent = b.location || "N/A";
                    
                    clone.querySelector(".from-val").textContent = formatDate(b.pickup_date);
                    clone.querySelector(".to-val").textContent = formatDate(b.return_date);
                    
                    clone.querySelector(".dur-val").textContent = b.total_days;
                    clone.querySelector(".price-val").textContent = `${Number(b.total_price).toLocaleString()} SEK`;
                    
                    const cancelBtn = clone.querySelector(".btn-cancel");
                    cancelBtn.addEventListener("click", () => openCancelModal(b.id));

                    wrapper.appendChild(clone);
                });
            })
            .catch(err => {
                console.error("Fetch error:", err);
                wrapper.innerHTML = "<p class='error-text'>Error loading bookings. Please try again.</p>";
            });
    };

    window.openCancelModal = (id) => {
        bookingIdToDelete = id;
        const modal = document.getElementById("cancel-modal");
        if (modal) modal.style.display = "block";
    };

    window.closeCancelModal = () => {
        const modal = document.getElementById("cancel-modal");
        if (modal) modal.style.display = "none";
        bookingIdToDelete = null;
    };

    const confirmBtn = document.getElementById("confirm-cancel-btn");
    if (confirmBtn) {
        confirmBtn.addEventListener("click", () => {
            if (!bookingIdToDelete) return;

            fetch(`../public/api2.php?action=cancelBooking`, { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json' 
                },
                body: JSON.stringify({
                    booking_id: bookingIdToDelete
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeCancelModal();
                    loadBookings(); 
                } else {
                    alert("Error: " + (data.message || "Could not cancel"));
                }
            })
            .catch(err => {
                console.error("Cancel Error:", err);
                alert("Server error while canceling.");
            });
        });
    }

    const logoutBtn = document.getElementById('logout-btn-profile');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            fetch('../public/api2.php?action=logout').then(() => {
                window.location.href = '../html/biluthyrning.html';
            });
        });
    }

    loadBookings();
});