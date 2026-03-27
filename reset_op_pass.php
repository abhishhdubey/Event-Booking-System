<?php
require_once 'config/config.php';
session_start();

// Reset password to "lakshy123" for operator_id 2
$newPass = password_hash('123456', PASSWORD_DEFAULT);
$conn->query("UPDATE operators SET password='$newPass' WHERE operator_id=2");

echo "<div style='font-family:sans-serif;padding:20px;background:#1a1a2e;color:#e0e0e0;min-height:100vh;'>";
echo "<h2 style='color:#f84464;'>✅ Password Reset Done</h2>";
echo "<p>Operator <strong>Lakshy Gupta</strong> (lakshygupta@gmail.com) ka password reset ho gaya!</p>";
echo "<p style='background:#0d0d1a;padding:12px;border-radius:8px;'>New Password: <strong style='color:#f84464;font-size:1.2em;'>lakshy123</strong></p>";
echo "<br><a href='/bookmyshow-clone/operator-login.php' style='background:#f84464;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:bold;'>→ Go to Login</a>";
echo "</div>";
?>
