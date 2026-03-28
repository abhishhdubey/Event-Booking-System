<?php
session_start();
require_once 'config/config.php';
$pageTitle = 'BookYourShow - Book Movie & Event Tickets Online';

// City filter from session
$selectedCity = isset($_SESSION['selected_city']) ? $_SESSION['selected_city'] : '';
$cityWhere    = $selectedCity ? "AND t.city = '" . $conn->real_escape_string($selectedCity) . "'" : '';

// Movies showing in current city (via shows->theatres join), fallback to all
if ($selectedCity) {
    $movies = $conn->query("
        SELECT DISTINCT m.* FROM movies m
        JOIN shows s ON m.movie_id = s.movie_id
        JOIN theatres t ON s.theatre_id = t.theatre_id
        WHERE t.city = '" . $conn->real_escape_string($selectedCity) . "' AND t.licence_status = 'approved' AND s.status = 'active'
        ORDER BY m.rating DESC LIMIT 8
    ");
    if ($movies->num_rows === 0) {
        // Fallback to all movies if none in city
        $movies = $conn->query("SELECT * FROM movies ORDER BY rating DESC LIMIT 8");
    }
} else {
    $movies = $conn->query("SELECT * FROM movies ORDER BY rating DESC LIMIT 8");
}

// Events for city
if ($selectedCity) {
    $events = $conn->query("SELECT * FROM events WHERE location LIKE '%" . $conn->real_escape_string($selectedCity) . "%' ORDER BY event_date ASC LIMIT 6");
    if ($events->num_rows === 0) {
        $events = $conn->query("SELECT * FROM events ORDER BY event_date ASC LIMIT 6");
    }
} else {
    $events = $conn->query("SELECT * FROM events ORDER BY event_date ASC LIMIT 6");
}

// Removed duplicate slider query as it is re-queried below
// Notifications
$notifs = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 3");

// Get min show price per movie for "starting from"
function getMinPrice($conn, $movieId) {
    $r = $conn->query("SELECT MIN(s.price) as p FROM shows s JOIN theatres t ON s.theatre_id = t.theatre_id WHERE s.movie_id = " . (int)$movieId . " AND t.licence_status = 'approved' AND s.status = 'active'");
    $row = $r ? $r->fetch_assoc() : null;
    return $row && $row['p'] ? '₹' . number_format($row['p'], 0) : '₹120';
}

include 'includes/header.php';
?>

<!-- Hero Slider -->
<section class="hero-slider">
    <?php
    $slideMovies = $conn->query("SELECT * FROM movies WHERE is_slider_ad = 1");
    if ($slideMovies->num_rows === 0) {
        $slideMovies = $conn->query("SELECT * FROM movies ORDER BY rating DESC LIMIT 6");
    }
    $slideCount = $slideMovies->num_rows;
    $slideIndex = 0;
    $posterColors = ['#1a1a2e','#0d1b2a','#1b0032','#001f3f'];
    while ($m = $slideMovies->fetch_assoc()):
        $isActive = $slideIndex === 0 ? 'active' : '';
    ?>
    <div class="hero-slide <?php echo $isActive; ?>">
        <?php
        $hasBanner = !empty($m['banner_image']) && file_exists(__DIR__ . '/assets/images/movies/' . $m['banner_image']);
        $bannerBg  = $hasBanner ? "url('" . BASE_URL . 'assets/images/movies/' . htmlspecialchars($m['banner_image']) . "') center/cover no-repeat" : "linear-gradient(135deg,{$posterColors[$slideIndex % 4]} 0%,#0f0f1a 100%)";
        ?>
        <div style="width:100%;height:100%;background:<?php echo $bannerBg; ?>;position:relative;">
            <div style="position:absolute;inset:0;background:linear-gradient(to right,rgba(0,0,0,0.82) 0%,rgba(0,0,0,0.45) 55%,rgba(0,0,0,0.1) 100%);"></div>
        </div>
        <div class="hero-content">
            <span class="hero-tag">⭐ Top Rated</span>
            <h1 class="hero-title"><?php echo htmlspecialchars($m['title']); ?></h1>
            <div class="hero-meta">
                <span>⭐ <?php echo $m['rating']; ?>/10</span>
                <span>🎬 <?php echo htmlspecialchars($m['genre']); ?></span>
                <span>🕐 <?php echo htmlspecialchars($m['duration']); ?></span>
                <span>🌐 <?php echo htmlspecialchars($m['language']); ?></span>
            </div>
            <p class="hero-desc"><?php echo htmlspecialchars(substr($m['description'], 0, 140)); ?>...</p>
            <div class="hero-btns">
                <a href="movie-details.php?id=<?php echo $m['movie_id']; ?>" class="btn btn-primary btn-lg">🎟️ Book Tickets</a>
                <?php if ($m['trailer_url']): ?>
                <button class="btn btn-dark btn-lg" onclick="openTrailer('<?php echo str_replace("'", "\\'", $m['trailer_url']); ?>')">▶ Watch Trailer</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php $slideIndex++; endwhile; ?>
    <!-- Dots -->
    <div class="hero-controls">
        <?php for ($i = 0; $i < $slideCount; $i++): ?>
        <button class="hero-dot <?php echo $i === 0 ? 'active' : ''; ?>"></button>
        <?php endfor; ?>
    </div>
</section>

<!-- Search Section -->
<section class="search-section">
    <div class="container">
        <form action="search.php" method="GET" class="search-box">
            <span style="font-size:1.2rem;">🔍</span>
            <input type="text" name="q" placeholder="Search for movies, events, artists...">
            <select name="type">
                <option value="">All</option>
                <option value="movie">Movies</option>
                <option value="event">Events</option>
            </select>
            <select name="city">
                <option value="">All Cities</option>
                <?php foreach($cities as $c): ?>
                <option value="<?php echo $c; ?>" <?php echo ($selectedCity === $c) ? 'selected' : ''; ?>><?php echo $c; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        <?php if ($selectedCity): ?>
        <p style="text-align:center;margin-top:12px;color:var(--text-muted);font-size:0.9rem;">
            📍 Showing content for <strong style="color:var(--primary);"><?php echo htmlspecialchars($selectedCity); ?></strong>
            &nbsp;·&nbsp; <a href="?set_city=" style="color:var(--primary);">Show All Cities</a>
        </p>
        <?php endif; ?>
    </div>
</section>

<!-- =================== NOW SHOWING =================== -->
<section class="section" style="padding-top:40px;">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">🎬 Now Showing</h2>
                <p class="section-subtitle">Catch the latest blockbusters in theatres now</p>
            </div>
            <a href="movies.php" class="view-all">View All →</a>
        </div>

        <div class="movies-grid">
            <?php
            $movies->data_seek(0);
            while ($movie = $movies->fetch_assoc()):
                $minPrice = getMinPrice($conn, $movie['movie_id']);
                $genres   = explode(',', $movie['genre']);
                $genre1   = trim($genres[0]);
                $genre2   = isset($genres[1]) ? trim($genres[1]) : '';
                $movieUrl = BASE_URL . 'movie-details.php?id=' . $movie['movie_id'];
            ?>
            <!-- Entire card is a link -->
            <a href="<?php echo $movieUrl; ?>" class="movie-card movie-card-link">
                <div class="movie-poster" style="position:relative;">
                    <?php
                    $hasPosterCard = !empty($movie['poster']) && file_exists(__DIR__ . '/assets/images/movies/' . $movie['poster']);
                    ?>
                    <div class="movie-poster-img" style="<?php echo $hasPosterCard ? 'font-size:0;' : ''; ?>">
                        <?php if ($hasPosterCard): ?>
                        <img src="<?php echo BASE_URL; ?>assets/images/movies/<?php echo htmlspecialchars($movie['poster']); ?>" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:inherit;" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                        <?php else: ?>🎬<?php endif; ?>
                    </div>
                    <div class="movie-rating-badge" style="position:absolute;top:8px;left:8px;transform:none;">⭐ <?php echo $movie['rating']; ?></div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="POST" action="<?php echo BASE_URL; ?>wishlist.php" style="display:inline;" onclick="event.stopPropagation()">
                        <input type="hidden" name="movie_id" value="<?php echo $movie['movie_id']; ?>">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="redirect" value="index.php">
                        <button type="submit" class="wishlist-btn" title="Add to Wishlist">♥</button>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="movie-info">
                    <div class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></div>
                    <!-- Genre badges -->
                    <div class="movie-meta">
                        <span class="movie-tag"><?php echo htmlspecialchars($genre1); ?></span>
                        <?php if ($genre2): ?>
                        <span class="movie-tag" style="background:rgba(99,102,241,0.15);color:#a5b4fc;"><?php echo htmlspecialchars($genre2); ?></span>
                        <?php endif; ?>
                        <span class="movie-lang"><?php echo htmlspecialchars($movie['language']); ?></span>
                    </div>
                    <!-- Quick details row -->
                    <div class="movie-quick-details">
                        <span title="Duration">🕐 <?php echo htmlspecialchars($movie['duration']); ?></span>
                        <span title="Starting from" style="color:var(--primary);font-weight:600;"><?php echo $minPrice; ?> onwards</span>
                    </div>
                </div>
                <div class="movie-actions">
                    <span class="btn btn-primary btn-book">Book Tickets</span>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- Promo Banner -->
<section style="padding:20px 0 60px;">
    <div class="container">
        <div class="trending-banner">
            <div>
                <h2>🎉 Use Code <span style="background:rgba(255,255,255,0.2);padding:2px 10px;border-radius:6px;">FIRST50</span> for 50% Off!</h2>
                <p>On your very first booking. Limited time offer. Don't miss out!</p>
            </div>
            <a href="movies.php" class="btn btn-dark btn-lg">Book Now →</a>
        </div>
    </div>
</section>

<!-- =================== UPCOMING EVENTS =================== -->
<section class="section" style="padding-top:0;">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">🎪 Upcoming Events</h2>
                <p class="section-subtitle">Concerts, sports, comedy and more<?php echo $selectedCity ? ' in ' . htmlspecialchars($selectedCity) : ''; ?></p>
            </div>
            <a href="events.php" class="view-all">View All →</a>
        </div>
        <div class="events-grid">
            <?php
            $events->data_seek(0);
            $eventBgs    = ['#1a0032','#00172d','#1a1400','#001a12','#1a0010','#003322'];
            $eventEmojis = ['🎵','🏏','😂','🎸','🎭','🎪'];
            $ei = 0;
            while ($event = $events->fetch_assoc()):
                $eventUrl = BASE_URL . 'event-details.php?id=' . $event['event_id'];
            ?>
            <a href="<?php echo $eventUrl; ?>" class="event-card event-card-link">
                <div class="event-image">
                    <div style="width:100%;height:100%;background:linear-gradient(135deg,<?php echo $eventBgs[$ei % 6]; ?>,#0f0f1a);display:flex;align-items:center;justify-content:center;font-size:4rem;">
                        <?php echo $eventEmojis[$ei % 6]; ?>
                    </div>
                    <span class="event-category">Live Event</span>
                </div>
                <div class="event-info">
                    <h3 class="event-title"><?php echo htmlspecialchars($event['event_name']); ?></h3>
                    <div class="event-detail"><span class="icon">📅</span> <?php echo date('D, d M Y', strtotime($event['event_date'])); ?></div>
                    <div class="event-detail"><span class="icon">📍</span> <?php echo htmlspecialchars($event['location']); ?></div>
                    <div class="event-price">
                        <span class="event-price-text">₹<?php echo number_format($event['ticket_price'], 0); ?> onwards</span>
                        <span class="btn btn-primary btn-sm">Book</span>
                    </div>
                </div>
            </a>
            <?php $ei++; endwhile; ?>
        </div>
    </div>
</section>

<!-- Notifications -->
<?php if ($notifs->num_rows > 0): ?>
<section style="padding:0 0 60px;">
    <div class="container">
        <div class="section-header">
            <div><h2 class="section-title">🔔 Latest Offers</h2></div>
            <a href="notifications.php" class="view-all">View All →</a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
            <?php while ($notif = $notifs->fetch_assoc()): ?>
            <div class="notification-item">
                <div class="notification-icon">🎉</div>
                <div>
                    <strong style="font-size:0.95rem;"><?php echo htmlspecialchars($notif['title']); ?></strong>
                    <p style="color:var(--text-muted);font-size:0.85rem;margin-top:4px;"><?php echo htmlspecialchars($notif['message']); ?></p>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

