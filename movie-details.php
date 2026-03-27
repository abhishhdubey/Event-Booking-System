<?php
session_start();
require_once 'config/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: movies.php'); exit; }

$movie = $conn->query("SELECT * FROM movies WHERE movie_id = $id")->fetch_assoc();
if (!$movie) { header('Location: movies.php'); exit; }

// Reviews
$reviews = $conn->query("SELECT r.*, u.name FROM reviews r JOIN users u ON r.user_id = u.user_id WHERE r.movie_id = $id ORDER BY r.review_date DESC");

// Wishlist check
$inWishlist = false;
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $wCheck = $conn->query("SELECT * FROM wishlist WHERE user_id = $uid AND movie_id = $id");
    $inWishlist = $wCheck->num_rows > 0;
}

// Handle review submit
$reviewMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $reviewMsg = 'error:Please login to submit a review.';
    } else {
        $uid = (int)$_SESSION['user_id'];
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if ($rating < 1 || $rating > 10 || !$comment) {
            $reviewMsg = 'error:Please provide a rating (1-10) and comment.';
        } else {
            // Check if already reviewed
            $alreadyReviewed = $conn->query("SELECT review_id FROM reviews WHERE user_id = $uid AND movie_id = $id")->num_rows > 0;
            if ($alreadyReviewed) {
                $reviewMsg = 'error:You have already reviewed this movie.';
            } else {
                $stmt = $conn->prepare("INSERT INTO reviews (user_id, movie_id, rating, comment) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('iiis', $uid, $id, $rating, $comment);
                $stmt->execute();
                $reviewMsg = 'success:Review submitted successfully!';
                header("Location: movie-details.php?id=$id&review=ok");
                exit;
            }
        }
    }
}

$pageTitle = htmlspecialchars($movie['title']) . ' - BookYourShow';
include 'includes/header.php';
?>

<div class="container" style="padding-top:40px;">
    <div class="breadcrumb">
        <a href="index.php">Home</a>
        <span class="separator">›</span>
        <a href="movies.php">Movies</a>
        <span class="separator">›</span>
        <span><?php echo htmlspecialchars($movie['title']); ?></span>
    </div>
</div>

<!-- Movie Hero -->
<?php
$hasBanner = !empty($movie['banner_image']) && file_exists('assets/images/movies/' . $movie['banner_image']);
$bannerUrl  = $hasBanner ? BASE_URL . 'assets/images/movies/' . htmlspecialchars($movie['banner_image']) : '';
?>
<div style="
    position:relative;
    min-height:400px;
    background:<?php echo $hasBanner ? "url('$bannerUrl') center/cover no-repeat" : 'linear-gradient(135deg,#0f0f1a 0%,#1a1a2e 100%)'; ?>;
    border-bottom:1px solid var(--border);
    overflow:hidden;
    display:flex;
    align-items:center;
