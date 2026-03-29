<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'BookYourShow');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");

// Base URL
define('BASE_URL', 'http://localhost/BookYourShow/');
define('SITE_NAME', 'BookYourShow');

// ==========================================
// 🔑 API CONFIGURATIONS
// ==========================================

// 1. Google OAuth 2.0 (For Login/Signup)
// Get these from: https://console.cloud.google.com/apis/credentials
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
define('GOOGLE_REDIRECT_URI', BASE_URL . 'google-callback.php');

// 2. SendGrid Email API (For Booking Tickets)
// Get this from: https://app.sendgrid.com/settings/api_keys
define('SENDGRID_API_KEY', 'YOUR_SENDGRID_API_KEY_HERE');
define('SENDGRID_FROM_EMAIL', 'your_registered_email@example.com'); // Must be verified in SendGrid
define('SENDGRID_FROM_NAME', 'BookYourShow Clone');

// 3. Twilio SMS API (For SMS Notifications)
// Get these from: https://console.twilio.com/
define('TWILIO_ACCOUNT_SID', 'YOUR_TWILIO_SID_HERE');
define('TWILIO_AUTH_TOKEN', 'YOUR_TWILIO_AUTH_TOKEN_HERE');
define('TWILIO_FROM_NUMBER', 'YOUR_TWILIO_PHONE_NUMBER'); // E.g., +1234567890

// Auto-sync database (Export to folder's .sql file so it is always latest)
require_once __DIR__ . '/auto_sync.php';

?>
