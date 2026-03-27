<?php
session_start();
require_once 'config/config.php';

$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
$city     = isset($_GET['city'])     ? $conn->real_escape_string($_GET['city']) : '';
$selDate  = isset($_GET['date'])     ? $conn->real_escape_string($_GET['date']) : '';

if (!$movie_id) { header('Location: movies.php'); exit; }

$movie = $conn->query("SELECT * FROM movies WHERE movie_id = $movie_id")->fetch_assoc();
if (!$movie) { header('Location: movies.php'); exit; }

// Check if shows.status column exists (added in migration_v2.sql)
$colCheck        = $conn->query("SHOW COLUMNS FROM shows LIKE 'status'");
$hasStatusCol    = ($colCheck && $colCheck->num_rows > 0);
// With alias 's.' for JOIN queries
$statusFilter    = $hasStatusCol ? "AND (s.status = 'active' OR s.status IS NULL)" : "";
// Without alias for direct shows table queries
$statusFilterRaw = $hasStatusCol ? "AND (status = 'active' OR status IS NULL)" : "";
$dateFilter      = $selDate ? "AND s.show_date = '$selDate'" : "AND s.show_date >= CURDATE()";
$dateFilterRaw   = $selDate ? "AND show_date = '$selDate'" : "AND show_date >= CURDATE()";
$whereCity       = $city ? "AND t.city = '$city'" : '';

