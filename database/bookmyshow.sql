-- ============================================================
-- BookYourShow Clone - Complete Database (v2)
-- Fresh install: run this file ONCE in phpMyAdmin or MySQL CLI
-- No separate migration needed.
-- ============================================================

CREATE DATABASE IF NOT EXISTS bookmyshow_clone;
USE bookmyshow_clone;

-- ============================================================
-- TABLES
-- ============================================================

-- Users
CREATE TABLE IF NOT EXISTS users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100),
    email         VARCHAR(100) UNIQUE,
    phone         VARCHAR(15),
    password      VARCHAR(255),
    city          VARCHAR(100) DEFAULT '',
    profile_image VARCHAR(255) DEFAULT 'default.png',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admins
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    password VARCHAR(255)
);

-- Operators
CREATE TABLE IF NOT EXISTS operators (
    operator_id  INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100),
    email        VARCHAR(100) UNIQUE,
    password     VARCHAR(255),
    organization VARCHAR(150),
    city         VARCHAR(100) DEFAULT '',
    status       ENUM('active','suspended') DEFAULT 'active',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Movies
CREATE TABLE IF NOT EXISTS movies (
    movie_id     INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(150),
    description  TEXT,
    genre        VARCHAR(100),
    duration     VARCHAR(20),
    language     VARCHAR(50),
    release_date DATE,
    poster       VARCHAR(255),
    banner_image VARCHAR(255) DEFAULT '',
    trailer_url  VARCHAR(255),
    rating       DECIMAL(2,1),
    status       ENUM('active','inactive') DEFAULT 'active'
);

-- Theatres
CREATE TABLE IF NOT EXISTS theatres (
    theatre_id   INT AUTO_INCREMENT PRIMARY KEY,
    theatre_name VARCHAR(150),
    city         VARCHAR(100),
    location     VARCHAR(255),
    map_link     VARCHAR(500) DEFAULT '',
    is_verified  TINYINT DEFAULT 0,
    added_by     INT DEFAULT NULL
);

-- Theatre Licences
CREATE TABLE IF NOT EXISTS theatre_licences (
    licence_id     INT AUTO_INCREMENT PRIMARY KEY,
    theatre_id     INT NOT NULL,
    operator_id    INT NOT NULL,
    licence_number VARCHAR(150) NOT NULL,
    status         ENUM('pending','approved','rejected') DEFAULT 'pending',
    rejection_note VARCHAR(500) DEFAULT '',
    submitted_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at    TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_theatre_operator (theatre_id, operator_id),
    FOREIGN KEY (theatre_id)  REFERENCES theatres(theatre_id)  ON DELETE CASCADE,
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE CASCADE
);

-- Shows
CREATE TABLE IF NOT EXISTS shows (
    show_id     INT AUTO_INCREMENT PRIMARY KEY,
    movie_id    INT,
    theatre_id  INT,
    show_date   DATE,
    show_time   TIME,
    language    VARCHAR(50) DEFAULT 'Hindi',
    price       DECIMAL(10,2),
    operator_id INT DEFAULT NULL,
    status      ENUM('active','pending','cancelled') DEFAULT 'active',
    FOREIGN KEY (movie_id)   REFERENCES movies(movie_id),
    FOREIGN KEY (theatre_id) REFERENCES theatres(theatre_id)
);

-- Seats
CREATE TABLE IF NOT EXISTS seats (
    seat_id     INT AUTO_INCREMENT PRIMARY KEY,
    show_id     INT,
    seat_number VARCHAR(10),
    status      VARCHAR(20) DEFAULT 'available',
    FOREIGN KEY (show_id) REFERENCES shows(show_id)
);

-- Bookings
CREATE TABLE IF NOT EXISTS bookings (
    booking_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT,
    show_id        INT,
    seats          VARCHAR(100),
    total_price    DECIMAL(10,2),
    booking_date   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    booking_status VARCHAR(50) DEFAULT 'confirmed',
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (show_id) REFERENCES shows(show_id)
);

-- Payments
CREATE TABLE IF NOT EXISTS payments (
    payment_id     INT AUTO_INCREMENT PRIMARY KEY,
    booking_id     INT,
    payment_method VARCHAR(50),
    amount         DECIMAL(10,2),
    payment_status VARCHAR(50) DEFAULT 'success',
    payment_date   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
);

