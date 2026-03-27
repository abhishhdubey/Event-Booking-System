<?php
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$uid = (int)$_SESSION['user_id'];
$msg = '';

// Add to wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mid    = (int)($_POST['movie_id'] ?? 0);
    $action = $_POST['action'] ?? 'add';
    $redirect = $_POST['redirect'] ?? 'wishlist.php';

    if ($mid) {
        if ($action === 'add') {
            $exists = $conn->query("SELECT wishlist_id FROM wishlist WHERE user_id = $uid AND movie_id = $mid")->num_rows;
            if (!$exists) {
                $conn->query("INSERT INTO wishlist (user_id, movie_id) VALUES ($uid, $mid)");
                $msg = 'success:Added to wishlist!';
            } else {
                $msg = 'info:Already in wishlist.';
            }
        } elseif ($action === 'remove') {
            $conn->query("DELETE FROM wishlist WHERE user_id = $uid AND movie_id = $mid");
            $msg = 'success:Removed from wishlist.';
        }
    }
    if ($redirect && $redirect !== 'wishlist.php') {
        header("Location: " . $redirect);
        exit;
    }
}

$wishlist = $conn->query("SELECT w.*, m.title, m.genre, m.rating, m.language FROM wishlist w JOIN movies m ON w.movie_id = m.movie_id WHERE w.user_id = $uid ORDER BY w.wishlist_id DESC");

$pageTitle = 'My Wishlist - BookYourShow';
include 'includes/header.php';
?>
<div class="container" style="padding:40px 15px;">
    <h2 class="section-title">❤️ My Wishlist</h2>
    <p class="section-subtitle">Movies you've saved for later</p>

    <?php if ($msg): list($t,$m) = explode(':',$msg,2); ?>
    <div class="alert alert-<?php echo $t==='success'?'success':'warning'; ?>"><?php echo htmlspecialchars($m); ?></div>
    <?php endif; ?>

    <?php if ($wishlist->num_rows === 0): ?>
    <div style="text-align:center;padding:60px;color:var(--text-muted);">
        <div style="font-size:3rem;margin-bottom:16px;">❤️</div>
        <p>Your wishlist is empty.</p>
        <a href="movies.php" class="btn btn-primary" style="margin-top:16px;">Discover Movies</a>
    </div>
    <?php else: ?>
    <div class="movies-grid">
        <?php while($w = $wishlist->fetch_assoc()): ?>
        <div class="movie-card">
            <div class="movie-poster">
                <div style="width:100%;aspect-ratio:2/3;background:linear-gradient(135deg,#1a1a2e,#2e2e4e);display:flex;align-items:center;justify-content:center;font-size:3rem;">🎬</div>
                <div class="movie-rating-badge">⭐ <?php echo $w['rating']; ?></div>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="movie_id" value="<?php echo $w['movie_id']; ?>">
                    <input type="hidden" name="action" value="remove">
                    <button type="submit" class="wishlist-btn active" title="Remove from Wishlist">♥</button>
                </form>
                <div class="movie-overlay">
                    <a href="movie-details.php?id=<?php echo $w['movie_id']; ?>" class="btn btn-primary btn-sm">Book Now</a>
                </div>
            </div>
            <div class="movie-info">
                <div class="movie-title"><?php echo htmlspecialchars($w['title']); ?></div>
                <div class="movie-meta">
                    <?php $g = explode(',',$w['genre']); ?>
                    <span class="movie-tag"><?php echo htmlspecialchars(trim($g[0])); ?></span>
                    <span class="movie-lang"><?php echo htmlspecialchars($w['language']); ?></span>
                </div>
            </div>
            <div class="movie-actions">
                <a href="movie-details.php?id=<?php echo $w['movie_id']; ?>" class="btn btn-primary btn-book">Book Tickets</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>

