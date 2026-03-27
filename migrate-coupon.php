<?php
require_once 'config/config.php';
$results = [];

// Add coupon_code column to bookings if it doesn't exist
$check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'coupon_code'");
if ($check->num_rows === 0) {
    $r = $conn->query("ALTER TABLE bookings ADD COLUMN coupon_code VARCHAR(50) DEFAULT NULL");
    $results[] = $r ? '✅ Added coupon_code column to bookings table.' : '❌ Error: ' . $conn->error;
} else {
    $results[] = '✅ coupon_code column already exists in bookings table.';
}

foreach ($results as $res) {
    echo $res . "<br>\n";
}
echo "<br><strong>Done!</strong> You can delete this file now.";
?>
