<?php
// Password reset + DB patch helper — run once via browser, then delete
require_once 'config/config.php';

echo "<pre>";

// Run ALTER TABLE statements (IF NOT EXISTS equivalent in older MySQL)
$alters = [
    // Operators
    "ALTER TABLE operators ADD COLUMN IF NOT EXISTS status ENUM('active','suspended') DEFAULT 'active'",
    // Theatres
    "ALTER TABLE theatres ADD COLUMN IF NOT EXISTS licence_number VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE theatres ADD COLUMN IF NOT EXISTS licence_status ENUM('pending','approved','rejected') DEFAULT 'pending'",
    "ALTER TABLE theatres ADD COLUMN IF NOT EXISTS operator_id INT DEFAULT NULL",
    "ALTER TABLE theatres ADD COLUMN IF NOT EXISTS map_link VARCHAR(500) DEFAULT NULL",
    // Shows
    "ALTER TABLE shows ADD COLUMN IF NOT EXISTS language VARCHAR(50) DEFAULT 'Hindi'",
    // Events
    "ALTER TABLE events ADD COLUMN IF NOT EXISTS poster VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE events ADD COLUMN IF NOT EXISTS banner_image VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE events ADD COLUMN IF NOT EXISTS status ENUM('pending','approved','rejected') DEFAULT 'pending'",
    "ALTER TABLE events ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE events ADD COLUMN IF NOT EXISTS total_seats INT DEFAULT 500",
    // Notifications
    "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS type VARCHAR(50) DEFAULT 'general'",
    "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS ref_id INT DEFAULT NULL",
    "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS is_read TINYINT DEFAULT 0",
];

foreach ($alters as $sql) {
    if ($conn->query($sql)) {
        echo "✅ " . substr($sql, 0, 70) . "...\n";
    } else {
        echo "⚠️ (already exists or error): " . $conn->error . "\n";
    }
}

// Data fixes
$fixes = [
    "UPDATE theatres SET licence_status='approved' WHERE operator_id IS NULL",
    "UPDATE shows SET language='Hindi' WHERE language IS NULL OR language=''",
    "UPDATE events SET status='approved' WHERE organizer_id=1 AND (status IS NULL OR status='')",
    "UPDATE shows SET show_date=DATE_ADD(CURDATE(), INTERVAL (show_id % 7) + 1 DAY) WHERE show_date < CURDATE()",
];

foreach ($fixes as $sql) {
    $conn->query($sql);
    echo "✅ Fix: " . substr($sql, 0, 80) . " [" . $conn->affected_rows . " rows]\n";
}

// Reset passwords using PHP hash
$adminHash    = password_hash('admin123',    PASSWORD_DEFAULT);
$operatorHash = password_hash('operator123', PASSWORD_DEFAULT);
$conn->query("UPDATE admins SET password='$adminHash' WHERE username='admin'");
echo "✅ Admin password reset to: admin123\n";
$conn->query("UPDATE operators SET password='$operatorHash' WHERE email='john@events.com'");
echo "✅ Operator password reset to: operator123\n";

echo "\n<strong>🎉 All done!</strong>\n";
echo "</pre>";
echo '<a href="admin-login.php">→ Admin Login</a> | <a href="operator-login.php">→ Operator Login</a>';
?>
