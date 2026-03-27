<?php
session_start();
require_once 'config/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: events.php'); exit; }

$event = $conn->query("SELECT e.*, o.name as organizer, o.organization FROM events e LEFT JOIN operators o ON e.organizer_id = o.operator_id WHERE e.event_id = $id")->fetch_assoc();
if (!$event) { header('Location: events.php'); exit; }

$pageTitle = htmlspecialchars($event['event_name']) . ' - BookYourShow';
include 'includes/header.php';
?>

<?php
// Only show approved events publicly (treat NULL/empty status as approved for legacy)
$eventStatus = $event['status'] ?? 'approved';
if ($eventStatus !== '' && $eventStatus !== 'approved') {
    if (!isset($_SESSION['admin_id']) && !isset($_SESSION['operator_id'])) {
        header('Location: events.php'); exit;
    }
}

$hasBanner = !empty($event['banner_image']) && file_exists('assets/images/events/' . $event['banner_image']);
$hasPoster = !empty($event['poster']) && file_exists('assets/images/events/' . $event['poster']);
$bannerUrl  = $hasBanner ? BASE_URL . 'assets/images/events/' . htmlspecialchars($event['banner_image']) : '';
$posterUrl  = $hasPoster ? BASE_URL . 'assets/images/events/' . htmlspecialchars($event['poster']) : '';
?>

<!-- Event Banner Hero -->
<div style="
    position:relative;
    min-height:400px;
    background:<?php echo $hasBanner ? "url('$bannerUrl') center/cover no-repeat" : 'linear-gradient(135deg,#1a0032,#0f0f1a)'; ?>;
    border-bottom:1px solid var(--border);
    overflow:hidden;
    display:flex;
    align-items:center;
">
    <!-- Multi-layer dark overlay for readability -->
    <?php if ($hasBanner): ?>
    <!-- Bottom-to-top fade so bottom is always dark -->
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom, rgba(0,0,0,0.35) 0%, rgba(0,0,0,0.7) 100%);"></div>
    <!-- Left-to-right fade so text side is darkest -->
    <div style="position:absolute;inset:0;background:linear-gradient(to right, rgba(0,0,0,0.88) 0%, rgba(0,0,0,0.60) 45%, rgba(0,0,0,0.15) 100%);"></div>
    <!-- Extra blur backdrop behind text area -->
    <div style="position:absolute;inset:0;backdrop-filter:none;"></div>
    <?php else: ?>
    <div style="position:absolute;inset:0;background:linear-gradient(135deg,rgba(26,0,50,0.97),rgba(15,15,26,0.95));"></div>
    <?php endif; ?>

    <div class="container" style="position:relative;z-index:2;padding:50px 15px;width:100%;">
        <?php if ($hasPoster): ?>
        <!-- Layout with poster -->
        <div style="display:flex;gap:36px;align-items:flex-start;flex-wrap:wrap;">
            <div style="flex-shrink:0;width:180px;border-radius:12px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.7);">
                <img src="<?php echo $posterUrl; ?>" style="width:100%;display:block;object-fit:cover;" alt="<?php echo htmlspecialchars($event['event_name']); ?>">
            </div>
            <div style="flex:1;min-width:0;">
        <?php else: ?>
        <!-- Layout without poster — full width -->
        <div>
            <div>
        <?php endif; ?>
                <span class="badge badge-info" style="margin-bottom:14px;display:inline-block;backdrop-filter:blur(6px);">Live Event</span>
                <h1 style="font-size:2.6rem;font-weight:800;margin-bottom:16px;text-shadow:0 2px 12px rgba(0,0,0,0.8);line-height:1.2;"><?php echo htmlspecialchars($event['event_name']); ?></h1>
                <div style="display:flex;gap:20px;flex-wrap:wrap;color:rgba(255,255,255,0.85);margin-bottom:18px;font-size:0.95rem;">
                    <span style="text-shadow:0 1px 4px rgba(0,0,0,0.8);">📅 <?php echo date('D, d M Y', strtotime($event['event_date'])); ?></span>
                    <span style="text-shadow:0 1px 4px rgba(0,0,0,0.8);">📍 <?php echo htmlspecialchars($event['location']); ?></span>
                    <span style="text-shadow:0 1px 4px rgba(0,0,0,0.8);">👤 <?php echo htmlspecialchars($event['organizer']); ?></span>
                </div>
                <?php if ($event['description']): ?>
                <p style="color:rgba(255,255,255,0.78);line-height:1.8;margin-bottom:24px;max-width:580px;text-shadow:0 1px 6px rgba(0,0,0,0.7);"><?php echo htmlspecialchars(substr($event['description'], 0, 200)); ?><?php echo strlen($event['description']) > 200 ? '...' : ''; ?></p>
                <?php endif; ?>
                <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                    <div style="background:rgba(0,0,0,0.4);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.15);padding:10px 18px;border-radius:12px;">
                        <div style="color:rgba(255,255,255,0.6);font-size:0.78rem;margin-bottom:2px;">Starting from</div>
                        <div style="font-size:1.8rem;font-weight:800;color:var(--primary);">₹<?php echo number_format($event['ticket_price'],0); ?></div>
                    </div>
                    <a href="event-booking.php?id=<?php echo $id; ?>" class="btn btn-primary btn-lg pulse">🎟️ Book Tickets</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding:40px 15px;">
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:40px;">
        <div>
            <h3 style="margin-bottom:16px;">About This Event</h3>
            <p style="color:var(--text-muted);line-height:1.9;"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
        </div>
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;height:fit-content;">
            <h4 style="margin-bottom:16px;">Event Information</h4>
            <?php $info = ['Date' => date('D, d M Y', strtotime($event['event_date'])), 'Venue' => $event['location'], 'Organizer' => $event['organizer'], 'Organization' => $event['organization'], 'Price' => '₹' . number_format($event['ticket_price'], 0) . ' per ticket']; foreach($info as $k=>$v): ?>
            <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);font-size:0.9rem;">
                <span style="color:var(--text-muted);"><?php echo $k; ?></span>
                <span style="font-weight:500;text-align:right;max-width:60%;"><?php echo htmlspecialchars($v); ?></span>
            </div>
            <?php endforeach; ?>
            <a href="event-booking.php?id=<?php echo $id; ?>" class="btn btn-primary" style="width:100%;margin-top:20px;justify-content:center;">Book Now →</a>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>

