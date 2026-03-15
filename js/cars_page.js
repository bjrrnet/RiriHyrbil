class CarRentalApp {
    constructor() {
    this.cars = [];
    this.filteredCars = [];
    this.currentFilter = 'all';
    this.mockData = [
        {
        'id': 1,
        'plate': 'ELEC001',
        'mileage': 15000,
        'price': 89.99,
        'brand': 'Tesla',
        'car_name': 'Model 3 Standard',
        'car_type': 'Electric',
        'car_type_description': 'Environmentally friendly electric vehicles'
        },
        {
        'id': 2,
        'plate': 'ELEC002',
        'mileage': 23000,
        'price': 95.00,
        'brand': 'Tesla',
        'car_name': 'Model Y Long Range',
        'car_type': 'Electric',
        'car_type_description': 'Environmentally friendly electric vehicles'
        },
        {
        'id': 3,
        'plate': 'ELEC003',
        'mileage': 8000,
        'price': 75.00,
        'brand': 'Nissan',
        'car_name': 'Leaf S Plus',
        'car_type': 'Electric',
        'car_type_description': 'Environmentally friendly electric vehicles'
        },
        {
        'id': 4,
        'plate': 'SUV001',
        'mileage': 35000,
        'price': 120.00,
        'brand': 'Toyota',
        'car_name': 'RAV4 Limited',
        'car_type': 'SUV',
        'car_type_description': 'Sport Utility Vehicles with ample space'
        },
        {
        'id': 5,
        'plate': 'SUV002',
        'mileage': 28000,
        'price': 135.00,
        'brand': 'Honda',
        'car_name': 'CR-V EX',
        'car_type': 'SUV',
        'car_type_description': 'Sport Utility Vehicles with ample space'
        },
        {
        'id': 6,
        'plate': 'LUX001',
        'mileage': 12000,
        'price': 250.00,
        'brand': 'Mercedes',
        'car_name': 'S-Class 580',
        'car_type': 'Luxury',
        'car_type_description': 'Premium luxury vehicles'
        },
        {
        'id': 7,
        'plate': 'LUX002',
        'mileage': 18000,
        'price': 275.00,
        'brand': 'BMW',
        'car_name': '7 Series 740i',
        'car_type': 'Luxury',
        'car_type_description': 'Premium luxury vehicles'
        },
        {
        'id': 8,
        'plate': 'SED001',
        'mileage': 45000,
        'price': 65.00,
        'brand': 'Toyota',
        'car_name': 'Camry XSE',
        'car_type': 'Sedan',
        'car_type_description': 'Comfortable family sedans'
        },
        {
        'id': 9,
        'plate': 'COM001',
        'mileage': 55000,
        'price': 45.00,
        'brand': 'Honda',
        'car_name': 'Civic Sport',
        'car_type': 'Compact',
        'car_type_description': 'Small and efficient city cars'
        },
        {
        'id': 10,
        'plate': 'HYB001',
        'mileage': 67000,
        'price': 78.00,
        'brand': 'Toyota',
        'car_name': 'Prius Prime',
        'car_type': 'Hybrid',
        'car_type_description': 'Fuel-efficient hybrid vehicles'
        }
        ];
                
        this.init();
        }

        init() {
        this.bindEvents();
        this.setDefaultDates();
        this.loadCars();
        }

        bindEvents() {
            document.getElementById('search-btn').addEventListener('click', () => this.searchCars());
                
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', (e) => this.setFilter(e.target.dataset.filter));
            });

        document.getElementById('location').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.searchCars();
            });

        document.getElementById('start-date').addEventListener('change', () => this.searchCars());
        document.getElementById('end-date').addEventListener('change', () => this.searchCars());
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
                this.cars = dbCars.map(car => ({ 
                    id: car.id,
                    plate: car.year,
                    mileage: car.price_per_day,
                    price: car.price_per_day,
                    brand: car.brand,
                    car_name: car.name,
                    car_type: car.category,
                    image_url: car.image_url
                    }));
                    this.cars = this.enrichCars(this.cars);
                    this.filteredCars = [...this.cars];
                    this.renderCars();  
                
                }catch (error) {
            console.error('Error loading cars:', error);
            this.showError('Error loading cars');
        }
            finally {
                this.showLoading(false);
            }
        }

            async searchCars() {
                const location = document.getElementById('location').value.trim();
                const startDate = document.getElementById('start-date').value;
                const endDate = document.getElementById('end-date').value;

                if (!startDate || !endDate) {
                    this.showError('Please select start and end dates');
                    return;
                }

                if (new Date(startDate) >= new Date(endDate)) {
                    this.showError('End date must be after start date');
                    return;
                }

                try {
                    this.showLoading(true);
                    
                    await new Promise(resolve => setTimeout(resolve, 500));
                    
                    let availableCars = [...this.mockData];
                    
                    if (location) {
                        const locationLower = location.toLowerCase();
                        availableCars = availableCars.filter(car => 
                            car.brand.toLowerCase().includes(locationLower) || 
                            car.car_name.toLowerCase().includes(locationLower)
                        );
                    }
                    
                    this.cars = this.enrichCars(availableCars);
                    this.applyCurrentFilter();
                    
                } catch (error) {
                    console.error('Error searching cars:', error);
                    this.showError('Error searching cars');
                } finally {
                    this.showLoading(false);
                }
            }

            enrichCars(cars) {
                return cars.map(car => ({
                    ...car,
                    category: this.getCategory(car.car_type),
                    typeName: this.formatTypeName(car.car_type),
                    pricePerDay: parseFloat(car.price),
                    displayName: car.car_name || `${car.brand} ${car.plate}`
                }));
            }

            getCategory(carType) {
                const type = carType.toLowerCase();
                if (type.includes('electric') || type.includes('tesla') || type.includes('hybrid')) {
                    return 'electric';
                }
                if (type.includes('suv') || type.includes('truck') || type.includes('van')) {
                    return 'suv';
                }
                if (type.includes('luxury') || type.includes('premium') || type.includes('bmw') || type.includes('mercedes') || type.includes('audi')) {
                    return 'luxury';
                }
                return 'standard';
            }

            formatTypeName(carType) {
                return carType.charAt(0).toUpperCase() + carType.slice(1);
            }

            setFilter(filter) {
                this.currentFilter = filter;
                
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelector(`[data-filter="${filter}"]`).classList.add('active');
                
                this.applyCurrentFilter();
            }

            applyCurrentFilter() {
                if (this.currentFilter === 'all') {
                    this.filteredCars = [...this.cars];
                } else {
                    this.filteredCars = this.cars.filter(car => car.category === this.currentFilter);
                }
                
                this.renderCars();
            }

            renderCars() {
                const grid = document.getElementById('cars-grid');
                const noResults = document.getElementById('no-results');
                
                if (this.filteredCars.length === 0) {
                    grid.innerHTML = '';
                    noResults.style.display = 'block';
                    return;
                }
                
                noResults.style.display = 'none';
                
                grid.innerHTML = this.filteredCars.map(car => `
                    <div class="car-card" data-car-id="${car.id}">
                        <div class="car-image">
                            <img src="${car.image_url}" alt="${car.displayName}">
                        </div>
                        <div class="car-info">
                            <h3 class="car-name">${car.displayName}</h3>
                            <div class="car-details">
                                <span class="car-type">${car.typeName}</span>
                                <span class="car-category">${car.category}</span>
                            </div>
                            <div class="car-specs">
                                <span class="spec">Year: ${car.plate}</span>
                            </div>
                            <div class="car-price">
                                <span class="price">$${car.pricePerDay}/day</span>
                            </div>
                            <button class="book-btn" onclick="carRental.bookCar(${car.id})">Book Now</button>
                        </div>
                    </div>
                `).join('');
            }

            async bookCar(carId) {
                const startDate = document.getElementById('start-date').value;
                const endDate = document.getElementById('end-date').value;
                
                if (!startDate || !endDate) {
                    this.showError('Please select dates first');
                    return;
                }
                const bookingData = {
                    car_id: carId,
                    pickup_date: startDate,
                    return_date: endDate,
                    email: "user@example.com",
                    phone: "00000000",
                    total_days: 1,
                    total_price: 100
                };
                try {
                    const response = await fetch('../public/api2.php?action=book', {
                        method: 'POST',
                        body: JSON.stringify(bookingData),
                        headers: { 'Content-Type': 'application/json' }
                    });
                    const result = await response.json();
                    if (result.success) {
                        this.showSuccess('Car booked successfully!');
                    } else {
                        this.showError(result.message || 'Failed to book car');
                    }
                } catch (error) {
                    console.error('Error booking car:', error);
                    this.showError('Error booking car');
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
document.addEventListener('DOMContentLoaded', () => {
    window.carRental = new CarRentalApp();
});
