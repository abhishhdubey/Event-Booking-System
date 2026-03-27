<?php
session_start();
require_once 'config/config.php';

$q    = isset($_GET['q']) ? $conn->real_escape_string(trim($_GET['q'])) : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$city = isset($_GET['city']) ? $conn->real_escape_string(trim($_GET['city'])) : '';

$movies = $events = [];

if ($q) {
    if (!$type || $type === 'movie') {
        $res = $conn->query("SELECT * FROM movies WHERE title LIKE '%$q%' OR genre LIKE '%$q%' OR language LIKE '%$q%' ORDER BY rating DESC");
        while($m = $res->fetch_assoc()) $movies[] = $m;
    }
    if (!$type || $type === 'event') {
        $cityWhere = $city ? "AND location LIKE '%$city%'" : '';
        $res = $conn->query("SELECT * FROM events WHERE event_name LIKE '%$q%' OR location LIKE '%$q%' $cityWhere ORDER BY event_date ASC");
        while($e = $res->fetch_assoc()) $events[] = $e;
    }
}

$pageTitle = 'Search - BookYourShow';
include 'includes/header.php';
?>
<div class="container" style="padding:40px 15px;">
    <h2 class="section-title" style="margin-bottom:20px;">🔍 Search</h2>

    <form method="GET" style="display:grid;grid-template-columns:1fr auto auto auto;gap:10px;margin-bottom:40px;max-width:800px;">
        <input type="text" name="q" placeholder="Search movies, events, genres..." value="<?php echo htmlspecialchars($q); ?>" class="form-control" required>
        <select name="type" class="form-control" style="width:auto;">
            <option value="">All</option>
            <option value="movie" <?php if($type=='movie') echo 'selected'; ?>>Movies</option>
            <option value="event" <?php if($type=='event') echo 'selected'; ?>>Events</option>
        </select>
        <input type="text" name="city" placeholder="City (optional)" value="<?php echo htmlspecialchars($city); ?>" class="form-control" style="min-width:140px;">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <?php if (!$q): ?>
    <p style="color:var(--text-muted);text-align:center;padding:40px;">Enter a keyword above to search for movies or events.</p>
    <?php else: ?>
    <p style="color:var(--text-muted);margin-bottom:24px;">Results for "<strong style="color:var(--text-light);"><?php echo htmlspecialchars($q); ?></strong>" — <?php echo count($movies); ?> movie(s), <?php echo count($events); ?> event(s)</p>

    <?php if ($movies): ?>
    <h3 style="margin-bottom:20px;">🎬 Movies</h3>
    <div class="movies-grid" style="margin-bottom:40px;">
        <?php foreach($movies as $movie): ?>
        <div class="movie-card">
            <div class="movie-poster">
                <div style="aspect-ratio:2/3;background:linear-gradient(135deg,#1a1a2e,#2e2e4e);display:flex;align-items:center;justify-content:center;font-size:3rem;">🎬</div>
                <div class="movie-rating-badge">⭐ <?php echo $movie['rating']; ?></div>
                <div class="movie-overlay"><a href="movie-details.php?id=<?php echo $movie['movie_id']; ?>" class="btn btn-primary btn-sm">Book Now</a></div>
            </div>
            <div class="movie-info">
                <div class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></div>
                <div class="movie-meta"><span class="movie-tag"><?php echo htmlspecialchars(explode(',',$movie['genre'])[0]); ?></span><span class="movie-lang"><?php echo htmlspecialchars($movie['language']); ?></span></div>
            </div>
            <div class="movie-actions"><a href="movie-details.php?id=<?php echo $movie['movie_id']; ?>" class="btn btn-primary btn-book">Book Tickets</a></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($events): ?>
    <h3 style="margin-bottom:20px;">🎪 Events</h3>
    <div class="events-grid">
        <?php $bgs=['#1a0032','#00172d','#1a1400','#001a12']; $ei=0; foreach($events as $ev): ?>
        <div class="event-card">
            <div class="event-image"><div style="width:100%;height:100%;background:linear-gradient(135deg,<?php echo $bgs[$ei%4]; ?>,#0f0f1a);display:flex;align-items:center;justify-content:center;font-size:4rem;">🎪</div><span class="event-category">Live Event</span></div>
            <div class="event-info">
                <h3 class="event-title"><?php echo htmlspecialchars($ev['event_name']); ?></h3>
                <div class="event-detail"><span class="icon">📅</span><?php echo date('d M Y', strtotime($ev['event_date'])); ?></div>
                <div class="event-detail"><span class="icon">📍</span><?php echo htmlspecialchars($ev['location']); ?></div>
                <div class="event-price"><span class="event-price-text">₹<?php echo number_format($ev['ticket_price'],0); ?></span><a href="event-details.php?id=<?php echo $ev['event_id']; ?>" class="btn btn-primary btn-sm">Book</a></div>
            </div>
        </div>
        <?php $ei++; endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!$movies && !$events): ?>
    <div style="text-align:center;padding:60px;color:var(--text-muted);"><div style="font-size:3rem;">🔍</div><p>No results found for "<?php echo htmlspecialchars($q); ?>"</p></div>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>

