<?php
require_once 'config/config.php';
require_once 'includes/session_guard.php';

set_no_cache_headers();
prevent_relogin();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $admin = $conn->query("SELECT * FROM admins WHERE username = '" . $conn->real_escape_string($username) . "'")->fetch_assoc();
    if ($admin && password_verify($password, $admin['password'])) {
        // Regenerate session ID to prevent session fixation / old account data leaking
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_name'] = $admin['username'];
        header('Location: admin-dashboard.php');
        exit;
    } else {
        $error = 'Invalid admin credentials.';
    }
}

$pageTitle = 'Admin Login - BookYourShow';
include 'includes/header.php';
?>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo"><span class="brand-logo">BookYourShow</span></div>
        <h1 class="auth-title">👑 Admin Login</h1>
        <p class="auth-subtitle">Secure admin access portal</p>
        <?php if ($error): ?><div class="alert alert-danger">❌ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group"><label class="form-label">Username</label><div class="input-group"><span class="input-icon">👑</span><input type="text" class="form-control" name="username" placeholder="admin username" required></div></div>
            <div class="form-group"><label class="form-label">Password</label><div class="input-group"><span class="input-icon">🔒</span><input type="password" class="form-control" name="password" id="password" placeholder="admin password" required><span class="input-toggle">👁</span></div></div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">🔑 Admin Login</button>
        </form>
        <div class="auth-link"><a href="login.php">← Back to User Login</a></div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
