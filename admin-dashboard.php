<?php
require_once 'config/config.php';
require_once 'includes/session_guard.php';

require_login(['admin']);
set_no_cache_headers();

// Stats
$total_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$total_movies = $conn->query("SELECT COUNT(*) as c FROM movies")->fetch_assoc()['c'];
$total_bookings = $conn->query("SELECT COUNT(*) as c FROM bookings")->fetch_assoc()['c'];
$total_events = $conn->query("SELECT COUNT(*) as c FROM events")->fetch_assoc()['c'];
$total_revenue = $conn->query("SELECT SUM(amount) as r FROM payments WHERE payment_status = 'success'")->fetch_assoc()['r'];
$total_theatres = $conn->query("SELECT COUNT(*) as c FROM theatres")->fetch_assoc()['c'];
$pending_licences = $conn->query("SELECT COUNT(*) as c FROM theatre_licences WHERE status='pending'")->fetch_assoc()['c'] ?? 0;
$pending_events = $conn->query("SELECT COUNT(*) as c FROM events WHERE status='pending'")->fetch_assoc()['c'] ?? 0;
$pending_shows = $conn->query("SELECT COUNT(*) as c FROM shows WHERE status='pending'")->fetch_assoc()['c'] ?? 0;

// Recent Bookings
$recentBookings = $conn->query("
    SELECT b.booking_id, u.name as user_name, m.title, b.seats, b.total_price, b.booking_date, b.booking_status
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN shows s ON b.show_id = s.show_id
    JOIN movies m ON s.movie_id = m.movie_id
    ORDER BY b.booking_date DESC LIMIT 10
");

$pageTitle = 'Admin Dashboard - BookYourShow';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle; ?></title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
</head>
<body>

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <span class="brand-logo" style="font-size:1.4rem;">BookYourShow</span>
            <p style="color:var(--text-muted);font-size:0.8rem;margin-top:4px;">Admin Panel</p>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Main</div>
            <a href="admin-dashboard.php" class="sidebar-link active"><span class="icon">📊</span> Dashboard</a>
            <a href="admin/manage-movies.php" class="sidebar-link"><span class="icon">🎬</span> Movies</a>
            <a href="admin/manage-events.php" class="sidebar-link"><span class="icon">🎪</span> Events</a>
            <a href="admin/manage-organizers.php" class="sidebar-link"><span class="icon">🎭</span> Organizers</a>
            <a href="admin/manage-ads.php" class="sidebar-link"><span class="icon">🖼️</span> Slider Ads</a>

            <div class="sidebar-section-label" style="margin-top:12px;">Management</div>
            <a href="admin/manage-users.php" class="sidebar-link"><span class="icon">👥</span> Users</a>
            <a href="admin/manage-bookings.php" class="sidebar-link"><span class="icon">🎟️</span> Bookings</a>
            <a href="admin/manage-licences.php" class="sidebar-link">🎫 Licences</a>
            <a href="admin/manage-theatres.php" class="sidebar-link">🏟️ Theatres</a>
            <a href="admin/manage-shows.php" class="sidebar-link">📅 Shows</a>

            <div class="sidebar-section-label" style="margin-top:12px;">System</div>
            <a href="admin/manage-coupons.php" class="sidebar-link"><span class="icon">🏷️</span> Coupons</a>
            <a href="admin/manage-food.php" class="sidebar-link"><span class="icon">🍿</span> Food Menu</a>
            <a href="notifications.php" class="sidebar-link"><span class="icon">🔔</span> Notifications</a>
            <a href="index.php" class="sidebar-link"><span class="icon">🌐</span> View Site</a>
            <a href="logout.php" class="sidebar-link" style="margin-top:20px;color:var(--primary);"><span class="icon">🚪</span> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-topbar">
            <h3 style="font-size:1rem;font-weight:600;">📊 Dashboard Overview</h3>
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="color:var(--text-muted);font-size:0.85rem;">👑 <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </div>

        <div class="admin-content">
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">👥</div>
                    <div class="stat-info"><div class="stat-value"><?php echo $total_users; ?></div><div class="stat-label">Total Users</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">🎬</div>
                    <div class="stat-info"><div class="stat-value"><?php echo $total_movies; ?></div><div class="stat-label">Movies</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">🎟️</div>
                    <div class="stat-info"><div class="stat-value"><?php echo $total_bookings; ?></div><div class="stat-label">Bookings</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gold">💰</div>
                    <div class="stat-info"><div class="stat-value">₹<?php echo number_format($total_revenue ?? 0, 0); ?></div><div class="stat-label">Revenue</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">🎪</div>
                    <div class="stat-info"><div class="stat-value"><?php echo $total_events; ?></div><div class="stat-label">Events</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue">🏟️</div>
                    <div class="stat-info"><div class="stat-value"><?php echo $total_theatres; ?></div><div class="stat-label">Theatres</div></div>
                </div>
            </div>

            <!-- Pending Notifications Banner -->
            <?php if ($pending_licences > 0): ?>
            <div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-radius:12px;padding:16px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                <div>
                    <div style="font-weight:700;color:#f59e0b;">⏳ <?php echo $pending_licences; ?> Pending Theatre Licence<?php echo $pending_licences > 1 ? 's' : ''; ?></div>
                    <div style="font-size:.85rem;color:var(--text-muted);">Organizers are waiting for theatre approval</div>
                </div>
                <a href="admin/manage-licences.php" class="btn btn-sm" style="background:rgba(245,158,11,.2);color:#f59e0b;border:1px solid #f59e0b;">Review Licences →</a>
            </div>
            <?php
endif; ?>
            <?php if ($pending_events > 0): ?>
            <div style="background:rgba(248,68,100,.08);border:1px solid rgba(248,68,100,.2);border-radius:12px;padding:16px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                <div>
                    <div style="font-weight:700;color:var(--primary);">🎪 <?php echo $pending_events; ?> Pending Event<?php echo $pending_events > 1 ? 's' : ''; ?></div>
                    <div style="font-size:.85rem;color:var(--text-muted);">Organizer events awaiting approval</div>
                </div>
                <a href="admin/manage-events.php" class="btn btn-sm" style="background:rgba(248,68,100,.15);color:var(--primary);border:1px solid var(--primary);">Review Events →</a>
            </div>
            <?php
endif; ?>
            <?php if ($pending_shows > 0): ?>
            <div style="background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.25);border-radius:12px;padding:16px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                <div>
                    <div style="font-weight:700;color:#818cf8;">🎬 <?php echo $pending_shows; ?> Pending Show<?php echo $pending_shows > 1 ? 's' : ''; ?></div>
                    <div style="font-size:.85rem;color:var(--text-muted);">Organizer shows awaiting approval to go live</div>
                </div>
                <a href="admin/manage-shows.php" class="btn btn-sm" style="background:rgba(99,102,241,.15);color:#818cf8;border:1px solid #818cf8;">Review Shows →</a>
            </div>
            <?php
endif; ?>

            <!-- Recent Bookings -->
            <div class="table-wrapper">
                <div class="table-header">
                    <h4 class="table-title">🎟️ Recent Bookings</h4>
                    <a href="admin/manage-bookings.php" class="btn btn-dark btn-sm">View All</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>User</th>
                            <th>Movie</th>
                            <th>Seats</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentBookings->num_rows === 0): ?>
                        <tr><td colspan="7" style="text-align:center;color:var(--text-muted);">No bookings yet.</td></tr>
                        <?php
else:
    while ($b = $recentBookings->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo str_pad($b['booking_id'], 6, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($b['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($b['title']); ?></td>
                            <td style="font-size:0.85rem;"><?php echo htmlspecialchars($b['seats']); ?></td>
                            <td style="font-weight:600;color:var(--primary);">₹<?php echo number_format($b['total_price'], 2); ?></td>
                            <td style="color:var(--text-muted);font-size:0.85rem;"><?php echo date('d M Y', strtotime($b['booking_date'])); ?></td>
                            <td><span class="badge badge-success"><?php echo ucfirst($b['booking_status']); ?></span></td>
                        </tr>
                        <?php
    endwhile;
endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div class="toast-container"></div>
<script src="<?php echo BASE_URL; ?>js/script.js"></script>
</body>
</html>

