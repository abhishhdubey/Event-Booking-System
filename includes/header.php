<?php
// includes/header.php
if (!isset($pageTitle)) $pageTitle = 'BookYourShow - Book Movie & Event Tickets';

// ---- City Selection via session ----
if (isset($_GET['set_city'])) {
    $_SESSION['selected_city'] = $conn->real_escape_string(trim($_GET['set_city']));
    // Redirect back to current page without the query param
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redirect);
    exit;
}
$selectedCity = $_SESSION['selected_city'] ?? '';

// ---- User Initials ----
$userInitials = '';
if (isset($_SESSION['user_name']) && $_SESSION['user_name']) {
    $nameParts = preg_split('/\s+/', trim($_SESSION['user_name']));
    if (count($nameParts) >= 2) {
        $userInitials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
    } else {
        $userInitials = strtoupper(substr($nameParts[0], 0, 1));
    }
}

$cities = ['Mumbai','Delhi','Bangalore','Hyderabad','Chennai','Kolkata','Pune','Jaipur'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="BookYourShow Clone - Book movie tickets, event tickets online. Easy, fast and secure booking.">
<title><?php echo htmlspecialchars($pageTitle); ?></title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css?v=3">
<link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>images/favicon.ico">
</head>
<body>

<!-- Trailer Modal -->
<div class="modal" id="trailerModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeTrailer()">✕</button>
        <iframe class="trailer-frame" id="trailerFrame" allowfullscreen></iframe>
    </div>
</div>

<!-- Mobile Nav Overlay -->
<nav class="mobile-nav" id="mobileNav" style="display:none;position:fixed;top:70px;left:0;right:0;z-index:999;background:var(--bg-card);border-bottom:1px solid var(--border);padding:20px;">
    <a href="<?php echo BASE_URL; ?>movies.php" class="nav-link" style="display:block;padding:12px 0;border-bottom:1px solid var(--border);">🎬 Movies</a>
    <a href="<?php echo BASE_URL; ?>events.php" class="nav-link" style="display:block;padding:12px 0;border-bottom:1px solid var(--border);">🎪 Events</a>
    <a href="<?php echo BASE_URL; ?>search.php" class="nav-link" style="display:block;padding:12px 0;border-bottom:1px solid var(--border);">🔍 Search</a>
    <?php if (isset($_SESSION['user_id'])): ?>
    <a href="<?php echo BASE_URL; ?>profile.php" class="nav-link" style="display:block;padding:12px 0;border-bottom:1px solid var(--border);">👤 Profile</a>
    <a href="<?php echo BASE_URL; ?>booking-history.php" class="nav-link" style="display:block;padding:12px 0;border-bottom:1px solid var(--border);">🎟️ My Bookings</a>
    <a href="<?php echo BASE_URL; ?>logout.php" class="nav-link" style="display:block;padding:12px 0;">🚪 Logout</a>
    <?php elseif (isset($_SESSION['operator_id'])): ?>
    <a href="<?php echo BASE_URL; ?>operator-dashboard.php" class="nav-link" style="display:block;padding:12px 0;border-bottom:1px solid var(--border);">⚙️ Dashboard</a>
    <a href="<?php echo BASE_URL; ?>logout.php" class="nav-link" style="display:block;padding:12px 0;">🚪 Logout</a>
    <?php elseif (isset($_SESSION['admin_id'])): ?>
    <a href="<?php echo BASE_URL; ?>admin-dashboard.php" class="nav-link" style="display:block;padding:12px 0;border-bottom:1px solid var(--border);">⚙️ Admin Panel</a>
    <a href="<?php echo BASE_URL; ?>logout.php" class="nav-link" style="display:block;padding:12px 0;">🚪 Logout</a>
    <?php else: ?>
    <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-primary btn-sm" style="display:block;text-align:center;margin-top:10px;">Login</a>
    <?php endif; ?>
</nav>

<!-- Navbar -->
<header class="navbar">
    <div class="navbar-container">
        <a href="<?php echo BASE_URL; ?>" class="navbar-brand">
            <span class="brand-logo">BookYourShow</span>
        </a>

        <nav class="navbar-nav">
            <a href="<?php echo BASE_URL; ?>" class="nav-link <?php echo (basename($_SERVER['PHP_SELF'])=='index.php'?'active':''); ?>">Home</a>
            <a href="<?php echo BASE_URL; ?>movies.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF'])=='movies.php'?'active':''); ?>">Movies</a>
            <a href="<?php echo BASE_URL; ?>events.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF'])=='events.php'?'active':''); ?>">Events</a>
            <a href="<?php echo BASE_URL; ?>search.php" class="nav-link">Search</a>
            <a href="<?php echo BASE_URL; ?>about.php" class="nav-link">About</a>
            <a href="<?php echo BASE_URL; ?>contact.php" class="nav-link">Contact</a>
        </nav>

        <div class="navbar-actions">
            <!-- City Selector (submit redirects to same page with session set) -->
            <form method="GET" action="" style="display:inline;" id="cityForm">
                <select class="nav-city-select" id="citySelect" name="set_city" onchange="document.getElementById('cityForm').submit()">
                    <option value="">📍 All Cities</option>
                    <?php foreach ($cities as $c): ?>
                    <option value="<?php echo $c; ?>" <?php echo ($selectedCity === $c) ? 'selected' : ''; ?>><?php echo $c; ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo BASE_URL; ?>notifications.php" class="nav-icon-btn" title="Notifications">🔔</a>
                <a href="<?php echo BASE_URL; ?>wishlist.php" class="nav-icon-btn" title="Wishlist">❤️</a>
                <!-- User Avatar with Initials -->
                <a href="<?php echo BASE_URL; ?>profile.php" class="nav-avatar-initials" title="My Profile">
                    <?php echo $userInitials ?: '👤'; ?>
                </a>
                <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-outline btn-sm">Logout</a>
            <?php elseif (isset($_SESSION['operator_id'])): ?>
                <a href="<?php echo BASE_URL; ?>operator-dashboard.php" class="btn btn-primary btn-sm">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-outline btn-sm">Logout</a>
            <?php elseif (isset($_SESSION['admin_id'])): ?>
                <a href="<?php echo BASE_URL; ?>admin-dashboard.php" class="btn btn-primary btn-sm">Admin Panel</a>
                <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-outline btn-sm">Logout</a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-outline btn-sm">Login</a>
                <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-primary btn-sm">Register</a>
            <?php endif; ?>

            <button class="dark-toggle" id="darkToggle" title="Toggle dark mode">🌙</button>
            <div class="hamburger" id="hamburger" onclick="document.getElementById('mobileNav').style.display=(document.getElementById('mobileNav').style.display=='none'?'block':'none')">
                <span></span><span></span><span></span>
            </div>
        </div>
    </div>
</header>

<div class="toast-container" id="toastContainer"></div>
