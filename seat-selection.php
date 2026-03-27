<?php
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$show_id = isset($_GET['show_id']) ? (int)$_GET['show_id'] : 0;
if (!$show_id) { header('Location: movies.php'); exit; }

// Get show with movie & theatre details
$show = $conn->query("
    SELECT s.*, m.title, m.language, m.genre, m.duration, t.theatre_name, t.location, t.city
    FROM shows s
    JOIN movies m ON s.movie_id = m.movie_id
    JOIN theatres t ON s.theatre_id = t.theatre_id
    WHERE s.show_id = $show_id
")->fetch_assoc();

if (!$show) { header('Location: movies.php'); exit; }

// Get booked seat IDs for this show
$bookedSeats = [];
$bookings = $conn->query("SELECT seats FROM bookings WHERE show_id = $show_id");
while ($b = $bookings->fetch_assoc()) {
    $seats = explode(',', $b['seats']);
    foreach ($seats as $s) {
        $bookedSeats[] = trim($s);
    }
}

// Generate seat layout
$rows = ['A','B','C','D','E','F','G','H'];
$seatsPerRow = 10;

$pageTitle = 'Select Seats - ' . htmlspecialchars($show['title']);
include 'includes/header.php';
?>

<div class="container" style="padding-top:40px;">
    <div class="breadcrumb">
        <a href="index.php">Home</a> <span class="separator">›</span>
        <a href="movies.php">Movies</a> <span class="separator">›</span>
        <a href="movie-details.php?id=<?php echo $show['movie_id']; ?>"><?php echo htmlspecialchars($show['title']); ?></a> <span class="separator">›</span>
        <span>Select Seats</span>
    </div>

    <!-- Show Info -->
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:30px;display:flex;gap:24px;flex-wrap:wrap;justify-content:space-between;align-items:center;">
        <div>
            <h2 style="font-size:1.3rem;font-weight:700;"><?php echo htmlspecialchars($show['title']); ?></h2>
            <p style="color:var(--text-muted);font-size:0.9rem;">
                🏟️ <?php echo htmlspecialchars($show['theatre_name']); ?> &nbsp;|&nbsp;
                📅 <?php echo date('D, d M Y', strtotime($show['show_date'])); ?> &nbsp;|&nbsp;
                🕐 <?php echo date('h:i A', strtotime($show['show_time'])); ?>
            </p>
        </div>
        <div style="text-align:right;">
            <div style="font-size:1.2rem;font-weight:700;color:var(--primary);">₹<?php echo $show['price']; ?>/seat</div>
            <div style="color:var(--text-muted);font-size:0.8rem;"><?php echo htmlspecialchars($show['language']); ?></div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:24px;">
        <!-- Seat Map -->
        <div>
            <div class="seat-map-wrapper">
                <div class="screen-label">
                    <div class="screen-visual"></div>
                    <p class="screen-text">🎬 SCREEN THIS SIDE</p>
                </div>

                <form method="POST" action="booking-confirm.php" id="seatForm">
                    <input type="hidden" name="show_id" value="<?php echo $show_id; ?>">
                    <input type="hidden" name="selected_seats" id="selectedSeatsInput" value="">
                    <input type="hidden" id="seatPrice" value="<?php echo $show['price']; ?>">

                    <div class="seat-area">
                        <!-- Premium Section (Rows A-B) -->
                        <div class="seat-section-label">🌟 Premium — ₹<?php echo $show['price']; ?></div>
                        <?php foreach(['A','B'] as $row): ?>
                        <div class="seat-row">
                            <span class="row-label"><?php echo $row; ?></span>
                            <?php
                            for ($n = 1; $n <= 5; $n++) {
                                $seatNum = $row . $n;
                                $booked = in_array($seatNum, $bookedSeats);
                                $cls = $booked ? 'booked' : 'available';
                                echo "<div class='seat $cls' data-seat='$seatNum'>$n</div>";
                            }
                            echo "<div class='seat-gap'></div>";
                            for ($n = 6; $n <= 10; $n++) {
                                $seatNum = $row . $n;
                                $booked = in_array($seatNum, $bookedSeats);
                                $cls = $booked ? 'booked' : 'available';
                                echo "<div class='seat $cls' data-seat='$seatNum'>$n</div>";
                            }
                            ?>
                        </div>
                        <?php endforeach; ?>

                        <!-- Normal Section (Rows C-F) -->
                        <div class="seat-section-label" style="margin-top:12px;">🎭 Executive</div>
                        <?php foreach(['C','D','E','F'] as $row): ?>
                        <div class="seat-row">
                            <span class="row-label"><?php echo $row; ?></span>
                            <?php
                            for ($n = 1; $n <= 5; $n++) {
                                $seatNum = $row . $n;
                                $booked = in_array($seatNum, $bookedSeats);
                                $cls = $booked ? 'booked' : 'available';
                                echo "<div class='seat $cls' data-seat='$seatNum'>$n</div>";
                            }
                            echo "<div class='seat-gap'></div>";
                            for ($n = 6; $n <= 10; $n++) {
                                $seatNum = $row . $n;
                                $booked = in_array($seatNum, $bookedSeats);
                                $cls = $booked ? 'booked' : 'available';
                                echo "<div class='seat $cls' data-seat='$seatNum'>$n</div>";
                            }
                            ?>
                        </div>
                        <?php endforeach; ?>

                        <!-- Economy (Rows G-H) -->
                        <div class="seat-section-label" style="margin-top:12px;">💺 Economy</div>
                        <?php foreach(['G','H'] as $row): ?>
                        <div class="seat-row">
                            <span class="row-label"><?php echo $row; ?></span>
                            <?php
                            for ($n = 1; $n <= 5; $n++) {
                                $seatNum = $row . $n;
                                $booked = in_array($seatNum, $bookedSeats);
                                $cls = $booked ? 'booked' : 'available';
                                echo "<div class='seat $cls' data-seat='$seatNum'>$n</div>";
                            }
                            echo "<div class='seat-gap'></div>";
                            for ($n = 6; $n <= 10; $n++) {
                                $seatNum = $row . $n;
                                $booked = in_array($seatNum, $bookedSeats);
                                $cls = $booked ? 'booked' : 'available';
                                echo "<div class='seat $cls' data-seat='$seatNum'>$n</div>";
                            }
                            ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Legend -->
                    <div class="seat-legend">
                        <div class="legend-item"><div class="legend-box available"></div> Available</div>
                        <div class="legend-item"><div class="legend-box selected"></div> Selected</div>
                        <div class="legend-item"><div class="legend-box booked"></div> Booked</div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary -->
        <div>
            <div class="booking-summary-sticky">
                <h4 style="margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border);">🎟️ Booking Summary</h4>
                <div style="margin-bottom:14px;">
                    <div style="color:var(--text-muted);font-size:0.8rem;">Movie</div>
                    <div style="font-weight:600;"><?php echo htmlspecialchars($show['title']); ?></div>
                </div>
                <div style="margin-bottom:14px;">
                    <div style="color:var(--text-muted);font-size:0.8rem;">Theatre</div>
                    <div style="font-weight:500;font-size:0.9rem;"><?php echo htmlspecialchars($show['theatre_name']); ?></div>
                </div>
                <div style="margin-bottom:14px;">
                    <div style="color:var(--text-muted);font-size:0.8rem;">Date & Time</div>
                    <div style="font-weight:500;font-size:0.9rem;"><?php echo date('D d M, h:i A', strtotime($show['show_date'] . ' ' . $show['show_time'])); ?></div>
                </div>
                <div style="margin-bottom:14px;padding-top:14px;border-top:1px solid var(--border);">
                    <div style="color:var(--text-muted);font-size:0.8rem;">Selected Seats</div>
                    <div id="selectedSeatsSummary" style="font-weight:600;color:var(--primary);">None</div>
                </div>
                <div style="margin-bottom:14px;">
                    <div style="color:var(--text-muted);font-size:0.8rem;">Count</div>
                    <div><span id="selectedCount">0</span> seat(s)</div>
                </div>
                <div style="border-top:2px solid var(--border);padding-top:14px;margin-top:14px;">
                    <div style="display:flex;justify-content:space-between;font-size:1.1rem;font-weight:700;">
                        <span>Total</span>
                        <span id="totalPrice" style="color:var(--primary);">₹0.00</span>
                    </div>
                </div>
                <button type="submit" form="seatForm" class="btn btn-primary" style="width:100%;margin-top:16px;justify-content:center;" onclick="return checkSeats()">
                    Continue →
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function checkSeats() {
    if (selectedSeats.length === 0) {
        showToast('Please select at least one seat', 'error');
        return false;
    }
    return true;
}
</script>

<?php include 'includes/footer.php'; ?>