">
    <!-- Multi-layer dark overlay for readability -->
    <?php if ($hasBanner): ?>
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom, rgba(0,0,0,0.35) 0%, rgba(0,0,0,0.8) 100%);"></div>
    <div style="position:absolute;inset:0;background:linear-gradient(to right, rgba(0,0,0,0.92) 0%, rgba(0,0,0,0.70) 50%, rgba(0,0,0,0.20) 100%);"></div>
    <?php endif; ?>

    <div class="container" style="position:relative;z-index:2;padding:50px 15px;width:100%;">
        <div style="display:grid;grid-template-columns:220px 1fr;gap:40px;align-items:start;">
            <!-- Poster -->
            <div style="position:relative;">
                <?php
                $hasPoster = !empty($movie['poster']) && file_exists('assets/images/movies/' . $movie['poster']);
                $posterSrc = $hasPoster ? BASE_URL . 'assets/images/movies/' . htmlspecialchars($movie['poster']) : '';
                ?>
                <div style="aspect-ratio:2/3;background:linear-gradient(135deg,var(--bg-card),var(--border));border-radius:var(--radius);overflow:hidden;border:1px solid rgba(255,255,255,0.1);box-shadow:0 8px 40px rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;font-size:5rem;position:relative;">
                    <?php if ($hasPoster): ?>
                    <img src="<?php echo $posterSrc; ?>" style="width:100%;height:100%;object-fit:cover;display:block;" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                    <?php else: ?>
                    🎬
                    <?php endif; ?>
                </div>
                <!-- Rating badge: top-left corner -->
                <div class="movie-rating-badge" style="position:absolute;top:10px;left:10px;font-size:0.9rem;padding:5px 12px;transform:none;">⭐ <?php echo $movie['rating']; ?>/10</div>
            </div>

            <!-- Info -->
            <div>
                <h1 style="font-size:2.8rem;font-weight:800;margin-bottom:12px;text-shadow:0 2px 12px rgba(0,0,0,0.8);line-height:1.2;"><?php echo htmlspecialchars($movie['title']); ?></h1>
                <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
                    <?php foreach(explode(',',$movie['genre']) as $g): ?>
                    <span class="badge badge-info" style="backdrop-filter:blur(6px);"><?php echo htmlspecialchars(trim($g)); ?></span>
                    <?php endforeach; ?>
                </div>
                <div style="display:flex;gap:24px;flex-wrap:wrap;margin-bottom:20px;color:rgba(255,255,255,0.85);font-size:0.95rem;">
                    <span style="text-shadow:0 1px 4px rgba(0,0,0,0.8);">🌐 <?php echo htmlspecialchars($movie['language']); ?></span>
                    <span style="text-shadow:0 1px 4px rgba(0,0,0,0.8);">🕐 <?php echo htmlspecialchars($movie['duration']); ?></span>
                    <span style="text-shadow:0 1px 4px rgba(0,0,0,0.8);">📅 <?php echo date('d M Y', strtotime($movie['release_date'])); ?></span>
                </div>
                <p style="color:rgba(255,255,255,0.78);line-height:1.8;margin-bottom:24px;max-width:600px;text-shadow:0 1px 6px rgba(0,0,0,0.7);"><?php echo htmlspecialchars($movie['description']); ?></p>
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <a href="theatres.php?movie_id=<?php echo $movie['movie_id']; ?>" class="btn btn-primary btn-lg pulse">🎟️ Book Tickets</a>
                    <?php if ($movie['trailer_url']): ?>
                    <button class="btn btn-dark btn-lg" onclick="openTrailer('<?php echo str_replace("'", "\\'", $movie['trailer_url']); ?>')" style="background:rgba(0,0,0,0.5);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.2);">▶ Watch Trailer</button>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="POST" action="wishlist.php">
                        <input type="hidden" name="movie_id" value="<?php echo $id; ?>">
                        <input type="hidden" name="action" value="<?php echo $inWishlist ? 'remove' : 'add'; ?>">
                        <input type="hidden" name="redirect" value="movie-details.php?id=<?php echo $id; ?>">
                        <button type="submit" class="btn btn-outline btn-lg" style="background:rgba(0,0,0,0.5);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.2);"><?php echo $inWishlist ? '💔 Remove Wishlist' : '❤️ Add to Wishlist'; ?></button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding:40px 15px;">
    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" onclick="showTab('overview')">📋 Overview</button>
        <button class="tab-btn" onclick="showTab('showtimes')">📅 Showtimes</button>
        <button class="tab-btn" onclick="showTab('reviews')">⭐ Reviews (<?php echo $reviews->num_rows; ?>)</button>
    </div>

    <!-- Overview Tab -->
    <div id="tab-overview" class="tab-content">
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:40px;">
            <div>
                <h3 style="margin-bottom:16px;">About the Movie</h3>
                <p style="color:var(--text-muted);line-height:1.9;"><?php echo htmlspecialchars($movie['description']); ?></p>
            </div>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;height:fit-content;">
                <h4 style="margin-bottom:16px;font-size:1rem;">Movie Details</h4>
                <?php
                $details = [
                    'Genre' => $movie['genre'],
                    'Duration' => $movie['duration'],
                    'Language' => $movie['language'],
                    'Release Date' => date('d M Y', strtotime($movie['release_date'])),
                    'Rating' => $movie['rating'] . '/10 ⭐'
                ];
                foreach($details as $key => $val):
                ?>
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);font-size:0.9rem;">
                    <span style="color:var(--text-muted);"><?php echo $key; ?></span>
                    <span style="font-weight:500;text-align:right;"><?php echo htmlspecialchars($val); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Showtimes Tab -->
    <div id="tab-showtimes" class="tab-content" style="display:none;">
        <h3 style="margin-bottom:20px;">Select Date & Theatre</h3>
        <?php
        // Get all upcoming shows grouped by date then theatre — only approved/active shows
        $allShows = $conn->query("
            SELECT s.*, t.theatre_name, t.city, t.location, t.map_link
            FROM shows s
            JOIN theatres t ON s.theatre_id = t.theatre_id
            WHERE s.movie_id = $id
              AND s.show_date >= CURDATE()
              AND (s.status = 'active' OR s.status IS NULL)
            ORDER BY s.show_date ASC, t.theatre_name ASC, s.show_time ASC
        ");

        if (!$allShows || $allShows->num_rows === 0): ?>
        <p style="color:var(--text-muted);padding:30px;text-align:center;">No upcoming shows available.<?php echo $selectedCity ? ' Try removing the city filter.' : ''; ?></p>
        <?php else:
        // Group shows by date
        $byDate = [];
        while($show = $allShows->fetch_assoc()) {
            $byDate[$show['show_date']][$show['theatre_id']]['info']    = $show;
            $byDate[$show['show_date']][$show['theatre_id']]['shows'][] = $show;
        }

        // Date selector tabs
        echo '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px;" id="dateTabs">';
        $first = true;
        foreach($byDate as $date => $theatresOnDate) {
            $active = $first ? 'background:var(--primary);color:#fff;' : 'background:var(--bg-card);color:var(--text);border:1px solid var(--border);';
            $label  = date('D', strtotime($date));
            $num    = date('d', strtotime($date));
            $month  = date('M', strtotime($date));
            echo "<button class='date-tab-btn' onclick='selectDate(this, \".date-pane-".str_replace('-','',$date)."\")' style='padding:10px 18px;border-radius:10px;border:none;cursor:pointer;font-weight:600;{$active}text-align:center;min-width:70px;'>
                <div style='font-size:.7rem;opacity:.8;'>$label</div>
                <div style='font-size:1.2rem;font-weight:800;'>$num</div>
                <div style='font-size:.7rem;opacity:.8;'>$month</div>
            </button>";
            $first = false;
        }
        echo '</div>';

        // Date Panes
        $first = true;
        foreach($byDate as $date => $theatresOnDate): ?>
        <div class="date-pane date-pane-<?php echo str_replace('-','',$date); ?>" style="<?php echo $first?'display:block':'display:none'; ?>">
            <?php foreach($theatresOnDate as $tid => $thData):
                $info   = $thData['info'];
                $tshows = $thData['shows'];
            ?>
            <div class="theatre-card" style="margin-bottom:16px;">
                <div class="theatre-header">
                    <div>
                        <div class="theatre-name"><?php echo htmlspecialchars($info['theatre_name']); ?></div>
                        <div class="theatre-location">📍 <?php echo htmlspecialchars($info['city'].' - '.$info['location']); ?>
                            <?php if(!empty($info['map_link'])): ?> &nbsp;<a href="<?php echo htmlspecialchars($info['map_link']); ?>" target="_blank" style="font-size:.8rem;color:var(--primary);">🗺️ Map</a><?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="show-times" style="flex-wrap:wrap;gap:12px;">
                    <?php foreach($tshows as $show):
                        $lang = $show['language'] ?? 'Hindi';
                        $langColor = match(strtolower($lang)) {
                            'english' => '#3b82f6',
                            'tamil'   => '#a855f7',
                            'telugu'  => '#ec4899',
                            'kannada' => '#f97316',
                            'malayalam'=>'#14b8a6',
                            default   => '#ef4444'
                        };
                    ?>
                    <a href="seat-selection.php?show_id=<?php echo $show['show_id']; ?>" class="show-time-btn" style="display:flex;flex-direction:column;align-items:center;gap:4px;padding:10px 16px;min-width:100px;">
                        <span style="background:<?php echo $langColor; ?>22;color:<?php echo $langColor; ?>;padding:2px 8px;border-radius:4px;font-size:.68rem;font-weight:700;letter-spacing:.5px;"><?php echo htmlspecialchars(strtoupper($lang)); ?></span>
                        <span style="font-weight:700;font-size:.95rem;"><?php echo date('h:i A', strtotime($show['show_time'])); ?></span>
                        <small style="color:var(--text-muted);font-size:.72rem;">₹<?php echo number_format($show['price'],0); ?></small>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php $first=false; endforeach; ?>
        <?php endif; ?>
        <div style="text-align:center;margin-top:20px;">
            <a href="theatres.php?movie_id=<?php echo $id; ?>" class="btn btn-primary">View All Theatres</a>
        </div>
    </div>

    <!-- Reviews Tab -->
    <div id="tab-reviews" class="tab-content" style="display:none;">
        <?php if (isset($_GET['review']) && $_GET['review'] === 'ok'): ?>
        <div class="alert alert-success">✅ Review submitted successfully!</div>
        <?php endif; ?>

        <!-- Review Form -->
        <?php if (isset($_SESSION['user_id'])): ?>
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:30px;">
            <h4 style="margin-bottom:16px;">Write a Review</h4>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Rating (1-10)</label>
                    <div style="display:flex;gap:6px;margin-bottom:10px;" id="starRating">
                        <?php for($i=1;$i<=10;$i++): ?>
                        <span class="star-label" style="font-size:1.5rem;cursor:pointer;color:var(--border);" data-val="<?php echo $i; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="0">
                </div>
                <div class="form-group">
                    <textarea class="form-control" name="comment" rows="4" placeholder="Share your experience..."></textarea>
                </div>
                <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
            </form>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">⚠️ <a href="login.php" style="color:var(--primary);">Login</a> to write a review.</div>
        <?php endif; ?>

        <!-- Reviews List -->
        <?php if ($reviews->num_rows === 0): ?>
        <p style="color:var(--text-muted);text-align:center;padding:30px;">No reviews yet. Be the first to review!</p>
        <?php else: while($rev = $reviews->fetch_assoc()): ?>
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <strong><?php echo htmlspecialchars($rev['name']); ?></strong>
                <div style="display:flex;gap:4px;align-items:center;">
                    <span style="color:var(--gold);font-size:1rem;">★</span>
                    <span style="font-weight:700;"><?php echo $rev['rating']; ?>/10</span>
                    <span style="color:var(--text-muted);font-size:0.8rem;margin-left:10px;"><?php echo date('d M Y', strtotime($rev['review_date'])); ?></span>
                </div>
            </div>
            <p style="color:var(--text-muted);font-size:0.9rem;line-height:1.7;"><?php echo htmlspecialchars($rev['comment']); ?></p>
        </div>
        <?php endwhile; endif; ?>
    </div>
</div>

<script>
function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(function(el){ el.style.display = 'none'; });
    document.querySelectorAll('.tab-btn').forEach(function(el){ el.classList.remove('active'); });
    document.getElementById('tab-' + tab).style.display = 'block';
    event.target.classList.add('active');
}

function selectDate(btn, paneClass) {
    // Update button styles
    document.querySelectorAll('.date-tab-btn').forEach(function(b) {
        b.style.background = 'var(--bg-card)';
        b.style.color      = 'var(--text)';
        b.style.border     = '1px solid var(--border)';
    });
    btn.style.background = 'var(--primary)';
    btn.style.color      = '#fff';
    btn.style.border     = 'none';
    // Show pane
    document.querySelectorAll('.date-pane').forEach(function(p){ p.style.display='none'; });
    document.querySelectorAll(paneClass).forEach(function(p){ p.style.display='block'; });
}

// Star rating
document.querySelectorAll('.star-label').forEach(function(star, idx) {
    star.addEventListener('click', function(){
        var val = parseInt(star.getAttribute('data-val'));
        document.getElementById('ratingInput').value = val;
        document.querySelectorAll('.star-label').forEach(function(s, i){
            s.style.color = i < val ? '#ffd700' : 'var(--border)';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>

