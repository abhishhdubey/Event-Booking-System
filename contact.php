<?php
session_start();
require_once 'config/config.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $email   = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $message = $conn->real_escape_string(trim($_POST['message'] ?? ''));

    if (!$name || !$email || !$message) {
        $msg = 'error:All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'error:Please enter a valid email.';
    } else {
        $conn->query("INSERT INTO contacts (name, email, message) VALUES ('$name', '$email', '$message')");
        $msg = 'success:Thank you! We will get back to you soon.';
    }
}

$pageTitle = 'Contact Us - BookYourShow';
include 'includes/header.php';
?>

<section class="page-hero">
    <h1>📞 Contact Us</h1>
    <p>We'd love to hear from you. Send us a message!</p>
</section>

<div class="container" style="padding:60px 15px;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:60px;max-width:1000px;margin:0 auto;">
        <!-- Form -->
        <div>
            <h3 style="margin-bottom:20px;">Send a Message</h3>
            <?php if ($msg): list($t,$m) = explode(':',$msg,2); ?>
            <div class="alert alert-<?php echo $t==='success'?'success':'danger'; ?>"><?php echo htmlspecialchars($m); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group"><label class="form-label">Your Name</label><input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? $_SESSION['user_name'] ?? ''); ?>" required></div>
                <div class="form-group"><label class="form-label">Email Address</label><input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? $_SESSION['user_email'] ?? ''); ?>" required></div>
                <div class="form-group"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="5" placeholder="Tell us how we can help..." required></textarea></div>
                <button type="submit" class="btn btn-primary btn-lg">📤 Send Message</button>
            </form>
        </div>

        <!-- Contact Info -->
        <div>
            <h3 style="margin-bottom:20px;">Get in Touch</h3>
            <?php $info = [['📍','Our Address','123 Entertainment Hub, Mumbai, Maharashtra 400001'],['📧','Email Us','support@BookYourShow.com'],['📞','Call Us','+91 98765 43210'],['🕐','Working Hours','Mon - Sat: 9:00 AM - 6:00 PM']]; foreach($info as $item): ?>
            <div style="display:flex;gap:16px;margin-bottom:24px;padding:20px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);">
                <div style="font-size:1.8rem;"><?php echo $item[0]; ?></div>
                <div><strong><?php echo $item[1]; ?></strong><p style="color:var(--text-muted);font-size:0.9rem;margin-top:4px;"><?php echo $item[2]; ?></p></div>
            </div>
            <?php endforeach; ?>

            <!-- Social -->
            <div style="display:flex;gap:12px;margin-top:8px;">
                <a href="#" class="social-link" style="width:44px;height:44px;">📘</a>
                <a href="#" class="social-link" style="width:44px;height:44px;">🐦</a>
                <a href="#" class="social-link" style="width:44px;height:44px;">📷</a>
                <a href="#" class="social-link" style="width:44px;height:44px;">📺</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

