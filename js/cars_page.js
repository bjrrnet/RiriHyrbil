class CarRentalApp {
    constructor() {
        this.cars = [];
        this.filteredCars = [];
        this.currentFilter = 'all';
        this.selectedCarForBooking = null;
        this.init();
    }
    init() {
        this.bindEvents();
        this.setDefaultDates();
        this.loadCars();
        this.checkNotifications();
    }
    formatDate(dateString) {
        if (!dateString) return "";
        const options = { month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('en-US', options);
    }

    bindEvents() {
        document.getElementById('search-btn')?.addEventListener('click', () => this.searchCars());
        
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.setFilter(e.target.dataset.filter));
        });
        document.addEventListener('click', (e) => {
            const modal = document.getElementById('booking-modal');
            if (modal && modal.style.display === 'block' && e.target === modal) {
                this.closeModal();}
           
            
        });document.getElementById('start-date').addEventListener('change', () => this.searchCars());
        document.getElementById('end-date').addEventListener('change', () => this.searchCars());
        document.getElementById('confirm-booking-btn')?.addEventListener('click', () => this.executeBooking());
        
        document.getElementById('national-id-input')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.executeBooking();
        });
    }
    setDefaultDates() {
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);              
                document.getElementById('start-date').valueAsDate = today;
                document.getElementById('end-date').valueAsDate = tomorrow;
            }
    async loadCars() {
        try {
            this.showLoading(true);
            const response = await fetch('../public/api2.php?action=cars');
            const dbCars = await response.json();
            this.cars = dbCars.map(car => {
                let brand = car.brand;
                let location = car.location;
                const knownBrands = ['Tesla', 'BMW', 'Toyota', 'Porsche', 'Mercedes-Benz', 'Ford', 'Volkswagen', 'Lexus', 'Hyundai', 'Mazda', 'Nissan', 'Jeep', 'Volvo'];                
                if (knownBrands.includes(location)) {
                    [brand, location] = [location, brand];
                }

                return {
                    id: car.id,
                    brand: brand,
                    car_name: car.name,
                    price: parseFloat(car.price_per_day),
                    car_type: car.category,
                    image_url: car.image_url,
                    location: location,
                    next_available_date: car.next_available_date
                };
            });
            this.filteredCars = [...this.cars];
            this.renderCars();
        } 
        catch (error) {
            console.error('Error loading cars:', error);
            this.showError('Error loading cars');
        } 
        finally {
            this.showLoading(false);
        }
    }

    renderCars() {
        const grid = document.getElementById('cars-grid');
        if (!grid) return;

        grid.innerHTML = this.filteredCars.map(car => {
            const today = new Date();
            today.setHours(0,0,0,0);
            const isAvailable = !car.next_available_date || new Date(car.next_available_date) < today;
            const statusHTML = isAvailable 
                ? `<span class="availability-tag status-available">Available</span>`
                : `<span class="availability-tag status-busy" data-tooltip="Available from: ${this.formatDate(car.next_available_date)}">Not Available</span>`;

            return `
                <div class="car-card ${!isAvailable ? 'car-busy' : ''}">
                    <div class="car-image">
                        <img src="${car.image_url}" alt="${car.car_name}" onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22250%22%3E%3Crect width=%22400%22 height=%22250%22 fill=%22%23eee%22/%3E%3Ctext x=%22200%22 y=%22130%22 text-anchor=%22middle%22 fill=%22%23aaa%22 font-size=%2216%22%3ENo image%3C/text%3E%3C/svg%3E'">
                        ${statusHTML}
                    </div>
                    <div class="car-info">
                        <h3 class="car-name">${car.brand} ${car.car_name}</h3>
                        <div class="car-details">
                            <span class="car-type">${car.car_type}</span>
                            <span class="car-location">📍 ${car.location}</span>
                        </div>
                        <div class="car-price">
                            <span class="price">${car.price.toLocaleString()} SEK/Day</span>
                        </div>
                        <button class="book-btn" 
                            ${!isAvailable ? 'disabled style="background: #ccc; cursor: not-allowed;"' : ''} 
                            onclick="carRental.bookCar(${car.id})">
                            ${isAvailable ? 'Book Now' : 'Fully Booked'}
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

searchCars() {
    const loc = document.getElementById('location')?.value.toLowerCase().trim();
    const startDate = document.getElementById('start-date')?.value;
    const endDate = document.getElementById('end-date')?.value;

    if (!startDate || !endDate) {
        this.showError('Please select start and end dates');
        return;
    }

    if (new Date(startDate) >= new Date(endDate)) {
        this.showError('End date must be after start date');
        return;
    }

    this.filteredCars = this.cars.filter(car => {
        const matchesLoc = !loc || car.location.toLowerCase().includes(loc);
        const matchesFilter = this.currentFilter === 'all' || car.car_type === this.currentFilter;
        return matchesLoc && matchesFilter;
    });

    this.renderCars();
}

    setFilter(filter) {
        this.currentFilter = filter;
        document.querySelectorAll('.filter-btn').forEach(btn => 
            btn.classList.toggle('active', btn.dataset.filter === filter)
         
        );
        this.searchCars();
    }
    async bookCar(carId) {
        const res = await fetch('../public/api2.php?action=checkLogin');
        const data = await res.json();
        
        if (!data.loggedIn) {
            window.location.href = '../html/bil_login.html';
            return;
        }

        const car = this.cars.find(c => c.id === carId);
        const start = document.getElementById('start-date')?.value;
        const end = document.getElementById('end-date')?.value;
        
        if (!start || !end) return alert('Please select dates in the search bar first');

        const days = Math.ceil(Math.abs(new Date(end) - new Date(start)) / (1000*60*60*24)) || 1;
        const total = days * car.price;
        
        this.selectedCarForBooking = { ...car, days, total, start, end };

        if (data.national_id) {
            this.selectedCarForBooking.national_id = data.national_id;
            this.executeBooking();
            return;
        }
        
        const detailsContainer = document.getElementById('modal-car-details');
        if (detailsContainer) {
            detailsContainer.innerHTML = `
                <p>Booking <b>${car.brand} ${car.car_name}</b></p>
                <p>Duration: ${days} days (${start} to ${end})</p>
                <p>Total Price: <b>${total.toLocaleString()} SEK</b></p>
            `;
        }
        
        document.getElementById('booking-modal').style.display = 'block';
    }

    async executeBooking() {
        const input = document.getElementById('national-id-input');
        const nationalId = this.selectedCarForBooking?.national_id || input?.value.trim();

        if (!nationalId || nationalId.length !== 12 || !/^\d+$/.test(nationalId)) {
            alert("National ID must be exactly 12 digits");
            return;
        }

        const bookingData = {
            car_id: this.selectedCarForBooking.id,
            pickup_date: this.selectedCarForBooking.start,
            return_date: this.selectedCarForBooking.end,
            total_days: this.selectedCarForBooking.days,
            total_price: this.selectedCarForBooking.total,
            national_id: nationalId
        };

        try {
            const res = await fetch('../public/api2.php?action=book', {
                method: 'POST',
                body: JSON.stringify(bookingData),
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await res.json();
            
            if (result.success) {
                localStorage.setItem('hasNewBooking', 'true');
                this.showSuccess("Booking Successful!");
                this.closeModal();
                this.loadCars(); 
                this.checkNotifications();
            } else {
                this.showError(result.message || "Failed to book car");
            }
        } 
        catch (e) { alert("Error connecting to server"); }
    }

    closeModal() { 
        const modal = document.getElementById('booking-modal');
        if (modal) modal.style.display = 'none'; 
    }

    showLoading(show) {
        const loading = document.getElementById('loading');
        if (loading) loading.style.display = show ? 'block' : 'none';
    }

    checkNotifications() {
        if (localStorage.getItem('hasNewBooking') === 'true') {
            document.getElementById('account-dot')?.classList.add('show-dot');
            document.getElementById('bookings-dot')?.classList.add('show-dot');
        }
    }

    setDefaultDates() {
        const start = document.getElementById('start-date'), end = document.getElementById('end-date');
        if (start && end && !start.value) {
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            start.valueAsDate = today;
            end.valueAsDate = tomorrow;
        }
    }
    showLoading(show) {
                const loading = document.getElementById('loading');
                loading.style.display = show ? 'block' : 'none';
            }
    showError(message) {
        this.showNotification(message, 'error');
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
    }
}
window.carRental = new CarRentalApp();