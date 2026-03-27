<?php
session_start();
require_once 'config/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$phone || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = 'Phone number must be 10 digits.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $name, $email, $phone, $hashed);
            if ($stmt->execute()) {
                $success = 'Account created successfully! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register - BookYourShow';
include 'includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <span class="brand-logo">BookYourShow</span>
        </div>
        <h1 class="auth-title">Create Account</h1>
        <p class="auth-subtitle">Join millions of movie lovers</p>

        <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php" onsubmit="return validateRegisterForm()">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-icon">👤</span>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-icon">✉️</span>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <div class="input-group">
                    <span class="input-icon">📱</span>
                    <input type="text" class="form-control" id="phone" name="phone" placeholder="10-digit mobile number" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required maxlength="10">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-icon">🔒</span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Min. 6 characters" required>
                        <span class="input-toggle">👁</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-icon">🔒</span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                        <span class="input-toggle">👁</span>
                    </div>
                </div>
            </div>
            <div class="form-check">
                <input type="checkbox" id="terms" required>
                <label for="terms">I agree to the <a href="about.php" style="color:var(--primary);">Terms & Conditions</a></label>
            </div>
            <br>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">🚀 Create Account</button>
        </form>



        <div class="auth-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
        <div class="auth-link" style="margin-top:10px;">
            <a href="operator-register.php">Register as Event Organizer →</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

