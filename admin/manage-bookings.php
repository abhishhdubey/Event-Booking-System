<?php
require_once 'admin-helper.php';

$bookings = $conn->query("
    SELECT b.*, u.name as user_name, m.title, t.theatre_name, s.show_date, s.show_time
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN shows s ON b.show_id = s.show_id
    JOIN movies m ON s.movie_id = m.movie_id
    JOIN theatres t ON s.theatre_id = t.theatre_id
    ORDER BY b.booking_date DESC
");

$content = '<div class="table-wrapper">
<div class="table-header"><h4 class="table-title">🎟️ All Bookings</h4></div>
<table>
<thead><tr><th>Booking ID</th><th>User</th><th>Movie</th><th>Theatre</th><th>Show</th><th>Seats</th><th>Amount</th><th>Status</th></tr></thead>
<tbody>';

while ($b = $bookings->fetch_assoc()) {
    $statusBadge = 'badge-success';
    $content .= '<tr>
    <td style="font-weight:600;">#BMS' . str_pad($b['booking_id'],6,'0',STR_PAD_LEFT) . '</td>
    <td>' . htmlspecialchars($b['user_name']) . '</td>
    <td>' . htmlspecialchars($b['title']) . '</td>
    <td style="color:var(--text-muted);font-size:0.85rem;">' . htmlspecialchars($b['theatre_name']) . '</td>
    <td style="color:var(--text-muted);font-size:0.82rem;">' . date('d M Y', strtotime($b['show_date'])) . '<br>' . date('h:i A', strtotime($b['show_time'])) . '</td>
    <td style="font-size:0.85rem;">' . htmlspecialchars($b['seats']) . '</td>
    <td style="font-weight:600;color:var(--primary);">₹' . number_format($b['total_price'],2) . '</td>
    <td><span class="badge badge-success">' . ucfirst($b['booking_status']) . '</span></td>
    </tr>';
}
$content .= '</tbody></table></div>';
adminLayout('Manage Bookings', $content, 'admin/manage-bookings.php');
?>
