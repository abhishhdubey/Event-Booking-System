<?php
session_start();
require_once 'config/config.php';

$pageTitle = 'Movies - BookYourShow';

// Filters
$genre  = isset($_GET['genre']) ? $conn->real_escape_string($_GET['genre']) : '';
$lang   = isset($_GET['lang'])  ? $conn->real_escape_string($_GET['lang'])  : '';
$search = isset($_GET['q'])     ? $conn->real_escape_string($_GET['q'])     : '';

// City filter from session
$selectedCity = $_SESSION['selected_city'] ?? '';

$where = "WHERE 1=1";
if ($genre)  $where .= " AND genre LIKE '%$genre%'";
if ($lang)   $where .= " AND language = '$lang'";
if ($search) $where .= " AND title LIKE '%$search%'";

// If city selected, filter to movies showing in that city
if ($selectedCity) {
    $where .= " AND m.movie_id IN (
        SELECT DISTINCT s.movie_id FROM shows s
        JOIN theatres t ON s.theatre_id = t.theatre_id
        WHERE t.city = '$selectedCity' AND t.licence_status = 'approved' AND s.status = 'active'
    )";
    $movies = $conn->query("SELECT m.* FROM movies m $where ORDER BY m.rating DESC");
} else {
    $movies = $conn->query("SELECT * FROM movies $where ORDER BY rating DESC");
}

$genres = $conn->query("SELECT DISTINCT genre FROM movies");
$langs  = $conn->query("SELECT DISTINCT language FROM movies");

// Helper: remaining seats for a movie across all upcoming shows
function getMovieSeatsInfo($conn, $movieId) {
    $mid = (int)$movieId;
    // Total seats created in the seats table for this movie's shows
    $total = $conn->query("SELECT COUNT(*) as c FROM seats s JOIN shows sh ON s.show_id = sh.show_id JOIN theatres t ON sh.theatre_id = t.theatre_id WHERE sh.movie_id = $mid AND t.licence_status = 'approved' AND sh.status = 'active'")->fetch_assoc()['c'];
    $booked = $conn->query("SELECT COUNT(*) as c FROM seats s JOIN shows sh ON s.show_id = sh.show_id JOIN theatres t ON sh.theatre_id = t.theatre_id WHERE sh.movie_id = $mid AND s.status = 'booked' AND t.licence_status = 'approved' AND sh.status = 'active'")->fetch_assoc()['c'];
    if ($total == 0) {
        // Fallback: count from bookings table (seats are comma-separated strings)
        $bRes = $conn->query("SELECT seats FROM bookings b JOIN shows sh ON b.show_id = sh.show_id JOIN theatres t ON sh.theatre_id = t.theatre_id WHERE sh.movie_id = $mid AND t.licence_status = 'approved' AND sh.status = 'active'");
        $bookedCount = 0;
        while ($br = $bRes->fetch_assoc()) {
            $bookedCount += count(array_filter(explode(',', $br['seats'])));
        }
        // Assume 80 seats per show, count shows
        $showCount = $conn->query("SELECT COUNT(*) as c FROM shows sh JOIN theatres t ON sh.theatre_id = t.theatre_id WHERE sh.movie_id = $mid AND t.licence_status = 'approved' AND sh.status = 'active'")->fetch_assoc()['c'];
        $total = $showCount * 80;
        $booked = $bookedCount;
    }
    $remaining = max(0, $total - $booked);
    return ['total' => $total, 'remaining' => $remaining];
}

include 'includes/header.php';
?>

<section class="page-hero">
    <h1>🎬 Movies</h1>
    <p>Book tickets for the latest blockbusters<?php echo $selectedCity ? ' in ' . htmlspecialchars($selectedCity) : ''; ?></p>
</section>

