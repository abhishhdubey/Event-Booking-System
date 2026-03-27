<?php
require_once 'config/config.php';
require_once 'includes/session_guard.php';

set_no_cache_headers();
prevent_relogin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');   // email or phone
    $password = $_POST['password'] ?? '';

    if (!$login || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        // Try to find user by email OR phone
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1");
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent session fixation / old account data leaking
            session_regenerate_id(true);
            $_SESSION['user_id']      = $user['user_id'];
            $_SESSION['user_name']    = $user['name'];
            $_SESSION['user_email']   = $user['email'];
            $_SESSION['profile_image']= $user['profile_image'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid email/phone or password.';
        }
    }
}

$pageTitle = 'Login - BookYourShow';
include 'includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <span class="brand-logo">BookYourShow</span>
        </div>
        <h1 class="auth-title">Welcome Back!</h1>
        <p class="auth-subtitle">Login to book your favourite shows</p>

        <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'registered'): ?>
        <div class="alert alert-success">✅ Registration successful! Please login.</div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label class="form-label">Email or Phone Number</label>
                <div class="input-group">
                    <span class="input-icon">📱</span>
                    <input type="text" class="form-control" id="login" name="login"
                           placeholder="Email address or 10-digit phone"
                           value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-icon">🔒</span>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Enter your password" required>
                    <span class="input-toggle" onclick="var e=document.getElementById('password');e.type=e.type==='password'?'text':'password';this.textContent=e.type==='password'?'👁':'🙈';">👁</span>
                </div>
            </div>
            <div class="form-check" style="justify-content:flex-end;">
                <a href="register.php" style="color:var(--primary);font-size:0.85rem;">Forgot Password?</a>
            </div>
            <br>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">🔑 Login</button>
        </form>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px;">
            <a href="admin-login.php" class="btn btn-dark" style="justify-content:center;font-size:0.85rem;">👑 Admin</a>
            <a href="operator-login.php" class="btn btn-dark" style="justify-content:center;font-size:0.85rem;">🎪 Organizer</a>
        </div>

        <div class="auth-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
