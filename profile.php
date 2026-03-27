<?php
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$uid = (int)$_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE user_id = $uid")->fetch_assoc();

$msg = '';

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name  = $conn->real_escape_string(trim($_POST['name']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $conn->query("UPDATE users SET name='$name', phone='$phone' WHERE user_id = $uid");
    $_SESSION['user_name'] = $name;
    $msg = 'success:Profile updated!';
    $user['name'] = $name; $user['phone'] = $phone;
}

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $curr = $_POST['current_password'];
    $new  = $_POST['new_password'];
    $conf = $_POST['confirm_password'];
    if (!password_verify($curr, $user['password'])) {
        $msg = 'error:Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $msg = 'error:New password must be at least 6 characters.';
    } elseif ($new !== $conf) {
        $msg = 'error:New passwords do not match.';
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hashed' WHERE user_id = $uid");
        $msg = 'success:Password changed successfully!';
    }
}

// Stats
$bookingCount = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE user_id = $uid")->fetch_assoc()['c'];
$wishlistCount = $conn->query("SELECT COUNT(*) as c FROM wishlist WHERE user_id = $uid")->fetch_assoc()['c'];
$reviewCount = $conn->query("SELECT COUNT(*) as c FROM reviews WHERE user_id = $uid")->fetch_assoc()['c'];

$pageTitle = 'My Profile - BookYourShow';
include 'includes/header.php';
?>

<div class="container" style="padding:40px 15px;">
    <?php if ($msg): list($t,$m) = explode(':',$msg,2); ?>
    <div class="alert alert-<?php echo $t==='success'?'success':'danger'; ?>"><?php echo htmlspecialchars($m); ?></div>
    <?php endif; ?>

    <div class="dashboard-grid">
        <!-- Sidebar -->
        <div class="dashboard-sidebar">
            <div class="user-card">
                <img src="images/default.png" class="user-avatar" onerror="this.style.display='none'" alt="avatar">
                <div style="font-weight:700;font-size:1rem;"><?php echo htmlspecialchars($user['name']); ?></div>
                <div style="color:var(--text-muted);font-size:0.85rem;"><?php echo htmlspecialchars($user['email']); ?></div>
                <div style="color:var(--text-muted);font-size:0.8rem;margin-top:4px;">📅 Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></div>
            </div>
            <div class="sidebar-nav-list">
                <a href="profile.php" class="sidebar-nav-item active">👤 My Profile</a>
                <a href="booking-history.php" class="sidebar-nav-item">🎟️ My Bookings (<?php echo $bookingCount; ?>)</a>
                <a href="wishlist.php" class="sidebar-nav-item">❤️ Wishlist (<?php echo $wishlistCount; ?>)</a>
                <a href="notifications.php" class="sidebar-nav-item">🔔 Notifications</a>
                <a href="logout.php" class="sidebar-nav-item" style="color:var(--primary);">🚪 Logout</a>
            </div>
        </div>

        <!-- Main -->
        <div>
            <!-- Stats -->
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;text-align:center;">
                    <div style="font-size:1.8rem;font-weight:700;color:var(--primary);"><?php echo $bookingCount; ?></div>
                    <div style="color:var(--text-muted);font-size:0.85rem;">Total Bookings</div>
                </div>
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;text-align:center;">
                    <div style="font-size:1.8rem;font-weight:700;color:var(--primary);"><?php echo $wishlistCount; ?></div>
                    <div style="color:var(--text-muted);font-size:0.85rem;">Wishlist</div>
                </div>
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;text-align:center;">
                    <div style="font-size:1.8rem;font-weight:700;color:var(--primary);"><?php echo $reviewCount; ?></div>
                    <div style="color:var(--text-muted);font-size:0.85rem;">Reviews</div>
                </div>
            </div>

            <!-- Profile Form -->
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:24px;">
                <h4 style="margin-bottom:20px;">✏️ Edit Profile</h4>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Full Name</label><input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required></div>
                        <div class="form-group"><label class="form-label">Phone</label><input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>"></div>
                    </div>
                    <div class="form-group"><label class="form-label">Email (Cannot change)</label><input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="opacity:0.6;"></div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                </form>
            </div>

            <!-- Change Password -->
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;">
                <h4 style="margin-bottom:20px;">🔒 Change Password</h4>
                <form method="POST">
                    <div class="form-group"><label class="form-label">Current Password</label><div class="input-group"><span class="input-icon">🔒</span><input type="password" class="form-control" name="current_password" required><span class="input-toggle">👁</span></div></div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">New Password</label><input type="password" class="form-control" name="new_password" required></div>
                        <div class="form-group"><label class="form-label">Confirm New</label><input type="password" class="form-control" name="confirm_password" required></div>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

