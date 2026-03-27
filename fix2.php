<?php
require_once 'config/config.php';

$queries = [
    "ALTER TABLE movies ADD COLUMN is_slider_ad TINYINT(1) DEFAULT 0",
    "ALTER TABLE operators ADD COLUMN IF NOT EXISTS status ENUM('active','suspended') DEFAULT 'active'",
    "ALTER TABLE theatres ADD COLUMN IF NOT EXISTS licence_number VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE theatres ADD COLUMN IF NOT EXISTS licence_status ENUM('pending','approved','rejected') DEFAULT 'pending'",
    "ALTER TABLE theatres ADD COLUMN IF NOT EXISTS operator_id INT DEFAULT NULL",
    "ALTER TABLE theatres ADD COLUMN IF NOT EXISTS map_link VARCHAR(500) DEFAULT NULL",
    "ALTER TABLE shows ADD COLUMN IF NOT EXISTS language VARCHAR(50) DEFAULT 'Hindi'",
    "ALTER TABLE events ADD COLUMN IF NOT EXISTS poster VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE events ADD COLUMN IF NOT EXISTS banner_image VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE events ADD COLUMN IF NOT EXISTS status ENUM('pending','approved','rejected') DEFAULT 'pending'",
    "ALTER TABLE events ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE events ADD COLUMN IF NOT EXISTS total_seats INT DEFAULT 500",
    "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS type VARCHAR(50) DEFAULT 'general'",
    "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS ref_id INT DEFAULT NULL",
    "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS is_read TINYINT DEFAULT 0",
    "ALTER TABLE shows ADD COLUMN IF NOT EXISTS status ENUM('active','cancelled') DEFAULT 'active'",
    "ALTER TABLE operators ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT ''",
    "ALTER TABLE theatres ADD COLUMN IF NOT EXISTS is_verified TINYINT DEFAULT 0",
    "ALTER TABLE theatres ADD COLUMN IF NOT EXISTS added_by INT DEFAULT NULL",
    "ALTER TABLE movies ADD COLUMN IF NOT EXISTS banner_image VARCHAR(255) DEFAULT ''",
    "ALTER TABLE movies ADD COLUMN IF NOT EXISTS status ENUM('active','inactive') DEFAULT 'active'"
];

foreach ($queries as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Success: " . $sql . "<br>";
    } else {
        echo "Error: " . $conn->error . " for query " . $sql . "<br>";
    }
}
echo "Done!";
?>
