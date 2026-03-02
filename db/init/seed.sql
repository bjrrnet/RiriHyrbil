INSERT INTO car_type (name, description) VALUES
('Kombi', 'Femdörrars med extra lastutrymme'),
('Van', 'Tresitsig skåpbil med stort lastutrymme'),
('Sedan', 'Femdörrars');

INSERT INTO cars (car_type, plate, mileage, price_category) VALUES
(1, 'ABC012', 201203, 1), 
(2, 'TYU234', 340123, 2), 
(3, 'OIU23U', 94382, 1);


-- password123 hashat password_hash('password123', PASSWORD_BCRYPT)
INSERT INTO users (name, email, phone_number, password_hash, role) VALUES
('Admin',   'admin@bilix.se',   '0701234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Test User', 'testUser@bilix.se', '0734567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer')