-- Events
CREATE TABLE IF NOT EXISTS events (
    event_id     INT AUTO_INCREMENT PRIMARY KEY,
    event_name   VARCHAR(150),
    description  TEXT,
    event_date   DATE,
    location     VARCHAR(255),
    city         VARCHAR(100) DEFAULT '',
    ticket_price DECIMAL(10,2),
    total_seats  INT DEFAULT 500,
    organizer_id INT,
    event_image  VARCHAR(255),
    banner_image VARCHAR(255) DEFAULT '',
    status       ENUM('pending','approved','rejected') DEFAULT 'approved',
    FOREIGN KEY (organizer_id) REFERENCES operators(operator_id)
);

-- Event Bookings
CREATE TABLE IF NOT EXISTS event_bookings (
    event_booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT,
    event_id         INT,
    tickets          INT,
    total_price      DECIMAL(10,2),
    booking_date     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(user_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id)
);

-- Foods
CREATE TABLE IF NOT EXISTS foods (
    food_id   INT AUTO_INCREMENT PRIMARY KEY,
    food_name VARCHAR(100),
    price     DECIMAL(10,2),
    image     VARCHAR(255)
);

-- Food Orders
CREATE TABLE IF NOT EXISTS food_orders (
    order_id    INT AUTO_INCREMENT PRIMARY KEY,
    booking_id  INT,
    food_items  VARCHAR(255),
    total_price DECIMAL(10,2),
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
);

-- Coupons
CREATE TABLE IF NOT EXISTS coupons (
    coupon_id       INT AUTO_INCREMENT PRIMARY KEY,
    coupon_code     VARCHAR(50) UNIQUE,
    discount_percent INT,
    expiry_date     DATE
);

-- Reviews
CREATE TABLE IF NOT EXISTS reviews (
    review_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT,
    movie_id    INT,
    rating      INT,
    comment     TEXT,
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(user_id),
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id)
);

-- Wishlist
CREATE TABLE IF NOT EXISTS wishlist (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT,
    movie_id    INT,
    FOREIGN KEY (user_id)  REFERENCES users(user_id),
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id)
);

-- Notifications (user-facing)
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(150),
    message         TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin Notifications
