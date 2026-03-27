<?php
require_once 'config/config.php';

$queries = [
    "ALTER TABLE movies ADD COLUMN IF NOT EXISTS is_slider_ad TINYINT(1) DEFAULT 0",
    "ALTER TABLE theatres ADD COLUMN IF NOT EXISTS licence_status ENUM('pending','approved','rejected') DEFAULT 'approved'",
    "UPDATE theatres SET licence_status='approved' WHERE licence_status='pending' OR licence_status IS NULL",
    "UPDATE movies SET is_slider_ad=0"
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
