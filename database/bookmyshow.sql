-- BookYourShow Clone Database
CREATE DATABASE IF NOT EXISTS bookmyshow_clone;
USE bookmyshow_clone;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(15),
    password VARCHAR(255),
    profile_image VARCHAR(255) DEFAULT 'default.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin Table
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    password VARCHAR(255)
);

-- Operators Table
CREATE TABLE IF NOT EXISTS operators (
    operator_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    organization VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Movies Table
CREATE TABLE IF NOT EXISTS movies (
    movie_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150),
    description TEXT,
    genre VARCHAR(100),
    duration VARCHAR(20),
    language VARCHAR(50),
    release_date DATE,
    poster VARCHAR(255),
    trailer_url VARCHAR(255),
    rating DECIMAL(2,1)
);

-- Theatres Table
CREATE TABLE IF NOT EXISTS theatres (
    theatre_id INT AUTO_INCREMENT PRIMARY KEY,
    theatre_name VARCHAR(150),
    city VARCHAR(100),
    location VARCHAR(255)
);

-- Shows Table
CREATE TABLE IF NOT EXISTS shows (
    show_id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT,
    theatre_id INT,
    show_date DATE,
    show_time TIME,
    price DECIMAL(10,2),
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id),
    FOREIGN KEY (theatre_id) REFERENCES theatres(theatre_id)
);

-- Seats Table
CREATE TABLE IF NOT EXISTS seats (
    seat_id INT AUTO_INCREMENT PRIMARY KEY,
    show_id INT,
    seat_number VARCHAR(10),
    status VARCHAR(20) DEFAULT 'available',
    FOREIGN KEY (show_id) REFERENCES shows(show_id)
);

-- Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    show_id INT,
    seats VARCHAR(100),
    total_price DECIMAL(10,2),
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    booking_status VARCHAR(50) DEFAULT 'confirmed',
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (show_id) REFERENCES shows(show_id)
);

-- Payments Table
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    payment_method VARCHAR(50),
    amount DECIMAL(10,2),
    payment_status VARCHAR(50) DEFAULT 'success',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
);

-- Events Table
CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(150),
    description TEXT,
    event_date DATE,
    location VARCHAR(255),
    ticket_price DECIMAL(10,2),
    total_seats INT DEFAULT 500,
    organizer_id INT,
    event_image VARCHAR(255),
    FOREIGN KEY (organizer_id) REFERENCES operators(operator_id)
);

-- Event Bookings Table
CREATE TABLE IF NOT EXISTS event_bookings (
    event_booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    event_id INT,
    tickets INT,
    total_price DECIMAL(10,2),
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id)
);

-- Foods Table
CREATE TABLE IF NOT EXISTS foods (
    food_id INT AUTO_INCREMENT PRIMARY KEY,
    food_name VARCHAR(100),
    price DECIMAL(10,2),
    image VARCHAR(255)
);

-- Food Orders Table
CREATE TABLE IF NOT EXISTS food_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    food_items VARCHAR(255),
    total_price DECIMAL(10,2),
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
);

-- Coupons Table
CREATE TABLE IF NOT EXISTS coupons (
    coupon_id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_code VARCHAR(50) UNIQUE,
    discount_percent INT,
    expiry_date DATE
);

-- Reviews Table
CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    movie_id INT,
    rating INT,
    comment TEXT,
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id)
);

