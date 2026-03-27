<?php
require_once 'config/config.php';

// Add payment_method and coupon_code to event_bookings table
$q1 = "ALTER TABLE event_bookings ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT 'Credit/Debit Card' AFTER total_price";
$q2 = "ALTER TABLE event_bookings ADD COLUMN IF NOT EXISTS coupon_code VARCHAR(50) DEFAULT NULL AFTER payment_method";

if ($conn->query($q1) && $conn->query($q2)) {
    echo "✅ Added payment_method and coupon_code columns to event_bookings table. Done!";
} else {
    echo "Error: " . $conn->error;
}
?>
