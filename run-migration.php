<?php
// run-migration.php  — Run ONCE to apply migration_v2
// Access via: http://localhost/bookmyshow-clone/run-migration.php
// DELETE after running!

require_once 'config/config.php';
$results = [];
$errors  = [];

function runSQL($conn, $sql, $label) {
    global $results, $errors;
    $sql = trim($sql);
    if (!$sql) return;
    if ($conn->query($sql)) {
        $results[] = "✅ $label";
    } else {
        // Silently skip "Duplicate column" errors (already run)
        if (strpos($conn->error, 'Duplicate column') !== false || strpos($conn->error, "already exists") !== false) {
            $results[] = "⏭️ $label (already exists, skipped)";
        } else {
            $errors[] = "❌ $label → " . $conn->error;
        }
    }
}

// ── events table ─────────────────────────────────────────────────────────
runSQL($conn, "ALTER TABLE events ADD COLUMN status ENUM('pending','approved','rejected') DEFAULT 'approved'", "events.status");
runSQL($conn, "ALTER TABLE events ADD COLUMN banner_image VARCHAR(255) DEFAULT '' AFTER event_image", "events.banner_image");
runSQL($conn, "ALTER TABLE events ADD COLUMN city VARCHAR(100) DEFAULT '' AFTER location", "events.city");
runSQL($conn, "UPDATE events SET status = 'approved' WHERE status IS NULL OR status = ''", "events: approve legacy");

// ── shows table ──────────────────────────────────────────────────────────
runSQL($conn, "ALTER TABLE shows ADD COLUMN language VARCHAR(50) DEFAULT 'Hindi' AFTER show_time", "shows.language");
runSQL($conn, "ALTER TABLE shows ADD COLUMN operator_id INT DEFAULT NULL AFTER language", "shows.operator_id");
runSQL($conn, "ALTER TABLE shows ADD COLUMN status ENUM('active','cancelled') DEFAULT 'active' AFTER operator_id", "shows.status");

// ── operators table ───────────────────────────────────────────────────────
runSQL($conn, "ALTER TABLE operators ADD COLUMN city VARCHAR(100) DEFAULT '' AFTER organization", "operators.city");
runSQL($conn, "ALTER TABLE operators ADD COLUMN status ENUM('active','suspended') DEFAULT 'active' AFTER city", "operators.status");

// ── theatres table ────────────────────────────────────────────────────────
runSQL($conn, "ALTER TABLE theatres ADD COLUMN map_link VARCHAR(500) DEFAULT '' AFTER location", "theatres.map_link");
runSQL($conn, "ALTER TABLE theatres ADD COLUMN is_verified TINYINT DEFAULT 0 AFTER map_link", "theatres.is_verified");
runSQL($conn, "ALTER TABLE theatres ADD COLUMN added_by INT DEFAULT NULL AFTER is_verified", "theatres.added_by");
runSQL($conn, "UPDATE theatres SET is_verified = 1 WHERE is_verified = 0", "theatres: mark existing as verified");

// ── movies table ──────────────────────────────────────────────────────────
runSQL($conn, "ALTER TABLE movies ADD COLUMN banner_image VARCHAR(255) DEFAULT '' AFTER poster", "movies.banner_image");
runSQL($conn, "ALTER TABLE movies ADD COLUMN status ENUM('active','inactive') DEFAULT 'active' AFTER rating", "movies.status");

// ── theatre_licences ─────────────────────────────────────────────────────
runSQL($conn, "CREATE TABLE IF NOT EXISTS theatre_licences (
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
)", "theatre_licences table");

// ── admin_notifications ───────────────────────────────────────────────────
runSQL($conn, "CREATE TABLE IF NOT EXISTS admin_notifications (
    notif_id     INT AUTO_INCREMENT PRIMARY KEY,
    type         VARCHAR(50) NOT NULL DEFAULT 'general',
    title        VARCHAR(200) NOT NULL,
    message      TEXT,
    reference_id INT DEFAULT NULL,
    is_read      TINYINT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)", "admin_notifications table");

// ── Update show dates to future ──────────────────────────────────────────
runSQL($conn, "UPDATE shows SET show_date = DATE_ADD(CURDATE(), INTERVAL (show_id % 14 + 1) DAY) WHERE show_date < CURDATE()", "shows: update past dates to future");

// ── Sample notification ───────────────────────────────────────────────────
runSQL($conn, "INSERT IGNORE INTO admin_notifications (type, title, message) VALUES ('info','System Upgraded','BookYourShow v2 with theatre licensing is active.')", "sample notification");

?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Migration Runner</title>
<style>body{font-family:monospace;background:#0f0f1a;color:#e2e8f0;padding:40px;} .ok{color:#22c55e;} .skip{color:#64748b;} .err{color:#ef4444;} h1{color:#f84464;} pre{background:#1e1e2e;padding:16px;border-radius:8px;line-height:1.8;}</style>
</head><body>
<h1>🔧 Migration v2 — Results</h1>
<pre>
<?php foreach($results as $r) echo '<span class="'.($r[0]==='✅'?'ok':'skip').'">'.$r.'</span>'."\n"; ?>
<?php foreach($errors  as $e) echo '<span class="err">'.$e.'</span>'."\n"; ?>
</pre>
<p><?php echo count($errors)===0 ? '<span class="ok">✅ All migrations applied successfully!</span>' : '<span class="err">⚠️ Some errors occurred above.</span>'; ?></p>
<p>⚠️ <strong style="color:#f59e0b;">Delete this file after use!</strong></p>
<hr><a href="events.php" style="color:#f84464;">→ Test Events Page</a> &nbsp;|&nbsp;
<a href="operator-register.php" style="color:#f84464;">→ Test Organizer Register</a> &nbsp;|&nbsp;
<a href="admin-dashboard.php" style="color:#f84464;">→ Admin Dashboard</a>
</body></html>
