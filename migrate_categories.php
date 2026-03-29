<?php
require 'config/config.php';

// Turn off exception throwing for mysqli temporarily or catch it
mysqli_report(MYSQLI_REPORT_OFF);

// Try adding column category
$result = $conn->query("ALTER TABLE events ADD COLUMN category VARCHAR(50) DEFAULT 'Other'");
if ($result) {
    echo "Column 'category' added to 'events' table.\n";
} else {
    echo "Column might already exist or error: " . $conn->error . "\n";
}

// Update existing dummy events
$updates = [
    "IPL 2026 - MI vs CSK" => "Sports",
    "Comedy Night with Kapil" => "Comedy",
    "Arijit Singh Live Concert" => "Music",
    "Sunburn Festival 2026" => "Festivals & Fairs",
    "Ardor - FCA" => "College Fests",
    "Rock in India Festival" => "Music"
];

$updatedCount = 0;
foreach ($updates as $name => $cat) {
    $stmt = $conn->prepare("UPDATE events SET category = ? WHERE event_name = ?");
    $stmt->bind_param("ss", $cat, $name);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $updatedCount++;
    }
}
echo "Migration done. Updated $updatedCount events.\n";
?>
