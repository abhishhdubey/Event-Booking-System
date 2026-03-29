<?php
session_start();
require_once 'config/config.php';

$pageTitle = 'Events - BookYourShow';
$search = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';
$category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$selectedCity = $_SESSION['selected_city'] ?? '';

$where = "WHERE 1=1";
if ($search)      $where .= " AND e.event_name LIKE '%$search%'";
if ($category)    $where .= " AND e.category = '$category'";
if ($selectedCity) $where .= " AND (e.location LIKE '%" . $conn->real_escape_string($selectedCity) . "%' OR e.city LIKE '%" . $conn->real_escape_string($selectedCity) . "%')";

// Guard: show events that are approved OR have no status (legacy data)
$where .= " AND (e.status = 'approved' OR e.status IS NULL OR e.status = '')";

$events = $conn->query("SELECT e.*, o.name as organizer FROM events e LEFT JOIN operators o ON e.organizer_id = o.operator_id $where ORDER BY e.event_date ASC");

// Helper: remaining seats for an event
function getEventSeats($conn, $eventId) {
    $eid = (int)$eventId;
    $row = $conn->query("SELECT total_seats FROM events WHERE event_id = $eid")->fetch_assoc();
    $total = isset($row['total_seats']) && $row['total_seats'] ? (int)$row['total_seats'] : 500;
    $booked = $conn->query("SELECT SUM(tickets) as t FROM event_bookings WHERE event_id = $eid")->fetch_assoc()['t'] ?? 0;
    return max(0, $total - (int)$booked);
}

include 'includes/header.php';
?>
<section class="page-hero">
    <h1>🎪 Events</h1>
    <p>Concerts, Sports, Comedy, Festivals &amp; More<?php echo $selectedCity ? ' in ' . htmlspecialchars($selectedCity) : ''; ?></p>
</section>
<div class="container" style="padding:40px 15px;">
    <form method="GET" style="display:flex;gap:10px;margin-bottom:20px;max-width:600px;">
        <?php if($category): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>"><?php endif; ?>
        <input type="text" name="q" placeholder="Search events..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if($search || $category): ?><a href="events.php" class="btn btn-dark">Reset</a><?php endif; ?>
    </form>

    <div style="display:flex;gap:10px;margin-bottom:30px;flex-wrap:wrap;">
        <?php 
        $qParam = $search ? '&q=' . urlencode($search) : '';
        $allActive = empty($category) ? 'background:linear-gradient(135deg, var(--primary), #ff7e5f);color:#fff;border:1px solid transparent;box-shadow:0 4px 15px rgba(248,68,100,0.3);font-weight:700;' : 'background:rgba(255,255,255,0.03);color:var(--text);border:1px solid var(--border);';
        ?>
        <a href="events.php<?php echo $search ? '?q='.urlencode($search) : ''; ?>" class="btn" style="border-radius:50px; padding:6px 18px; font-size:0.95rem; text-decoration:none; transition:0.3s; <?php echo $allActive; ?>">🌟 All Events</a>
        <?php 
        $cats = ['Comedy','Sports','Music','Festivals & Fairs','College Fests','Workshops','Parties','Gaming & Esports'];
        foreach($cats as $cat):
            $active = ($category === $cat) ? 'background:var(--primary);color:#fff;border:1px solid var(--primary);box-shadow:0 4px 10px rgba(248,68,100,0.25);' : 'background:transparent;color:var(--text);border:1px solid var(--border);';
        ?>
        <a href="?category=<?php echo urlencode($cat) . $qParam; ?>" class="btn" style="border-radius:50px; padding:6px 14px; font-size:0.9rem; text-decoration:none; transition:0.3s; <?php echo $active; ?>"><?php echo htmlspecialchars($cat); ?></a>
        <?php endforeach; ?>
    </div>

    <?php if(!$events || $events->num_rows === 0): ?>
    <div style="text-align:center;padding:60px;color:var(--text-muted);">
        <div style="font-size:3rem;">🎪</div>
        <p>No events found<?php echo $selectedCity ? ' in ' . htmlspecialchars($selectedCity) : ''; ?>.</p>
        <?php if($selectedCity): ?>
        <a href="events.php" class="btn btn-primary" style="margin-top:16px;">Show All Cities</a>
        <?php endif; ?>
    </div>
    <?php else: ?>

    <!-- Same grid as movies -->
    <div class="movies-grid">
        <?php
        $bgs    = ['#1a0032','#00172d','#1a1400','#001a12','#1a0010','#003322'];
        $emojis = ['🎵','🏏','😂','🎸','🎭','🎪'];
        $i = 0;
        while($ev = $events->fetch_assoc()):
            $remaining = getEventSeats($conn, $ev['event_id']);
            if ($remaining > 100)    { $sc='#22c55e'; $sb='rgba(34,197,94,0.12)';  $si='🎟️'; }
            elseif ($remaining > 30) { $sc='#f59e0b'; $sb='rgba(245,158,11,0.12)'; $si='⚡'; }
            else                      { $sc='#ef4444'; $sb='rgba(239,68,68,0.12)';  $si='🔥'; }
            $imagePath = !empty($ev['event_image']) ? $ev['event_image'] : ($ev['poster'] ?? '');
        ?>
        <!-- Event card — exactly like movie-card -->
        <a href="event-details.php?id=<?php echo $ev['event_id']; ?>" class="movie-card movie-card-link">
            <!-- Portrait poster — same as movie -->
            <div class="movie-poster">
                <?php if (!empty($imagePath) && file_exists('assets/images/events/' . $imagePath)): ?>
                <img src="<?php echo BASE_URL; ?>assets/images/events/<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($ev['event_name']); ?>">
                <?php else: ?>
                <div style="width:100%;height:100%;background:linear-gradient(135deg,<?php echo $bgs[$i%6]; ?>,#0f0f1a);display:flex;align-items:center;justify-content:center;font-size:4rem;"><?php echo $emojis[$i%6]; ?></div>
                <?php endif; ?>
                <!-- "Live Event" badge like rating badge -->
                <div class="movie-rating-badge" style="background:var(--gradient);border-color:transparent;color:#fff;">Live</div>
            </div>
            <!-- Info below — same as movie-info -->
            <div class="movie-info">
                <div class="movie-title"><?php echo htmlspecialchars($ev['event_name']); ?></div>
                <div class="movie-meta">
                    <span class="movie-tag"><?php echo date('d M Y', strtotime($ev['event_date'])); ?></span>
                    <span class="movie-lang"><?php echo htmlspecialchars($ev['category'] ?? 'Other'); ?></span>
                </div>
                <div class="movie-quick-details" style="margin-top:6px;">
                    <span style="color:<?php echo $sc; ?>;font-size:0.72rem;font-weight:600;"><?php echo $si; ?> <?php echo $remaining; ?> seats</span>
                    <span style="color:var(--primary);font-weight:600;font-size:0.78rem;">₹<?php echo number_format($ev['ticket_price'],0); ?></span>
                </div>
            </div>
            <div class="movie-actions">
                <span class="btn btn-primary btn-book">Book Tickets</span>
            </div>
        </a>
        <?php $i++; endwhile; ?>
    </div>

    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
