USE databas;

INSERT INTO car_type (name, description) VALUES
('Kombi', 'Femdörrars med extra lastutrymme'),
('Van', 'Tresitsig skåpbil med stort lastutrymme'),
('Sedan', 'Femdörrars');

INSERT INTO cars (car_type, plate, mileage, price_category) VALUES
(1, 'ABC012', 201203, 1), 
(2, 'TYU234', 340123, 2), 
(3, 'OIU23U', 94382, 1);
