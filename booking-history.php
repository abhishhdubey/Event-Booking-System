<?php
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$uid = (int)$_SESSION['user_id'];

// Movie bookings
$movieBookings = $conn->query("
    SELECT b.*, m.title, t.theatre_name, t.city, s.show_date, s.show_time,
           p.payment_method, p.payment_status
    FROM bookings b
    JOIN shows s ON b.show_id = s.show_id
    JOIN movies m ON s.movie_id = m.movie_id
    JOIN theatres t ON s.theatre_id = t.theatre_id
    LEFT JOIN payments p ON b.booking_id = p.booking_id
    WHERE b.user_id = $uid
    ORDER BY b.booking_date DESC
");

// Event bookings
$eventBookings = $conn->query("
    SELECT eb.*, e.event_name, e.event_date, e.location
    FROM event_bookings eb
    JOIN events e ON eb.event_id = e.event_id
    WHERE eb.user_id = $uid
    ORDER BY eb.booking_date DESC
");

$pageTitle = 'My Bookings - BookYourShow';
include 'includes/header.php';
?>
<div class="container" style="padding:40px 15px;">
    <div class="breadcrumb"><a href="index.php">Home</a> <span class="separator">›</span><span>My Bookings</span></div>

    <h2 class="section-title" style="margin-bottom:20px;">🎟️ My Booking History</h2>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('movie',this)">🎬 Movie Tickets (<?php echo $movieBookings->num_rows; ?>)</button>
        <button class="tab-btn" onclick="switchTab('event',this)">🎪 Event Tickets (<?php echo $eventBookings->num_rows; ?>)</button>
    </div>

    <!-- Movie Bookings -->
    <div id="tab-movie">
        <?php if ($movieBookings->num_rows === 0): ?>
        <div style="text-align:center;padding:60px;color:var(--text-muted);">
            <div style="font-size:3rem;">🎬</div>
            <p>No movie bookings yet.</p>
            <a href="movies.php" class="btn btn-primary" style="margin-top:16px;">Browse Movies</a>
        </div>
        <?php else: while($b = $movieBookings->fetch_assoc()): ?>
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
            <div style="flex:1;">
                <div style="font-size:1.1rem;font-weight:700;"><?php echo htmlspecialchars($b['title']); ?></div>
                <div style="color:var(--text-muted);font-size:0.85rem;margin-top:4px;">
                    🏟️ <?php echo htmlspecialchars($b['theatre_name']); ?>, <?php echo htmlspecialchars($b['city']); ?><br>
                    📅 <?php echo date('D, d M Y', strtotime($b['show_date'])); ?> &nbsp;|&nbsp; 🕐 <?php echo date('h:i A', strtotime($b['show_time'])); ?><br>
                    💺 Seats: <?php echo htmlspecialchars($b['seats']); ?>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:1.2rem;font-weight:700;color:var(--primary);">₹<?php echo number_format($b['total_price'],2); ?></div>
                <span class="badge badge-success"><?php echo ucfirst($b['booking_status']); ?></span>
                <div style="margin-top:10px;display:flex;gap:8px;justify-content:flex-end;">
                    <button type="button" onclick="showQR('BMS<?php echo str_pad($b['booking_id'], 6, '0', STR_PAD_LEFT); ?>')" class="btn btn-outline btn-sm">🔍 QR</button>
                    <a href="payment-success.php?booking_id=<?php echo $b['booking_id']; ?>" class="btn btn-dark btn-sm">🎟️ View Ticket</a>
                </div>
            </div>
        </div>
        <?php endwhile; endif; ?>
    </div>

    <!-- Event Bookings -->
    <div id="tab-event" style="display:none;">
        <?php if ($eventBookings->num_rows === 0): ?>
        <div style="text-align:center;padding:60px;color:var(--text-muted);">
            <div style="font-size:3rem;">🎪</div>
            <p>No event bookings yet.</p>
            <a href="events.php" class="btn btn-primary" style="margin-top:16px;">Browse Events</a>
        </div>
        <?php else: while($eb = $eventBookings->fetch_assoc()): ?>
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
            <div style="flex:1;">
                <div style="font-size:1.1rem;font-weight:700;"><?php echo htmlspecialchars($eb['event_name']); ?></div>
                <div style="color:var(--text-muted);font-size:0.85rem;margin-top:4px;">
                    📅 <?php echo date('D, d M Y', strtotime($eb['event_date'])); ?><br>
                    📍 <?php echo htmlspecialchars($eb['location']); ?><br>
                    🎫 <?php echo $eb['tickets']; ?> Ticket(s)
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:1.2rem;font-weight:700;color:var(--primary);">₹<?php echo number_format($eb['total_price'],2); ?></div>
                <span class="badge badge-success">Confirmed</span>
                <div style="color:var(--text-muted);font-size:0.8rem;margin-top:6px;">#EVT<?php echo str_pad($eb['event_booking_id'],6,'0',STR_PAD_LEFT); ?></div>
                <div style="margin-top:10px;display:flex;gap:8px;justify-content:flex-end;">
                    <button type="button" onclick="showQR('EVT<?php echo str_pad($eb['event_booking_id'], 6, '0', STR_PAD_LEFT); ?>')" class="btn btn-outline btn-sm">🔍 QR</button>
                </div>
            </div>
        </div>
        <?php endwhile; endif; ?>
    </div>
</div>

<div id="qrModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:var(--bg-card); padding:30px; border-radius:12px; text-align:center; position:relative;">
        <button onclick="document.getElementById('qrModal').style.display='none'" style="position:absolute; top:10px; right:15px; background:none; border:none; color:var(--text-light); font-size:1.5rem; cursor:pointer;">&times;</button>
        <h3 style="margin-bottom:16px;">Scan Ticket</h3>
        <img id="qrImage" src="" alt="QR Code" style="border-radius:8px; margin-bottom:10px;">
        <div id="qrText" style="font-weight:bold; letter-spacing:1px; color:var(--primary);"></div>
    </div>
</div>

<script>
function showQR(code) {
    document.getElementById('qrImage').src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + code;
    document.getElementById('qrText').innerText = code;
    document.getElementById('qrModal').style.display = 'flex';
}

function switchTab(tab, btn) {
    document.getElementById('tab-movie').style.display = 'none';
    document.getElementById('tab-event').style.display = 'none';
    document.querySelectorAll('.tab-btn').forEach(function(b){ b.classList.remove('active'); });
    document.getElementById('tab-' + tab).style.display = 'block';
    btn.classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>