CREATE TABLE IF NOT EXISTS admin_notifications (
    notif_id     INT AUTO_INCREMENT PRIMARY KEY,
    type         VARCHAR(50) NOT NULL DEFAULT 'general',
    title        VARCHAR(200) NOT NULL,
    message      TEXT,
    reference_id INT DEFAULT NULL,
    is_read      TINYINT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Contact Messages
CREATE TABLE IF NOT EXISTS contacts (
    contact_id INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100),
    email      VARCHAR(100),
    message    TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Admin (password: admin123)
INSERT IGNORE INTO admins (username, password)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Movies
INSERT IGNORE INTO movies (movie_id, title, description, genre, duration, language, release_date, poster, trailer_url, rating, status) VALUES
(1, 'Avengers: Endgame',      'The Avengers must undo the actions of Thanos in a desperate attempt to restore order to the universe.',             'Action, Adventure, Drama',     '181 min', 'English', '2019-04-26', 'avengers.jpg',    'https://www.youtube.com/embed/TcMBFSGVi1c', 8.4, 'active'),
(2, 'Spider-Man: No Way Home','Spider-Man seeks the help of Doctor Strange to forget his exposed identity as Peter Parker.',                        'Action, Adventure, Fantasy',   '148 min', 'English', '2021-12-17', 'spiderman.jpg',   'https://www.youtube.com/embed/JfVOs4VSpmA', 8.2, 'active'),
(3, 'KGF Chapter 2',          'Rocky continues his rule in the Kolar Gold Fields, but must face new threats.',                                     'Action, Drama',                '168 min', 'Kannada', '2022-04-14', 'kgf2.jpg',        'https://www.youtube.com/embed/iCDXBxYxjOE', 8.3, 'active'),
(4, 'RRR',                    'A fictional story about two Indian revolutionaries, Alluri Sitarama Raju and Komaram Bheem.',                       'Action, Drama',                '187 min', 'Telugu',  '2022-03-25', 'rrr.jpg',         'https://www.youtube.com/embed/f_vbAtFSEc0', 7.9, 'active'),
(5, 'Pathaan',                'An exiled spy is brought back to thwart a plot against India by a vengeful former agent.',                          'Action, Thriller',             '146 min', 'Hindi',   '2023-01-25', 'pathaan.jpg',     'https://www.youtube.com/embed/vqu4z34wENw', 5.9, 'active'),
(6, 'The Dark Knight',        'Batman faces the Joker, a criminal mastermind who plunges Gotham into anarchy.',                                   'Action, Crime, Drama',         '152 min', 'English', '2008-07-18', 'darkknight.jpg',  'https://www.youtube.com/embed/EXeTwQWrcwY', 9.0, 'active'),
(7, 'Jawan',                  'A man is driven by a personal cause to rectify the wrongs in society.',                                            'Action, Thriller',             '169 min', 'Hindi',   '2023-09-07', 'jawan.jpg',       'https://www.youtube.com/embed/pNAWy0mDmXE', 6.5, 'active'),
(8, 'Oppenheimer',            'The story of American scientist J. Robert Oppenheimer and his role in the development of the atomic bomb.',         'Biography, Drama, History',    '180 min', 'English', '2023-07-21', 'oppenheimer.jpg', 'https://www.youtube.com/embed/uYPbbksJxIg', 8.9, 'active');

-- Theatres (pre-verified — no licence needed for sample data)
INSERT IGNORE INTO theatres (theatre_id, theatre_name, city, location, is_verified) VALUES
(1, 'PVR Phoenix',        'Mumbai',    'Phoenix Palladium, Lower Parel, Mumbai',        1),
(2, 'INOX Nariman Point', 'Mumbai',    'Nariman Point, Mumbai',                         1),
(3, 'PVR Saket',          'Delhi',     'Select Citywalk, Saket, Delhi',                 1),
(4, 'Cinepolis Viviana',  'Mumbai',    'Viviana Mall, Thane, Mumbai',                   1),
(5, 'PVR Forum',          'Bangalore', 'Forum Mall, Koramangala, Bangalore',            1),
(6, 'INOX GVK One',       'Hyderabad', 'GVK One Mall, Banjara Hills, Hyderabad',        1),
(7, 'PVR Sathyam',        'Chennai',   'Sathyam Cinemas, Chennai',                      1),
(8, 'INOX South City',    'Kolkata',   'South City Mall, Kolkata',                      1),
(9, 'Cinepolis Fun Republic','Indore', 'Fun Republic Mall, AB Road, Indore',            1);

-- Shows (dates set dynamically to future using INTERVAL)
INSERT IGNORE INTO shows (movie_id, theatre_id, show_date, show_time, language, price, status) VALUES
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY),  '10:00:00', 'English', 250.00, 'active'),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY),  '14:00:00', 'English', 300.00, 'active'),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY),  '18:00:00', 'English', 350.00, 'active'),
(2, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY),  '11:00:00', 'English', 280.00, 'active'),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY),  '15:00:00', 'English', 300.00, 'active'),
(3, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY),  '10:30:00', 'Kannada', 220.00, 'active'),
(4, 4, DATE_ADD(CURDATE(), INTERVAL 3 DAY),  '13:00:00', 'Telugu',  260.00, 'active'),
(5, 5, DATE_ADD(CURDATE(), INTERVAL 4 DAY),  '16:00:00', 'Hindi',   240.00, 'active'),
(6, 6, DATE_ADD(CURDATE(), INTERVAL 5 DAY),  '10:00:00', 'English', 200.00, 'active'),
(7, 7, DATE_ADD(CURDATE(), INTERVAL 6 DAY),  '14:00:00', 'Hindi',   270.00, 'active'),
(8, 8, DATE_ADD(CURDATE(), INTERVAL 7 DAY),  '18:00:00', 'English', 320.00, 'active'),
(1, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY),  '12:00:00', 'English', 290.00, 'active'),
(2, 3, DATE_ADD(CURDATE(), INTERVAL 3 DAY),  '16:30:00', 'English', 260.00, 'active'),
(3, 4, DATE_ADD(CURDATE(), INTERVAL 4 DAY),  '09:00:00', 'Kannada', 210.00, 'active'),
(4, 5, DATE_ADD(CURDATE(), INTERVAL 5 DAY),  '13:00:00', 'Telugu',  240.00, 'active'),
(5, 6, DATE_ADD(CURDATE(), INTERVAL 6 DAY),  '17:00:00', 'Hindi',   230.00, 'active'),
(1, 9, DATE_ADD(CURDATE(), INTERVAL 2 DAY),  '12:30:00', 'Hindi',   220.00, 'active'),
(6, 9, DATE_ADD(CURDATE(), INTERVAL 3 DAY),  '19:00:00', 'English', 250.00, 'active');

