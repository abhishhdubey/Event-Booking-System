<?php
session_start();
require_once 'config/config.php';

// --- Session Guard ---
if (isset($_SESSION['operator_id'])) { header('Location: operator-dashboard.php'); exit; }
if (isset($_SESSION['admin_id']))    { header('Location: admin-dashboard.php'); exit; }
if (isset($_SESSION['user_id']))     { header('Location: index.php'); exit; }

$cities = ['Mumbai','Delhi','Bangalore','Hyderabad','Chennai','Kolkata','Pune','Ahmedabad','Jaipur','Chandigarh','Surat','Kochi'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $org   = trim($_POST['organization'] ?? '');
    $city  = trim($_POST['city'] ?? '');

    if (!$name || !$email || !$password || !$org || !$city) {
        $error = 'All fields including City are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($phone && !preg_match('/^[0-9]{10}$/', $phone)) {
        $error = 'Phone number must be 10 digits.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $check = $conn->query("SELECT operator_id FROM operators WHERE email = '" . $conn->real_escape_string($email) . "'");
        if ($check && $check->num_rows > 0) {
            $error = 'This email is already registered as an organizer.';
        } else {
            // Ensure phone column exists
            $conn->query("ALTER TABLE operators ADD COLUMN IF NOT EXISTS phone VARCHAR(15) DEFAULT '' AFTER email");
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO operators (name, email, phone, password, organization, city, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            if ($stmt) {
                $stmt->bind_param('ssssss', $name, $email, $phone, $hashed, $org, $city);
                if ($stmt->execute()) {
                    header('Location: operator-login.php?msg=registered');
                    exit;
                } else {
                    $error = 'Registration failed: ' . $conn->error;
                }
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Organizer Register - BookYourShow';
include 'includes/header.php';
?>
<div class="auth-wrapper">
    <div class="auth-card" style="max-width:480px;">
        <div class="auth-logo"><span class="brand-logo">BookYourShow</span></div>
        <h1 class="auth-title">🎪 Become an Organizer</h1>
        <p class="auth-subtitle">List your events & shows. Reach millions of fans.</p>

        <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" id="regForm">
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" class="form-control" name="name" placeholder="Your full name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address *</label>
                <input type="email" class="form-control" name="email" placeholder="organizer@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">📱 Phone Number</label>
                <div class="input-group">
                    <span class="input-icon">📱</span>
                    <input type="text" class="form-control" name="phone" placeholder="10-digit mobile number" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" maxlength="10">
                </div>
                <small style="color:var(--text-muted);">Optional — used for login &amp; notifications</small>
            </div>
            <div class="form-group">
                <label class="form-label">Organization / Company Name *</label>
                <input type="text" class="form-control" name="organization" placeholder="Your company or organization" value="<?php echo htmlspecialchars($_POST['organization'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Primary City *</label>
                <select class="form-control" name="city" required>
                    <option value="">-- Select Your City --</option>
                    <?php foreach($cities as $c): ?>
                    <option value="<?php echo $c; ?>" <?php echo (($_POST['city'] ?? '') === $c) ? 'selected' : ''; ?>><?php echo $c; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Password *</label>
                <div class="input-group">
                    <span class="input-icon">🔒</span>
                    <input type="password" class="form-control" name="password" id="regPassword" placeholder="Min. 6 characters" required>
                    <span class="input-toggle" onclick="togglePwd('regPassword',this)" style="cursor:pointer;">👁</span>
                </div>
                <small style="color:var(--text-muted);">Minimum 6 characters</small>
            </div>

            <div style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:8px;padding:12px;margin-bottom:16px;font-size:0.85rem;color:#f59e0b;">
                ⚠️ After registration, you can start listing shows. Theatre licences require admin approval before shows go live.
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                🚀 Register as Organizer
            </button>
        </form>

        <div class="auth-link">Already have an account? <a href="operator-login.php">Login here</a></div>
        <div class="auth-link" style="margin-top:8px;"><a href="login.php">← Back to User Login</a></div>
    </div>
</div>
<script>
function togglePwd(id, btn) {
    var el = document.getElementById(id);
    if (el.type === 'password') { el.type = 'text'; btn.textContent = '🙈'; }
    else { el.type = 'password'; btn.textContent = '👁'; }
}
</script>
<?php include 'includes/footer.php'; ?>
