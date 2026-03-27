<?php
session_start();
require_once 'config/config.php';

$notifications = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");
$pageTitle = 'Notifications - BookYourShow';
include 'includes/header.php';
?>
<div class="container" style="padding:40px 15px;max-width:800px;">
    <div class="breadcrumb"><a href="index.php">Home</a> <span class="separator">›</span><span>Notifications</span></div>
    <h2 class="section-title" style="margin-bottom:8px;">🔔 Notifications</h2>
    <p class="section-subtitle">Latest offers, deals and announcements</p>

    <?php if ($notifications->num_rows === 0): ?>
    <div style="text-align:center;padding:60px;color:var(--text-muted);">
        <div style="font-size:3rem;">🔔</div><p>No notifications yet.</p>
    </div>
    <?php else: while($n = $notifications->fetch_assoc()): ?>
    <div class="notification-item">
        <div class="notification-icon">🎉</div>
        <div>
            <strong><?php echo htmlspecialchars($n['title']); ?></strong>
            <p style="color:var(--text-muted);font-size:0.9rem;margin-top:5px;"><?php echo htmlspecialchars($n['message']); ?></p>
            <small style="color:var(--text-muted);font-size:0.78rem;">📅 <?php echo date('d M Y, h:i A', strtotime($n['created_at'])); ?></small>
        </div>
    </div>
    <?php endwhile; endif; ?>
</div>
<?php include 'includes/footer.php'; ?>