<div class="container" style="padding-top:40px;">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="index.php">Home</a><span class="separator">›</span><span>Movies</span>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <span style="font-weight:600;color:var(--text-muted);">🎯 Filter:</span>
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;flex:1;">
            <input type="text" name="q" placeholder="Search movies..." value="<?php echo htmlspecialchars($search); ?>" id="movieSearch" oninput="filterMovies()" style="flex:1;min-width:150px;background:var(--bg-dark);border:1px solid var(--border);color:var(--text-light);padding:9px 14px;border-radius:8px;outline:none;">
            <select name="genre" style="background:var(--bg-dark);border:1px solid var(--border);color:var(--text-light);padding:9px 12px;border-radius:8px;outline:none;">
                <option value="">All Genres</option>
                <?php while($g = $genres->fetch_assoc()): $gs = explode(',',$g['genre']); foreach($gs as $gi): $gi=trim($gi); if($gi): ?>
                <option value="<?php echo htmlspecialchars($gi); ?>" <?php if($genre==$gi) echo 'selected'; ?>><?php echo htmlspecialchars($gi); ?></option>
                <?php endif; endforeach; endwhile; ?>
            </select>
            <select name="lang" style="background:var(--bg-dark);border:1px solid var(--border);color:var(--text-light);padding:9px 12px;border-radius:8px;outline:none;">
                <option value="">All Languages</option>
                <?php while($l = $langs->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($l['language']); ?>" <?php if($lang==$l['language']) echo 'selected'; ?>><?php echo htmlspecialchars($l['language']); ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            <a href="movies.php" class="btn btn-dark btn-sm">Reset</a>
        </form>
    </div>

    <p style="color:var(--text-muted);margin-bottom:20px;font-size:0.9rem;">Showing <?php echo $movies->num_rows; ?> movie(s)</p>

    <!-- Movies Grid -->
    <div class="movies-grid" id="moviesGrid">
        <?php if ($movies->num_rows === 0): ?>
        <div style="grid-column:1/-1;text-align:center;padding:60px;color:var(--text-muted);">
            <div style="font-size:3rem;margin-bottom:16px;">🎬</div>
            <p>No movies found. Try different filters.</p>
            <a href="movies.php" class="btn btn-primary" style="margin-top:16px;">View All Movies</a>
        </div>
        <?php else: while ($movie = $movies->fetch_assoc()):
            $genres_arr = explode(',', $movie['genre']);
            $g1 = trim($genres_arr[0]);
            $g2 = isset($genres_arr[1]) ? trim($genres_arr[1]) : '';
            $seats = getMovieSeatsInfo($conn, $movie['movie_id']);
            $remaining = $seats['remaining'];
            // Seat badge style
            if ($remaining > 50) {
                $seatColor = '#22c55e'; $seatBg = 'rgba(34,197,94,0.12)'; $seatIcon = '🎟️';
            } elseif ($remaining > 20) {
                $seatColor = '#f59e0b'; $seatBg = 'rgba(245,158,11,0.12)'; $seatIcon = '⚡';
            } else {
                $seatColor = '#ef4444'; $seatBg = 'rgba(239,68,68,0.12)'; $seatIcon = '🔥';
            }
        ?>
        <!-- Full card is clickable link -->
        <a href="movie-details.php?id=<?php echo $movie['movie_id']; ?>" class="movie-card movie-card-link">
            <div class="movie-poster" style="position:relative;overflow:hidden;">
                <?php $hasPosterM = !empty($movie['poster']) && file_exists('assets/images/movies/' . $movie['poster']); ?>
                <div class="movie-poster-img" style="<?php echo $hasPosterM ? 'font-size:0;' : ''; ?>">
                    <?php if ($hasPosterM): ?>
                    <img src="<?php echo BASE_URL; ?>assets/images/movies/<?php echo htmlspecialchars($movie['poster']); ?>" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:inherit;" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                    <?php else: ?>🎬<?php endif; ?>
                </div>
                <div class="movie-rating-badge" style="position:absolute;top:8px;left:8px;transform:none;">⭐ <?php echo $movie['rating']; ?></div>
                <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST" action="wishlist.php" style="display:inline;" onclick="event.stopPropagation()">
                    <input type="hidden" name="movie_id" value="<?php echo $movie['movie_id']; ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="redirect" value="movies.php">
                    <button type="submit" class="wishlist-btn" title="Add to Wishlist">♥</button>
                </form>
                <?php endif; ?>
            </div>
            <div class="movie-info">
                <div class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></div>
                <div class="movie-meta">
                    <span class="movie-tag"><?php echo htmlspecialchars($g1); ?></span>
                    <?php if ($g2): ?><span class="movie-tag" style="background:rgba(99,102,241,0.15);color:#a5b4fc;"><?php echo htmlspecialchars($g2); ?></span><?php endif; ?>
                    <span class="movie-lang"><?php echo htmlspecialchars($movie['language']); ?></span>
                </div>
                <!-- Quick details: duration + seats -->
                <div class="movie-quick-details">
                    <span>🕐 <?php echo htmlspecialchars($movie['duration']); ?></span>
                    <span class="seats-badge" style="background:<?php echo $seatBg; ?>;color:<?php echo $seatColor; ?>;padding:2px 8px;border-radius:50px;font-weight:600;font-size:0.72rem;">
                        <?php echo $seatIcon; ?> <?php echo $remaining; ?> seats left
                    </span>
                </div>
            </div>
            <div class="movie-actions">
                <span class="btn btn-primary btn-book">Book Tickets</span>
            </div>
        </a>
        <?php endwhile; endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

