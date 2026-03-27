<?php
require_once 'config/config.php';
require_once 'includes/session_guard.php';

set_no_cache_headers();
prevent_relogin();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');   // email or phone
    $password = $_POST['password'] ?? '';

    // Find organizer by email OR phone
    $stmt = $conn->prepare("SELECT * FROM operators WHERE email = ? OR phone = ? LIMIT 1");
    $stmt->bind_param('ss', $login, $login);
    $stmt->execute();
    $op = $stmt->get_result()->fetch_assoc();

    if ($op && password_verify($password, $op['password'])) {
        if (isset($op['status']) && $op['status'] === 'suspended') {
            $error = 'Your account has been suspended. Please contact admin.';
        } else {
            session_regenerate_id(true);
            $_SESSION['operator_id']   = $op['operator_id'];
            $_SESSION['operator_name'] = $op['name'];
            $_SESSION['operator_org']  = $op['organization'];
            $_SESSION['operator_city'] = $op['city'] ?? '';
            header('Location: operator-dashboard.php');
            exit;
        }
    } else {
        $error = 'Invalid email/phone or password.';
    }
}

$pageTitle = 'Organizer Login - BookYourShow';
include 'includes/header.php';
?>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo"><span class="brand-logo">BookYourShow</span></div>
        <h1 class="auth-title">🎪 Organizer Login</h1>
        <p class="auth-subtitle">Manage your events and sales</p>
        <?php if(isset($_GET['msg']) && $_GET['msg']==='registered'): ?><div class="alert alert-success">✅ Registered successfully! Now login.</div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger">❌ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email or Phone Number</label>
                <div class="input-group">
                    <span class="input-icon">📱</span>
                    <input type="text" class="form-control" name="login" id="login"
                           placeholder="Email address or 10-digit phone" required
                           value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-icon">🔒</span>
                    <input type="password" class="form-control" name="password" id="password" placeholder="Your password" required>
                    <span class="input-toggle" onclick="var e=document.getElementById('password');e.type=e.type==='password'?'text':'password';this.textContent=e.type==='password'?'👁':'🙈';">👁</span>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">🔑 Login</button>
        </form>
        <div class="auth-link">No account? <a href="operator-register.php">Register here</a></div>
        <div class="auth-link" style="margin-top:6px;"><a href="login.php">← User Login</a></div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