$theatres = $conn->query("
    SELECT DISTINCT t.*, MIN(s.price) as min_price
    FROM theatres t
    JOIN shows s ON t.theatre_id = s.theatre_id
    WHERE s.movie_id = $movie_id AND t.licence_status = 'approved' $whereCity $dateFilter $statusFilter
    GROUP BY t.theatre_id
");

// Available dates for this movie (only active shows)
$availDates = $conn->query("
    SELECT DISTINCT s.show_date FROM shows s
    JOIN theatres t ON s.theatre_id = t.theatre_id
    WHERE s.movie_id = $movie_id AND s.show_date >= CURDATE() AND t.licence_status = 'approved' $statusFilter
    ORDER BY s.show_date LIMIT 14
");

$cities_res = $conn->query("SELECT DISTINCT t.city FROM theatres t JOIN shows s ON t.theatre_id=s.theatre_id WHERE s.movie_id=$movie_id AND t.licence_status = 'approved' $statusFilter AND s.show_date>=CURDATE() ORDER BY t.city");

$pageTitle = 'Select Theatre - ' . htmlspecialchars($movie['title']);
include 'includes/header.php';
?>

<div class="container" style="padding-top:40px;">
    <div class="breadcrumb">
        <a href="index.php">Home</a> <span class="separator">›</span>
        <a href="movies.php">Movies</a> <span class="separator">›</span>
        <a href="movie-details.php?id=<?php echo $movie_id; ?>"><?php echo htmlspecialchars($movie['title']); ?></a> <span class="separator">›</span>
        <span>Select Theatre</span>
    </div>

    <h2 class="section-title" style="margin-bottom:6px;">Select a Theatre</h2>
    <p class="section-subtitle">Showing theatres for: <strong><?php echo htmlspecialchars($movie['title']); ?></strong></p>

    <!-- Filters Row -->
    <form method="GET" id="filterForm" style="margin-bottom:24px;">
        <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">

        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:14px;">
            <!-- City -->
            <select name="city" onchange="document.getElementById('filterForm').submit()" class="form-control" style="width:auto;min-width:160px;">
                <option value="">🌆 All Cities</option>
                <?php while($c = $cities_res->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($c['city']); ?>" <?php if($city==$c['city']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($c['city']); ?>
                </option>
                <?php endwhile; ?>
            </select>

            <!-- Date picker -->
            <div style="position:relative;">
                <input type="date"
                    name="date"
                    id="datePicker"
                    class="form-control"
                    value="<?php echo htmlspecialchars($selDate); ?>"
                    min="<?php echo date('Y-m-d'); ?>"
                    onchange="document.getElementById('filterForm').submit()"
                    style="width:180px;cursor:pointer;"
                    placeholder="📅 Pick Date">
            </div>

            <!-- Reset -->
            <a href="theatres.php?movie_id=<?php echo $movie_id; ?>" class="btn btn-dark btn-sm">🔄 Reset</a>
        </div>

        <!-- Quick date tab buttons -->
        <?php if($availDates && $availDates->num_rows > 0): ?>
        <div style="display:flex;gap:8px;flex-wrap:wrap;padding-bottom:8px;">
            <a href="theatres.php?movie_id=<?php echo $movie_id; ?>&city=<?php echo urlencode($city); ?>"
               class="btn btn-sm <?php echo !$selDate?'btn-primary':'btn-dark'; ?>"
               style="font-size:.8rem;padding:6px 14px;">All Dates</a>
            <?php $availDates->data_seek(0); while($ad=$availDates->fetch_assoc()):
                $isActive = $selDate === $ad['show_date'];
            ?>
            <a href="theatres.php?movie_id=<?php echo $movie_id; ?>&city=<?php echo urlencode($city); ?>&date=<?php echo $ad['show_date']; ?>"
               class="btn btn-sm <?php echo $isActive?'btn-primary':'btn-dark'; ?>"
               style="font-size:.8rem;padding:6px 14px;">
               <?php echo date('D, d M', strtotime($ad['show_date'])); ?>
            </a>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </form>

    <?php if($selDate): ?>
    <div style="background:rgba(248,68,100,.08);border:1px solid rgba(248,68,100,.2);border-radius:8px;padding:10px 16px;margin-bottom:18px;font-size:.9rem;color:var(--primary);">
        📅 Showing shows for: <strong><?php echo date('l, d F Y', strtotime($selDate)); ?></strong>
    </div>
    <?php endif; ?>

    <!-- Theatre List -->
    <?php if (!$theatres || $theatres->num_rows === 0): ?>
    <div style="text-align:center;padding:60px;color:var(--text-muted);">
        <div style="font-size:3rem;margin-bottom:16px;">🏟️</div>
        <p><?php echo $selDate ? 'No shows available on ' . date('d M Y', strtotime($selDate)) . '.' : 'No theatres found for this movie in selected city.'; ?></p>
        <?php if($selDate): ?><a href="theatres.php?movie_id=<?php echo $movie_id; ?>" class="btn btn-primary" style="margin-top:12px;">View All Dates</a><?php endif; ?>
    </div>

    <?php else: while($theatre = $theatres->fetch_assoc()):
        // Use custom map_link if set, else fallback to Google search embed
        $mapSrc = !empty($theatre['map_link'])
            ? 'https://maps.google.com/maps?q=' . urlencode($theatre['map_link']) . '&output=embed'
            : 'https://maps.google.com/maps?q=' . urlencode($theatre['theatre_name'] . ' ' . $theatre['city']) . '&output=embed';
    ?>
    <div class="theatre-card">
        <div class="theatre-header" style="display:flex;justify-content:space-between;gap:20px;align-items:flex-start;">
            <div style="flex:1;">
                <div class="theatre-name">🏟️ <?php echo htmlspecialchars($theatre['theatre_name']); ?></div>
                <div class="theatre-location">📍 <?php echo htmlspecialchars($theatre['city'] . ' — ' . $theatre['location']); ?></div>
                <div style="margin-top:6px;color:var(--primary);font-size:0.85rem;">Starting from ₹<?php echo number_format($theatre['min_price'], 0); ?></div>
            </div>
            <?php if(!empty($theatre['map_link'])): ?>
            <a href="<?php echo htmlspecialchars($theatre['map_link']); ?>" target="_blank" style="flex-shrink:0;" title="Open in Maps">
            <?php endif; ?>
            <div style="width:140px;height:90px;border-radius:8px;overflow:hidden;border:1px solid var(--border);flex-shrink:0;background:var(--bg-dark);">
                <iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"
                    src="<?php echo $mapSrc; ?>">
                </iframe>
            </div>
            <?php if(!empty($theatre['map_link'])): ?></a><?php endif; ?>
        </div>

        <!-- Shows: filter by selected date if set, else show next 5 dates -->
        <?php
        if ($selDate) {
            // Only show selected date
            $dates_q = $conn->query("
                SELECT DISTINCT show_date FROM shows
                WHERE movie_id = $movie_id
                  AND theatre_id = {$theatre['theatre_id']}
                  AND show_date = '$selDate'
                  $statusFilterRaw
                ORDER BY show_date
            ");
        } else {
            $dates_q = $conn->query("
                SELECT DISTINCT show_date FROM shows
                WHERE movie_id = $movie_id
                  AND theatre_id = {$theatre['theatre_id']}
                  AND show_date >= CURDATE()
                  $statusFilterRaw
                ORDER BY show_date LIMIT 5
            ");
        }

        while($date = $dates_q->fetch_assoc()):
        ?>
        <div style="margin-bottom:16px;">
            <p style="color:var(--text-muted);font-size:0.8rem;font-weight:600;text-transform:uppercase;margin-bottom:8px;">
                📅 <?php echo date('D, d M Y', strtotime($date['show_date'])); ?>
            </p>
            <div class="show-times" style="flex-wrap:wrap;gap:12px;">
                <?php
                $shows = $conn->query("
                    SELECT * FROM shows
                    WHERE movie_id = $movie_id
                      AND theatre_id = {$theatre['theatre_id']}
                      AND show_date = '{$date['show_date']}'
                      $statusFilterRaw
                    ORDER BY show_time
                ");
                while($show = $shows->fetch_assoc()):
                    $lang = $show['language'] ?? 'Hindi';
                    $langColor = match(strtolower($lang)) {
                        'english'   => '#3b82f6',
                        'tamil'     => '#a855f7',
                        'telugu'    => '#ec4899',
                        'kannada'   => '#f97316',
                        'malayalam' => '#14b8a6',
                        default     => '#ef4444'
                    };
                ?>
                <a href="seat-selection.php?show_id=<?php echo $show['show_id']; ?>" class="show-time-btn"
                   style="display:flex;flex-direction:column;align-items:center;gap:4px;padding:10px 16px;min-width:100px;text-decoration:none;">
                    <span style="background:<?php echo $langColor; ?>22;color:<?php echo $langColor; ?>;padding:2px 8px;border-radius:4px;font-size:.68rem;font-weight:700;letter-spacing:.5px;">
                        <?php echo htmlspecialchars(strtoupper($lang)); ?>
                    </span>
                    <span style="font-weight:700;font-size:.95rem;"><?php echo date('h:i A', strtotime($show['show_time'])); ?></span>
                    <small style="color:var(--text-muted);font-size:.72rem;">₹<?php echo number_format($show['price'], 0); ?></small>
                </a>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endwhile; endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
