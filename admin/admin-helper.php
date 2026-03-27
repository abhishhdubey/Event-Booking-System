<?php
// Admin helper - check admin session
session_start();
require_once '../config/config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: ../admin-login.php'); exit; }

function adminSidebar($active = '') {
    global $conn;
    $base = BASE_URL;

    // Pending licence count for badge
    $pendingLic = 0;
    $r = $conn->query("SELECT COUNT(*) c FROM theatre_licences WHERE status='pending'");
    if ($r) $pendingLic = (int)$r->fetch_assoc()['c'];

    // Unread notifications
    $unreadNotif = 0;
    $r2 = $conn->query("SELECT COUNT(*) c FROM admin_notifications WHERE is_read=0");
    if ($r2) $unreadNotif = (int)$r2->fetch_assoc()['c'];

    $links = [
        'admin-dashboard.php'            => ['📊', 'Dashboard', 0],
        'admin/manage-movies.php'        => ['🎬', 'Movies', 0],
        'admin/manage-events.php'        => ['🎪', 'Events', 0],
        'admin/manage-organizers.php'    => ['🎭', 'Organizers', 0],
        'admin/manage-ads.php'           => ['🖼️', 'Slider Ads', 0],
        'admin/manage-licences.php'      => ['🎫', 'Licences', $pendingLic],
        'admin/manage-theatres.php'      => ['🏟️', 'Theatres', 0],
        'admin/manage-shows.php'         => ['📅', 'Shows', 0],
        'admin/manage-users.php'         => ['👥', 'Users', 0],
        'admin/manage-bookings.php'      => ['🎟️', 'Bookings', 0],
        'admin/manage-coupons.php'       => ['🏷️', 'Coupons', 0],
        'admin/manage-food.php'          => ['🍿', 'Food Menu', 0],
    ];

    echo '<aside class="admin-sidebar">';
    echo '<div class="sidebar-brand"><span class="brand-logo" style="font-size:1.4rem;">BookYourShow</span><p style="color:var(--text-muted);font-size:0.8rem;margin-top:4px;">Admin Panel</p></div>';
    echo '<nav class="sidebar-nav">';
    foreach ($links as $path => $info) {
        $isActive = ($active === $path) ? 'active' : '';
        $badge = $info[2] > 0 ? "<span style='background:var(--primary);color:#fff;border-radius:50px;padding:1px 7px;font-size:.7rem;margin-left:auto;'>$info[2]</span>" : '';
        echo "<a href='{$base}{$path}' class='sidebar-link {$isActive}' style='display:flex;align-items:center;gap:8px;'><span class='icon'>{$info[0]}</span> {$info[1]}{$badge}</a>";
    }
    echo "<a href='{$base}index.php' class='sidebar-link'><span class='icon'>🌐</span> View Site</a>";
    echo "<a href='{$base}logout.php' class='sidebar-link' style='color:var(--primary);margin-top:20px;'><span class='icon'>🚪</span> Logout</a>";
    echo '</nav></aside>';
}

function adminLayout($title, $content, $active = '') {
    global $conn;
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($title) . ' - Admin | BookYourShow</title>';
    echo '<link rel="stylesheet" href="' . BASE_URL . 'css/style.css"></head><body>';
    echo '<div class="admin-layout">';
    adminSidebar($active);
    echo '<main class="admin-main">';
    echo '<div class="admin-topbar"><h3 style="font-size:1rem;font-weight:600;">' . htmlspecialchars($title) . '</h3>';
    echo '<div style="display:flex;align-items:center;gap:12px;"><span style="color:var(--text-muted);font-size:0.85rem;">👑 ' . htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') . '</span><a href="' . BASE_URL . 'logout.php" class="btn btn-outline btn-sm">Logout</a></div></div>';
    echo '<div class="admin-content">' . $content . '</div></main></div>';
    echo '<div class="toast-container"></div><script src="' . BASE_URL . 'js/script.js"></script></body></html>';
}
?>