-- Foods
INSERT IGNORE INTO foods (food_name, price, image) VALUES
('Regular Popcorn',      150.00, 'popcorn.jpg'),
('Nachos with Cheese',   199.00, 'nachos.jpg'),
('Pepsi (500ml)',         100.00, 'pepsi.jpg'),
('Combo: Popcorn + Pepsi',230.00,'combo.jpg'),
('Burger',               180.00, 'burger.jpg'),
('Hot Dog',              120.00, 'hotdog.jpg'),
('Coffee',                90.00, 'coffee.jpg'),
('Mineral Water',         50.00, 'water.jpg');

-- Coupons
INSERT IGNORE INTO coupons (coupon_code, discount_percent, expiry_date) VALUES
('FIRST50',   50, '2026-12-31'),
('SAVE20',    20, '2026-06-30'),
('WELCOME10', 10, '2026-12-31'),
('MOVIE30',   30, '2026-09-30');

-- Notifications
INSERT IGNORE INTO notifications (title, message) VALUES
('New Movies Added',  'Check out the latest blockbusters now showing in theatres near you!'),
('Offer Alert',       'Use coupon FIRST50 for 50% off on your first booking!'),
('Weekend Special',   'Book 2 tickets and get free popcorn this weekend!');

-- Sample Operator (password: operator123)
INSERT IGNORE INTO operators (operator_id, name, email, password, organization, city, status)
VALUES (1, 'John Events', 'john@events.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MegaEvents Inc.', 'Mumbai', 'active');

-- Events
INSERT IGNORE INTO events (event_name, description, event_date, location, city, ticket_price, organizer_id, event_image, status) VALUES
('Arijit Singh Live Concert', 'Experience the magic of Arijit Singh live on stage with a 3-hour performance.', DATE_ADD(CURDATE(), INTERVAL 17 DAY), 'MMRDA Grounds, BKC, Mumbai',    'Mumbai',    2500.00, 1, 'arijit.jpg', 'approved'),
('Sunburn Festival 2026',     'Asia''s biggest electronic music festival returns!',                              DATE_ADD(CURDATE(), INTERVAL 22 DAY), 'Vagator Beach, Goa',            'Goa',       3500.00, 1, 'sunburn.jpg','approved'),
('IPL 2026 - MI vs CSK',      'Watch the epic rivalry between Mumbai Indians and Chennai Super Kings.',          DATE_ADD(CURDATE(), INTERVAL 5 DAY),  'Wankhede Stadium, Mumbai',      'Mumbai',    1500.00, 1, 'ipl.jpg',   'approved'),
('Comedy Night with Kapil',   'A hilarious evening with India''s most loved comedian.',                         DATE_ADD(CURDATE(), INTERVAL 8 DAY),  'NCPA, Mumbai',                  'Mumbai',    1200.00, 1, 'comedy.jpg','approved'),
('Rock in India Festival',    'Biggest rock music festival featuring top national and international bands.',    DATE_ADD(CURDATE(), INTERVAL 42 DAY), 'Palace Grounds, Bangalore',     'Bangalore', 2000.00, 1, 'rock.jpg',  'approved');

-- Admin notification
INSERT IGNORE INTO admin_notifications (type, title, message)
VALUES ('info', 'System Ready', 'BookYourShow Clone database initialized successfully (v2).');

SELECT 'Database setup complete!' AS status;
