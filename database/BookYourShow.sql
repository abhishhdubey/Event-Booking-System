-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: BookYourShow
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_notifications`
--

DROP TABLE IF EXISTS `admin_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_notifications` (
  `notif_id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL DEFAULT 'general',
  `title` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notif_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_notifications`
--

LOCK TABLES `admin_notifications` WRITE;
/*!40000 ALTER TABLE `admin_notifications` DISABLE KEYS */;
INSERT INTO `admin_notifications` VALUES (1,'info','System Upgraded','BookYourShow Clone has been upgraded to v2 with theatre licensing and language-tagged showtimes.',NULL,0,'2026-03-26 19:05:18'),(2,'licence','????️ Licence Request: Cinepolis Viviana','Organizer Lakshy Gupta submitted licence #123456789ABC for Cinepolis Viviana to show \"Jawan\".',1,1,'2026-03-26 19:40:32'),(3,'show','✅ Auto-Live: 0 Shows','Licence for Cinepolis Viviana by Lakshy Gupta approved. 0 pending shows automatically went live.',1,0,'2026-03-26 19:40:41'),(4,'licence','????️ Licence Request: PVR Cinemas Treasure Island Indore','Organizer Lakshy Gupta submitted licence #PVR-TI-INDORE for PVR Cinemas Treasure Island Indore to show \"Dhurandhar: The Revenge\".',2,1,'2026-03-28 18:55:08'),(5,'show','✅ Auto-Live: 9 Shows','Licence for PVR Cinemas Treasure Island Indore by Lakshy Gupta approved. 9 pending shows automatically went live.',2,0,'2026-03-28 18:55:46'),(6,'licence','????️ Licence Request: PVR Saket','Organizer Lakshy Gupta submitted licence #PVR-SAKET-DELHI for PVR Saket to show \"Dhurandhar: The Revenge\".',3,1,'2026-03-28 19:02:50'),(7,'show','✅ Auto-Live: 4 Shows','Licence for PVR Saket by Lakshy Gupta approved. 4 pending shows automatically went live.',3,0,'2026-03-28 19:04:19');
/*!40000 ALTER TABLE `admin_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `show_id` int(11) DEFAULT NULL,
  `seats` varchar(100) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `booking_status` varchar(50) DEFAULT 'confirmed',
  `coupon_code` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`booking_id`),
  KEY `user_id` (`user_id`),
  KEY `show_id` (`show_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`show_id`) REFERENCES `shows` (`show_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (1,2,9,'A1,A2',440.00,'2026-03-28 17:31:37','confirmed',NULL),(2,2,9,'A1,A2',220.00,'2026-03-28 17:32:02','confirmed','FIRST50'),(3,1,1,'A3,A4,A5,A2,A1',1350.00,'2026-03-28 17:32:57','confirmed',NULL),(4,1,19,'D1,D2,D3,D4,D5',1527.50,'2026-03-29 11:48:58','confirmed','FIRST50');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cities`
--

DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cities` (
  `city_id` int(11) NOT NULL AUTO_INCREMENT,
  `city_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`city_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cities`
--

LOCK TABLES `cities` WRITE;
/*!40000 ALTER TABLE `cities` DISABLE KEYS */;
INSERT INTO `cities` VALUES (1,'Mumbai'),(2,'Delhi'),(3,'Bangalore'),(4,'Hyderabad'),(5,'Chennai'),(6,'Kolkata'),(7,'Pune'),(8,'Ahmedabad');
/*!40000 ALTER TABLE `cities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contacts` (
  `contact_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contacts`
--

LOCK TABLES `contacts` WRITE;
/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupons` (
  `coupon_id` int(11) NOT NULL AUTO_INCREMENT,
  `coupon_code` varchar(50) DEFAULT NULL,
  `discount_percent` int(11) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  PRIMARY KEY (`coupon_id`),
  UNIQUE KEY `coupon_code` (`coupon_code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES (1,'FIRST50',50,'2026-12-31'),(2,'SAVE20',20,'2026-06-30'),(3,'WELCOME10',10,'2026-12-31'),(4,'MOVIE30',30,'2026-09-30');
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_bookings`
--

DROP TABLE IF EXISTS `event_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_bookings` (
  `event_booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `tickets` int(11) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`event_booking_id`),
  KEY `user_id` (`user_id`),
  KEY `event_id` (`event_id`),
  CONSTRAINT `event_bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `event_bookings_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_bookings`
--

LOCK TABLES `event_bookings` WRITE;
/*!40000 ALTER TABLE `event_bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_name` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT '',
  `ticket_price` decimal(10,2) DEFAULT NULL,
  `total_seats` int(11) DEFAULT 500,
  `organizer_id` int(11) DEFAULT NULL,
  `event_image` varchar(255) DEFAULT NULL,
  `banner_image` varchar(255) DEFAULT '',
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `category` varchar(50) DEFAULT 'Other',
  PRIMARY KEY (`event_id`),
  KEY `organizer_id` (`organizer_id`),
  CONSTRAINT `events_ibfk_1` FOREIGN KEY (`organizer_id`) REFERENCES `operators` (`operator_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
INSERT INTO `events` VALUES (1,'Arijit Singh Live Concert','Experience the magic of Arijit Singh live on stage with a 3-hour performance.','2026-04-15','MMRDA Grounds, BKC, Mumbai','Mumbai',2500.00,500,2,'poster_1774645116_6594.jpeg','banner_1774645116_5588.jpeg','approved','Music'),(2,'Sunburn Festival 2026','Asia\'s biggest electronic music festival returns to Goa!','2026-04-20','Vagator Beach, Goa','',3500.00,500,2,'poster_1774645843_6034.jpeg','banner_1774645843_4514.jpg','approved','Festivals & Fairs'),(3,'IPL 2026 - MI vs CSK','Watch the epic rivalry between Mumbai Indians and Chennai Super Kings.','2026-03-28','Wankhede Stadium, Mumbai','Mumbai',1500.00,500,2,'poster_1774645316_4452.jpeg','banner_1774645316_8703.webp','approved','Sports'),(4,'Comedy Night with Kapil','A hilarious evening with India\'s most loved comedian.','2026-04-05','NCPA, Mumbai','Mumbai',1200.00,500,2,'poster_1774645232_7820.jpg','banner_1774645232_1491.jpg','approved','Comedy'),(5,'Rock in India Festival','Biggest rock music festival featuring top national and international bands.','2026-05-10','Palace Grounds, Bangalore','Mumbai',2000.00,500,2,'poster_1774645709_8276.jpeg','banner_1774645489_9584.jpg','approved','Music'),(6,'Ardor - FCA','The Faculty of Computer Applications (FCA) is proud to organize Ardor ????, the Annual Fest celebrating creativity, innovation, and talent. The event will be held at the Central Auditorium, AITR ????️, bringing together students to showcase their skills and enthusiasm.\r\n\r\n???? Date: 9th – 10th May\r\n⏰ Time: 10:30 AM to 2:30 PM\r\n\r\n✨ Ardor promises an engaging and vibrant experience filled with exciting activities, performances, and opportunities to connect, learn, and celebrate together.','2026-05-09','Central Auditorium - AITR','Mumbai',199.00,500,2,'poster_1774644342_5871.png','banner_1774644342_6947.png','approved','College Fests');
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `food_orders`
--

DROP TABLE IF EXISTS `food_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `food_orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) DEFAULT NULL,
  `food_items` varchar(255) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `booking_id` (`booking_id`),
  CONSTRAINT `food_orders_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `food_orders`
--

LOCK TABLES `food_orders` WRITE;
/*!40000 ALTER TABLE `food_orders` DISABLE KEYS */;
INSERT INTO `food_orders` VALUES (1,4,'Combo: Popcorn + Pepsi x2',460.00);
/*!40000 ALTER TABLE `food_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `foods`
--

DROP TABLE IF EXISTS `foods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `foods` (
  `food_id` int(11) NOT NULL AUTO_INCREMENT,
  `food_name` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`food_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `foods`
--

LOCK TABLES `foods` WRITE;
/*!40000 ALTER TABLE `foods` DISABLE KEYS */;
INSERT INTO `foods` VALUES (1,'Regular Popcorn',150.00,'popcorn.jpg'),(2,'Nachos with Cheese',199.00,'nachos.jpg'),(3,'Pepsi (500ml)',100.00,'pepsi.jpg'),(4,'Combo: Popcorn + Pepsi',230.00,'combo.jpg'),(5,'Burger',180.00,'burger.jpg'),(6,'Hot Dog',120.00,'hotdog.jpg'),(7,'Coffee',90.00,'coffee.jpg'),(8,'Mineral Water',50.00,'water.jpg');
/*!40000 ALTER TABLE `foods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movies`
--

DROP TABLE IF EXISTS `movies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movies` (
  `movie_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `duration` varchar(20) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `banner_image` varchar(255) DEFAULT '',
  `trailer_url` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `is_slider_ad` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`movie_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movies`
--

LOCK TABLES `movies` WRITE;
/*!40000 ALTER TABLE `movies` DISABLE KEYS */;
INSERT INTO `movies` VALUES (1,'Avengers: Endgame','The Avengers must undo the actions of Thanos in a desperate attempt to restore order to the universe.','Action, Adventure, Drama','181 min','English','2019-04-26','poster_1774640338_2560.jpg','banner_1774640338_7713.jpg','https://www.youtube.com/embed/TcMBFSGVi1c',8.4,'active',1),(2,'Spider-Man: No Way Home','Spider-Man seeks the help of Doctor Strange to forget his exposed identity as Peter Parker.','Action, Adventure, Fantasy','148 min','English','2021-12-17','poster_1774640802_1756.jpeg','banner_1774640802_5452.jpg','https://www.youtube.com/embed/JfVOs4VSpmA',8.2,'active',0),(3,'KGF Chapter 2','Rocky continues his rule in the Kolar Gold Fields, but must face new threats.','Action, Drama','168 min','Kannada','2022-04-14','poster_1774640678_2074.jpg','banner_1774640678_5943.jpg','https://www.youtube.com/embed/iCDXBxYxjOE',8.3,'active',1),(4,'RRR','A fictional story about two Indian revolutionaries, Alluri Sitarama Raju and Komaram Bheem.','Action, Drama','187 min','Telugu','2022-03-25','poster_1774640652_6653.jpg','banner_1774640703_1318.jpg','https://www.youtube.com/embed/f_vbAtFSEc0',7.9,'active',0),(5,'Pathaan','An exiled spy is brought back to thwart a plot against India by a vengeful former agent.','Action, Thriller','146 min','Hindi','2023-01-25','poster_1774640629_3602.jpg','banner_1774640629_9225.jpg','https://www.youtube.com/embed/vqu4z34wENw',5.9,'active',0),(6,'The Dark Knight','Batman faces the Joker, a criminal mastermind who plunges Gotham into anarchy.','Action, Crime, Drama','152 min','English','2008-07-18','poster_1774640615_2404.jpg','banner_1774640615_2400.jpg','https://www.youtube.com/embed/EXeTwQWrcwY',9.0,'active',0),(7,'Jawan','A man is driven by a personal cause to rectify the wrongs in society.','Action, Thriller','169 min','Hindi','2023-09-07','poster_1774640602_8839.jpg','banner_1774640602_6953.jpg','https://www.youtube.com/embed/pNAWy0mDmXE',6.5,'active',0),(8,'Oppenheimer','The story of American scientist J. Robert Oppenheimer and his role in the development of the atomic bomb.','Biography, Drama, History','180 min','English','2023-07-21','poster_1774640572_5870.jpg','banner_1774640572_4436.webp','https://www.youtube.com/embed/uYPbbksJxIg',8.9,'active',0),(9,'Dhurandhar: The Revenge','Dhurandhar: The Revenge (2026) is a gritty, 4-hour Hindi spy-thriller directed by Aditya Dhar, following undercover Indian agent Hamza (Ranveer Singh) as he orchestrates a brutal vendetta against Pakistani handlers, led by Major Iqbal (Arjun Rampal). The film, a sequel to Dhurandhar, explores Jaskirat Singh Rangi\'s evolution into Hamza, featuring high-octane violence and intense action in Pakistan\'s Lyari underworld.','Action, Thriller','3h 49m','Hindi','2026-03-19','poster_1774640544_3956.jpg','banner_1774782510_2937.jpg','https://www.youtube.com/watch?v=NHk7scrb_9I',9.8,'active',1);
/*!40000 ALTER TABLE `movies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'New Movies Added','Check out the latest blockbusters now showing in theatres near you!','2026-03-26 19:05:10'),(2,'Offer Alert','Use coupon FIRST50 for 50% off on your first booking!','2026-03-26 19:05:10'),(3,'Weekend Special','Book 2 tickets and get free popcorn this weekend!','2026-03-26 19:05:10');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `operators`
--

DROP TABLE IF EXISTS `operators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `operators` (
  `operator_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT '',
  `password` varchar(255) DEFAULT NULL,
  `organization` varchar(150) DEFAULT NULL,
  `city` varchar(100) DEFAULT '',
  `status` enum('active','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`operator_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `operators`
--

LOCK TABLES `operators` WRITE;
/*!40000 ALTER TABLE `operators` DISABLE KEYS */;
INSERT INTO `operators` VALUES (2,'Lakshy Gupta','lakshygupta@gmail.com','9090909090','$2y$10$GqgXjEq/j7NaFCqRbqqVL.viiBhUnI39K4FNKsQybBctawPMBxo22','DEMO','Delhi','active','2026-03-26 19:10:28'),(3,'Test Organizer','testuser_op@example.com','1234567890','$2y$10$NUH1nD5uB3m3mbTSlnlXUuIaHxE0VsN1fKKKJbxSbbHzA9PepWdw6','Test Org','Agra','active','2026-03-28 18:10:59');
/*!40000 ALTER TABLE `operators` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'success',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`payment_id`),
  KEY `booking_id` (`booking_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,'UPI',440.00,'success','2026-03-28 17:31:37'),(2,2,'UPI',220.00,'success','2026-03-28 17:32:02'),(3,3,'UPI',1350.00,'success','2026-03-28 17:32:57'),(4,4,'UPI',1527.50,'success','2026-03-29 11:48:58');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `movie_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `review_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`review_id`),
  KEY `user_id` (`user_id`),
  KEY `movie_id` (`movie_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`movie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seats`
--

DROP TABLE IF EXISTS `seats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seats` (
  `seat_id` int(11) NOT NULL AUTO_INCREMENT,
  `show_id` int(11) DEFAULT NULL,
  `seat_number` varchar(10) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'available',
  PRIMARY KEY (`seat_id`),
  KEY `show_id` (`show_id`),
  CONSTRAINT `seats_ibfk_1` FOREIGN KEY (`show_id`) REFERENCES `shows` (`show_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seats`
--

LOCK TABLES `seats` WRITE;
/*!40000 ALTER TABLE `seats` DISABLE KEYS */;
/*!40000 ALTER TABLE `seats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shows`
--

DROP TABLE IF EXISTS `shows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shows` (
  `show_id` int(11) NOT NULL AUTO_INCREMENT,
  `movie_id` int(11) DEFAULT NULL,
  `theatre_id` int(11) DEFAULT NULL,
  `show_date` date DEFAULT NULL,
  `show_time` time DEFAULT NULL,
  `language` varchar(50) DEFAULT 'Hindi',
  `operator_id` int(11) DEFAULT NULL,
  `status` enum('pending','active','cancelled') DEFAULT 'active',
  `price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`show_id`),
  KEY `movie_id` (`movie_id`),
  KEY `theatre_id` (`theatre_id`),
  CONSTRAINT `shows_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`movie_id`),
  CONSTRAINT `shows_ibfk_2` FOREIGN KEY (`theatre_id`) REFERENCES `theatres` (`theatre_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shows`
--

LOCK TABLES `shows` WRITE;
/*!40000 ALTER TABLE `shows` DISABLE KEYS */;
INSERT INTO `shows` VALUES (1,1,1,'2026-03-28','10:00:00','Hindi',NULL,'active',250.00),(2,1,1,'2026-03-29','14:00:00','Hindi',NULL,'active',300.00),(3,1,1,'2026-03-30','18:00:00','Hindi',NULL,'active',350.00),(4,2,1,'2026-03-31','11:00:00','Hindi',NULL,'active',280.00),(5,2,2,'2026-04-01','15:00:00','Hindi',NULL,'active',300.00),(6,3,3,'2026-04-02','10:30:00','Hindi',NULL,'active',220.00),(7,4,4,'2026-04-03','13:00:00','Hindi',NULL,'active',260.00),(8,5,5,'2026-04-04','16:00:00','Hindi',NULL,'active',240.00),(9,6,6,'2026-04-05','10:00:00','Hindi',NULL,'active',200.00),(10,7,7,'2026-04-06','14:00:00','Hindi',NULL,'active',270.00),(11,8,8,'2026-04-07','18:00:00','Hindi',NULL,'active',320.00),(12,1,2,'2026-04-08','12:00:00','Hindi',NULL,'active',290.00),(13,2,3,'2026-04-09','16:30:00','Hindi',NULL,'active',260.00),(14,3,4,'2026-03-27','09:00:00','Hindi',NULL,'active',210.00),(15,4,5,'2026-03-28','13:00:00','Hindi',NULL,'active',240.00),(16,5,6,'2026-03-29','17:00:00','Hindi',NULL,'active',230.00),(17,7,4,'2026-03-31','07:00:00','Hindi',2,'active',599.00),(18,9,9,'2026-04-04','03:00:00','Hindi',2,'active',149.00),(19,9,9,'2026-04-04','05:00:00','Hindi',2,'active',499.00),(20,9,9,'2026-04-04','07:00:00','Hindi',2,'active',599.00),(21,9,9,'2026-04-05','04:00:00','Hindi',2,'active',300.00),(22,9,9,'2026-04-05','06:00:00','Hindi',2,'active',399.00),(23,9,9,'2026-04-05','08:00:00','Hindi',2,'active',499.00),(24,9,9,'2026-04-06','04:00:00','Hindi',2,'active',399.00),(25,9,9,'2026-04-06','06:00:00','Hindi',2,'active',499.00),(26,9,9,'2026-04-06','07:00:00','Hindi',2,'active',600.00),(27,9,3,'2026-04-04','04:00:00','Hindi',2,'active',350.00),(28,9,3,'2026-04-04','05:00:00','Hindi',2,'active',499.00),(29,9,3,'2026-04-05','07:00:00','Hindi',2,'active',359.00),(30,9,3,'2026-04-05','09:00:00','Hindi',2,'active',499.00);
/*!40000 ALTER TABLE `shows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `theatre_licences`
--

DROP TABLE IF EXISTS `theatre_licences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `theatre_licences` (
  `licence_id` int(11) NOT NULL AUTO_INCREMENT,
  `theatre_id` int(11) NOT NULL,
  `operator_id` int(11) NOT NULL,
  `licence_number` varchar(150) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `rejection_note` varchar(500) DEFAULT '',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`licence_id`),
  UNIQUE KEY `uq_theatre_operator` (`theatre_id`,`operator_id`),
  KEY `operator_id` (`operator_id`),
  CONSTRAINT `theatre_licences_ibfk_1` FOREIGN KEY (`theatre_id`) REFERENCES `theatres` (`theatre_id`) ON DELETE CASCADE,
  CONSTRAINT `theatre_licences_ibfk_2` FOREIGN KEY (`operator_id`) REFERENCES `operators` (`operator_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `theatre_licences`
--

LOCK TABLES `theatre_licences` WRITE;
/*!40000 ALTER TABLE `theatre_licences` DISABLE KEYS */;
INSERT INTO `theatre_licences` VALUES (1,4,2,'123456789ABC','approved','','2026-03-26 19:40:32','2026-03-26 19:40:41'),(2,9,2,'PVR-TI-INDORE','approved','','2026-03-28 18:55:08','2026-03-28 18:55:46'),(3,3,2,'PVR-SAKET-DELHI','approved','','2026-03-28 19:02:50','2026-03-28 19:04:19');
/*!40000 ALTER TABLE `theatre_licences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `theatres`
--

DROP TABLE IF EXISTS `theatres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `theatres` (
  `theatre_id` int(11) NOT NULL AUTO_INCREMENT,
  `theatre_name` varchar(150) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `map_link` varchar(500) DEFAULT '',
  `is_verified` tinyint(4) DEFAULT 0,
  `added_by` int(11) DEFAULT NULL,
  `licence_status` enum('pending','approved','rejected') DEFAULT 'approved',
  PRIMARY KEY (`theatre_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `theatres`
--

LOCK TABLES `theatres` WRITE;
/*!40000 ALTER TABLE `theatres` DISABLE KEYS */;
INSERT INTO `theatres` VALUES (1,'PVR Phoenix','Mumbai','Phoenix Palladium, Lower Parel, Mumbai','',1,NULL,'approved'),(2,'INOX Nariman Point','Mumbai','Nariman Point, Mumbai','',1,NULL,'approved'),(3,'PVR Saket','Delhi','Select Citywalk, Saket, Delhi','',1,NULL,'approved'),(4,'Cinepolis Viviana','Mumbai','Viviana Mall, Thane, Mumbai','',1,NULL,'approved'),(5,'PVR Forum','Bangalore','Forum Mall, Koramangala, Bangalore','',1,NULL,'approved'),(6,'INOX GVK One','Hyderabad','GVK One Mall, Banjara Hills, Hyderabad','',1,NULL,'approved'),(7,'PVR Sathyam','Chennai','Sathyam Cinemas, Chennai','',1,NULL,'approved'),(8,'INOX South City','Kolkata','South City Mall, Kolkata','',1,NULL,'approved'),(9,'PVR Cinemas Treasure Island Indore','Indore','4th Floor, Treasure Island Mall, 11, Mahatma Gandhi Road, South Tukoganj, Indore, Madhya Pradesh 452001','https://share.google/uCToCxkJo9LuH3okJ',1,2,'approved');
/*!40000 ALTER TABLE `theatres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `city` varchar(100) DEFAULT '',
  `password` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'default.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Abhishek Dubey','abhishekdubey0861@gmail.com','8989898989','','$2y$10$51vN6un4yEht2SsXwMQRZOe/YDzOw2e1YZZD.mY1pDfVE70Lkjv7u','default.png','2026-03-26 19:09:18'),(2,'Test User','testuser@example.com','1234567890','','$2y$10$bUJowTmrxvgMWqhqxE5TPudjFZvhQHdUN832/Rtwvr1bju/AktNtm','default.png','2026-03-28 17:29:24'),(3,'Prince Prajapat','prince@gmail.com','0000000000','Indore','$2y$10$mXavySeodIY.2cOGLcJSC.8Hux9ZcQMBhjCMIlip8NZ2j093moq3.','default.png','2026-03-29 17:10:40');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `movie_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`wishlist_id`),
  KEY `user_id` (`user_id`),
  KEY `movie_id` (`movie_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`movie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-30 22:57:37
