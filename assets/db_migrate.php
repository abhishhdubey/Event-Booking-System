<?php
/**
 * DB Migration Script - Run ONCE then delete
 * Visit: http://localhost/bookmyshow-clone/assets/db_migrate.php
 */
require_once '../config/config.php';

echo "<pre style='font-family:monospace;padding:20px;background:#111;color:#0f0;'>";
echo "=== BookYourShow DB Migration ===\n\n";

$migrations = [
    // Movies: add poster (vertical) and banner_image (horizontal)
    "ALTER TABLE movies ADD COLUMN IF NOT EXISTS poster VARCHAR(255) DEFAULT NULL" => "movies: poster column",
    "ALTER TABLE movies ADD COLUMN IF NOT EXISTS banner_image VARCHAR(255) DEFAULT NULL" => "movies: banner_image column",

    // Events: poster, banner, status
    "ALTER TABLE events ADD COLUMN IF NOT EXISTS poster VARCHAR(255) DEFAULT NULL" => "events: poster column",
    "ALTER TABLE events ADD COLUMN IF NOT EXISTS banner_image VARCHAR(255) DEFAULT NULL" => "events: banner_image column",
    "ALTER TABLE events ADD COLUMN IF NOT EXISTS status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'" => "events: status column",
    // Approve all existing events so they aren't hidden
    "UPDATE events SET status = 'approved' WHERE status = 'pending'" => "events: approve all existing events",
    // Operators: total_seats optional
    "ALTER TABLE events ADD COLUMN IF NOT EXISTS total_seats INT DEFAULT 500" => "events: total_seats column",
];

foreach ($migrations as $sql => $label) {
    if ($conn->query($sql)) {
        echo "✅ {$label}\n";
    } else {
        // Ignore duplicate column errors (older MySQL without IF NOT EXISTS)
        if (strpos($conn->error, 'Duplicate column') !== false) {
            echo "⏭️  {$label} (already exists)\n";
        } else {
            echo "❌ {$label}: {$conn->error}\n";
        }
    }
}

echo "\n✅ Migration complete! You can now delete this file for security.\n";
echo "</pre>";
?>