-- Wishlist Table
CREATE TABLE IF NOT EXISTS wishlist (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    movie_id INT,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id)
);

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Contact Messages Table
CREATE TABLE IF NOT EXISTS contacts (
    contact_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cities Table
CREATE TABLE IF NOT EXISTS cities (
    city_id INT AUTO_INCREMENT PRIMARY KEY,
    city_name VARCHAR(100)
);

-- ===================================
-- SAMPLE DATA
-- ===================================

-- Admin (password: admin123)
INSERT INTO admins (username, password) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Cities
INSERT INTO cities (city_name) VALUES ('Mumbai'), ('Delhi'), ('Bangalore'), ('Hyderabad'), ('Chennai'), ('Kolkata'), ('Pune'), ('Ahmedabad');

-- Movies
INSERT INTO movies (title, description, genre, duration, language, release_date, poster, trailer_url, rating) VALUES
('Avengers: Endgame', 'The Avengers must undo the actions of Thanos in a desperate attempt to restore order to the universe.', 'Action, Adventure, Drama', '181 min', 'English', '2019-04-26', 'avengers.jpg', 'https://www.youtube.com/embed/TcMBFSGVi1c', 8.4),
('Spider-Man: No Way Home', 'Spider-Man seeks the help of Doctor Strange to forget his exposed identity as Peter Parker.', 'Action, Adventure, Fantasy', '148 min', 'English', '2021-12-17', 'spiderman.jpg', 'https://www.youtube.com/embed/JfVOs4VSpmA', 8.2),
('KGF Chapter 2', 'Rocky continues his rule in the Kolar Gold Fields, but must face new threats.', 'Action, Drama', '168 min', 'Kannada', '2022-04-14', 'kgf2.jpg', 'https://www.youtube.com/embed/iCDXBxYxjOE', 8.3),
('RRR', 'A fictional story about two Indian revolutionaries, Alluri Sitarama Raju and Komaram Bheem.', 'Action, Drama', '187 min', 'Telugu', '2022-03-25', 'rrr.jpg', 'https://www.youtube.com/embed/f_vbAtFSEc0', 7.9),
('Pathaan', 'An exiled spy is brought back to thwart a plot against India by a vengeful former agent.', 'Action, Thriller', '146 min', 'Hindi', '2023-01-25', 'pathaan.jpg', 'https://www.youtube.com/embed/vqu4z34wENw', 5.9),
('The Dark Knight', 'Batman faces the Joker, a criminal mastermind who plunges Gotham into anarchy.', 'Action, Crime, Drama', '152 min', 'English', '2008-07-18', 'darkknight.jpg', 'https://www.youtube.com/embed/EXeTwQWrcwY', 9.0),
('Jawan', 'A man is driven by a personal cause to rectify the wrongs in society.', 'Action, Thriller', '169 min', 'Hindi', '2023-09-07', 'jawan.jpg', 'https://www.youtube.com/embed/pNAWy0mDmXE', 6.5),
('Oppenheimer', 'The story of American scientist J. Robert Oppenheimer and his role in the development of the atomic bomb.', 'Biography, Drama, History', '180 min', 'English', '2023-07-21', 'oppenheimer.jpg', 'https://www.youtube.com/embed/uYPbbksJxIg', 8.9);

-- Theatres
INSERT INTO theatres (theatre_name, city, location) VALUES
('PVR Phoenix', 'Mumbai', 'Phoenix Palladium, Lower Parel, Mumbai'),
('INOX Nariman Point', 'Mumbai', 'Nariman Point, Mumbai'),
('PVR Saket', 'Delhi', 'Select Citywalk, Saket, Delhi'),
('Cinepolis Viviana', 'Mumbai', 'Viviana Mall, Thane, Mumbai'),
('PVR Forum', 'Bangalore', 'Forum Mall, Koramangala, Bangalore'),
('INOX GVK One', 'Hyderabad', 'GVK One Mall, Banjara Hills, Hyderabad'),
('PVR Sathyam', 'Chennai', 'Sathyam Cinemas, Chennai'),
('INOX South City', 'Kolkata', 'South City Mall, Kolkata');

-- Shows
INSERT INTO shows (movie_id, theatre_id, show_date, show_time, price) VALUES
(1, 1, '2026-03-15', '10:00:00', 250.00),
(1, 1, '2026-03-15', '14:00:00', 300.00),
(1, 1, '2026-03-15', '18:00:00', 350.00),
(2, 1, '2026-03-15', '11:00:00', 280.00),
(2, 2, '2026-03-15', '15:00:00', 300.00),
(3, 3, '2026-03-15', '10:30:00', 220.00),
(4, 4, '2026-03-15', '13:00:00', 260.00),
(5, 5, '2026-03-15', '16:00:00', 240.00),
(6, 6, '2026-03-16', '10:00:00', 200.00),
(7, 7, '2026-03-16', '14:00:00', 270.00),
(8, 8, '2026-03-16', '18:00:00', 320.00),
(1, 2, '2026-03-16', '12:00:00', 290.00),
(2, 3, '2026-03-16', '16:30:00', 260.00),
(3, 4, '2026-03-17', '09:00:00', 210.00),
(4, 5, '2026-03-17', '13:00:00', 240.00),
(5, 6, '2026-03-17', '17:00:00', 230.00);

-- Foods
INSERT INTO foods (food_name, price, image) VALUES
('Regular Popcorn', 150.00, 'popcorn.jpg'),
('Nachos with Cheese', 199.00, 'nachos.jpg'),
('Pepsi (500ml)', 100.00, 'pepsi.jpg'),
('Combo: Popcorn + Pepsi', 230.00, 'combo.jpg'),
('Burger', 180.00, 'burger.jpg'),
('Hot Dog', 120.00, 'hotdog.jpg'),
('Coffee', 90.00, 'coffee.jpg'),
('Mineral Water', 50.00, 'water.jpg');

-- Coupons
INSERT INTO coupons (coupon_code, discount_percent, expiry_date) VALUES
('FIRST50', 50, '2026-12-31'),
('SAVE20', 20, '2026-06-30'),
('WELCOME10', 10, '2026-12-31'),
('MOVIE30', 30, '2026-09-30');

-- Notifications
INSERT INTO notifications (title, message) VALUES
('New Movies Added', 'Check out the latest blockbusters now showing in theatres near you!'),
('Offer Alert', 'Use coupon FIRST50 for 50% off on your first booking!'),
('Weekend Special', 'Book 2 tickets and get free popcorn this weekend!');

-- Sample Operator (password: operator123)
INSERT INTO operators (name, email, password, organization) VALUES
('John Events', 'john@events.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MegaEvents Inc.');

-- Events
INSERT INTO events (event_name, description, event_date, location, ticket_price, organizer_id, event_image) VALUES
('Arijit Singh Live Concert', 'Experience the magic of Arijit Singh live on stage with a 3-hour performance.', '2026-04-15', 'MMRDA Grounds, BKC, Mumbai', 2500.00, 1, 'arijit.jpg'),
('Sunburn Festival 2026', 'Asia''s biggest electronic music festival returns to Goa!', '2026-04-20', 'Vagator Beach, Goa', 3500.00, 1, 'sunburn.jpg'),
('IPL 2026 - MI vs CSK', 'Watch the epic rivalry between Mumbai Indians and Chennai Super Kings.', '2026-03-28', 'Wankhede Stadium, Mumbai', 1500.00, 1, 'ipl.jpg'),
('Comedy Night with Kapil', 'A hilarious evening with India''s most loved comedian.', '2026-04-05', 'NCPA, Mumbai', 1200.00, 1, 'comedy.jpg'),
('Rock in India Festival', 'Biggest rock music festival featuring top national and international bands.', '2026-05-10', 'Palace Grounds, Bangalore', 2000.00, 1, 'rock.jpg');
