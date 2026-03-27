<?php
// One-time migration: add status column to shows table
require_once 'config/config.php';

$sqls = [
    "ALTER TABLE shows ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER language",
    // Set old shows (no organizer_id = admin-added) to 'active'
    "UPDATE shows SET status='active' WHERE status='active' OR operator_id IS NULL OR operator_id=0",
];

echo '<pre style="font-family:monospace;padding:20px;">';
echo "=== Shows Status Migration ===\n\n";

// Check if column already exists
$check = $conn->query("SHOW COLUMNS FROM shows LIKE 'status'");
if ($check && $check->num_rows > 0) {
    echo "✅ Column 'status' already exists in shows table.\n";
} else {
    $r = $conn->query($sqls[0]);
    echo $r ? "✅ Added 'status' column to shows.\n" : "❌ Error: " . $conn->error . "\n";
}

// Make sure old admin-added shows are 'active'
$r2 = $conn->query("UPDATE shows SET status='active' WHERE operator_id IS NULL OR operator_id=0");
echo "✅ Set " . $conn->affected_rows . " admin-added show(s) to 'active'.\n";

// Double-check
$tot   = $conn->query("SELECT COUNT(*) c FROM shows")->fetch_assoc()['c'];
$act   = $conn->query("SELECT COUNT(*) c FROM shows WHERE status='active'")->fetch_assoc()['c'];
$pend  = $conn->query("SELECT COUNT(*) c FROM shows WHERE status='pending'")->fetch_assoc()['c'];

echo "\n📊 Shows summary:\n";
echo "  Total: $tot\n";
echo "  Active (visible to users): $act\n";
echo "  Pending (awaiting admin approval): $pend\n";
echo "\n✅ Done! You can delete this file now.\n";
echo '</pre>';
?>
