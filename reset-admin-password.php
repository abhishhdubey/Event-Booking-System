<?php
// reset-admin-password.php - Run ONCE then delete!
require_once 'config/config.php';
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$ok = $conn->query("UPDATE admins SET password='$hash' WHERE username='admin'");
if (!$ok || $conn->affected_rows == 0) {
    $conn->query("INSERT INTO admins (name, username, email, password) VALUES ('Admin','admin','admin@bookmyshow.com','$hash')");
}
echo '<html><head><style>body{background:#0f0f1a;color:#e2e8f0;font-family:monospace;padding:40px;}</style></head><body>';
echo '<h2 style="color:#f84464;">Admin Password Reset</h2>';
echo '<p style="color:#22c55e;">✅ Admin password set to: <strong>admin123</strong></p>';
$a = $conn->query("SELECT admin_id, username, name FROM admins LIMIT 3")->fetch_assoc();
echo '<p>Admin record found: ' . htmlspecialchars(json_encode($a)) . '</p>';
echo '<a href="admin-login.php" style="background:#f84464;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;">→ Go to Admin Login</a>';
echo '</body></html>';
?>
