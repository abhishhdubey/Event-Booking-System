<?php
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if (!$booking_id) { header('Location: events.php'); exit; }

$booking = $conn->query("
    SELECT b.*, e.event_name, e.location, e.event_date, e.event_time, e.ticket_price,
           u.name as user_name, u.email
    FROM event_bookings b
    JOIN events e ON b.event_id = e.event_id
    JOIN users u ON b.user_id = u.user_id
    WHERE b.booking_id = $booking_id AND b.user_id = {$_SESSION['user_id']}
")->fetch_assoc();

if (!$booking) { header('Location: events.php'); exit; }

$pageTitle = 'Event Ticket Confirmed! - BookYourShow';
include 'includes/header.php';
?>

<div class="success-wrapper">
    <div class="success-card" id="printArea">
        <div class="success-icon">✅</div>
        <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:8px;">Booking Confirmed!</h1>
        <p style="color:var(--text-muted);margin-bottom:4px;">Your event ticket has been booked successfully.</p>
        <p style="color:var(--text-muted);font-size:0.85rem;">Booking ID: <strong style="color:var(--primary);">#EVT<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?></strong></p>

        <!-- Ticket -->
        <div class="ticket-card">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding-bottom:16px;border-bottom:1px dashed var(--border);">
                <div style="font-size:2.5rem;">🎪</div>
                <div>
                    <div style="font-size:1.1rem;font-weight:700;"><?php echo htmlspecialchars($booking['event_name']); ?></div>
                    <div style="color:var(--text-muted);font-size:0.85rem;">Event Ticket</div>
                </div>
            </div>

            <?php
            $ticketDetails = [
                '📍 Location' => $booking['location'],
                '📅 Date' => date('D, d M Y', strtotime($booking['event_date'])),
                '🕐 Time' => date('h:i A', strtotime($booking['event_time'])),
                '🎟️ Tickets' => $booking['tickets'] . ' Ticket(s)',
                '💳 Payment' => $booking['payment_method'],
                '✅ Status' => 'Confirmed'
            ];
            foreach($ticketDetails as $k => $v):
            ?>
            <div class="ticket-row">
                <span><?php echo $k; ?></span>
                <span><?php echo htmlspecialchars($v); ?></span>
            </div>
            <?php endforeach; ?>

            <div class="ticket-row" style="border-top:2px dashed var(--primary);margin-top:8px;padding-top:12px;">
                <span style="font-size:1rem;font-weight:700;">💰 Total Paid</span>
                <span style="font-size:1.2rem;font-weight:700;color:var(--primary);">₹<?php echo number_format($booking['total_price'], 2); ?></span>
            </div>
        </div>

        <!-- QR Code -->
        <div style="background:var(--bg-dark);border-radius:8px;padding:16px;margin-bottom:24px;text-align:center;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=EVT<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?>" alt="QR Code" style="margin-bottom:8px; border-radius: 8px;">
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;"><?php echo htmlspecialchars($booking['email']); ?></div>
        </div>

        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <button onclick="printTicket()" class="btn btn-primary">🖨️ Print Ticket</button>
            <a href="booking-history.php" class="btn btn-dark">📋 My Bookings</a>
            <a href="events.php" class="btn btn-outline">🎪 Browse Events</a>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar, .footer, .btn, .toast-container { display: none !important; }
    .success-wrapper { margin: 0; padding: 0; }
    .success-card {
        box-shadow: none;
        border: 2px solid #333;
        max-width: 100%;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
