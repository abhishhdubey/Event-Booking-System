<?php
require_once 'admin-helper.php';

$msg = '';
$uploadDir = '../assets/images/movies/';

// Helper: handle image upload
function uploadImage($fieldName, $uploadDir, $prefix = '') {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    $mime = mime_content_type($_FILES[$fieldName]['tmp_name']);
    if (!in_array($mime, $allowed)) return null;
    $ext = pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION);
    $filename = $prefix . '_' . time() . '_' . rand(1000,9999) . '.' . strtolower($ext);
    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $uploadDir . $filename)) {
        return $filename;
    }
    return null;
}

// ==== ADD MOVIE ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_movie'])) {
    $title = $conn->real_escape_string(trim($_POST['title']));
    $desc  = $conn->real_escape_string(trim($_POST['description']));
    $genre = $conn->real_escape_string(trim($_POST['genre']));
    $dur   = $conn->real_escape_string(trim($_POST['duration']));
    $lang  = $conn->real_escape_string(trim($_POST['language']));
    $date  = $conn->real_escape_string($_POST['release_date']);
    $rate  = floatval($_POST['rating']);
    $trail = $conn->real_escape_string(trim($_POST['trailer_url']));

    $poster = uploadImage('poster', $uploadDir, 'poster') ?? '';
    $banner = uploadImage('banner_image', $uploadDir, 'banner') ?? '';

    $conn->query("INSERT INTO movies (title, description, genre, duration, language, release_date, poster, banner_image, trailer_url, rating) VALUES ('$title','$desc','$genre','$dur','$lang','$date','$poster','$banner','$trail',$rate)");
    $msg = '<div class="alert alert-success">✅ Movie added successfully!</div>';
}

// ==== EDIT MOVIE ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_movie'])) {
    $mid   = (int)$_POST['movie_id'];
    $title = $conn->real_escape_string(trim($_POST['title']));
    $desc  = $conn->real_escape_string(trim($_POST['description']));
    $genre = $conn->real_escape_string(trim($_POST['genre']));
    $dur   = $conn->real_escape_string(trim($_POST['duration']));
    $lang  = $conn->real_escape_string(trim($_POST['language']));
    $date  = $conn->real_escape_string($_POST['release_date']);
    $rate  = floatval($_POST['rating']);
    $trail = $conn->real_escape_string(trim($_POST['trailer_url']));

    $updates = "title='$title', description='$desc', genre='$genre', duration='$dur', language='$lang', release_date='$date', rating=$rate, trailer_url='$trail'";

    $newPoster = uploadImage('poster', $uploadDir, 'poster');
    if ($newPoster) $updates .= ", poster='$newPoster'";

    $newBanner = uploadImage('banner_image', $uploadDir, 'banner');
    if ($newBanner) $updates .= ", banner_image='$newBanner'";

    $conn->query("UPDATE movies SET $updates WHERE movie_id = $mid");
    $msg = '<div class="alert alert-success">✅ Movie updated successfully!</div>';
}

// ==== DELETE MOVIE ====
if (isset($_GET['delete'])) {
    $mid = (int)$_GET['delete'];
    $conn->query("DELETE FROM movies WHERE movie_id = $mid");
    header('Location: manage-movies.php?deleted=1');
    exit;
}

if (isset($_GET['deleted'])) $msg = '<div class="alert alert-success">✅ Movie deleted.</div>';

// Get movie for edit
$editMovie = null;
if (isset($_GET['edit'])) {
    $editMovie = $conn->query("SELECT * FROM movies WHERE movie_id = " . (int)$_GET['edit'])->fetch_assoc();
}

$movies = $conn->query("SELECT * FROM movies ORDER BY movie_id DESC");

// ==== BUILD PAGE CONTENT ====
ob_start();
echo $msg;

// ----- EDIT FORM -----
if ($editMovie): ?>
<div style="background:var(--bg-card);border:2px solid var(--primary);border-radius:var(--radius);padding:24px;margin-bottom:24px;">
    <h4 style="margin-bottom:20px;color:var(--primary);">✏️ Edit Movie: <?php echo htmlspecialchars($editMovie['title']); ?></h4>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="movie_id" value="<?php echo $editMovie['movie_id']; ?>">
        <div class="form-row">
            <div class="form-group"><label class="form-label">Title</label><input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($editMovie['title']); ?>" required></div>
            <div class="form-group"><label class="form-label">Genre</label><input type="text" class="form-control" name="genre" value="<?php echo htmlspecialchars($editMovie['genre']); ?>" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Language</label><input type="text" class="form-control" name="language" value="<?php echo htmlspecialchars($editMovie['language']); ?>" required></div>
            <div class="form-group"><label class="form-label">Duration</label><input type="text" class="form-control" name="duration" value="<?php echo htmlspecialchars($editMovie['duration']); ?>" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Release Date</label><input type="date" class="form-control" name="release_date" value="<?php echo $editMovie['release_date']; ?>" required></div>
            <div class="form-group"><label class="form-label">Rating (0-10)</label><input type="number" class="form-control" name="rating" value="<?php echo $editMovie['rating']; ?>" min="0" max="10" step="0.1" required></div>
        </div>
        <div class="form-group"><label class="form-label">Trailer URL (YouTube Embed)</label><input type="url" class="form-control" name="trailer_url" value="<?php echo htmlspecialchars($editMovie['trailer_url'] ?? ''); ?>" placeholder="https://www.youtube.com/embed/..."></div>
        <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($editMovie['description']); ?></textarea></div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">🖼️ Poster (Vertical - 2:3 ratio) <?php if($editMovie['poster']): ?><small style="color:var(--primary);">Current: <?php echo $editMovie['poster']; ?></small><?php endif; ?></label>
                <?php if($editMovie['poster'] && file_exists('../assets/images/movies/' . $editMovie['poster'])): ?>
                <img src="<?php echo BASE_URL; ?>assets/images/movies/<?php echo $editMovie['poster']; ?>" style="height:80px;border-radius:6px;margin-bottom:8px;display:block;object-fit:cover;" alt="Current poster">
                <?php endif; ?>
                <input type="file" class="form-control" name="poster" accept="image/*" style="padding:6px;">
                <small style="color:var(--text-muted);">Leave blank to keep current poster</small>
            </div>
            <div class="form-group">
                <label class="form-label">🏞️ Banner (Horizontal - 16:9 ratio) <?php if($editMovie['banner_image']): ?><small style="color:var(--primary);">Current: <?php echo $editMovie['banner_image']; ?></small><?php endif; ?></label>
                <?php if($editMovie['banner_image'] && file_exists('../assets/images/movies/' . $editMovie['banner_image'])): ?>
                <img src="<?php echo BASE_URL; ?>assets/images/movies/<?php echo $editMovie['banner_image']; ?>" style="height:80px;border-radius:6px;margin-bottom:8px;display:block;object-fit:cover;width:100%;" alt="Current banner">
                <?php endif; ?>
                <input type="file" class="form-control" name="banner_image" accept="image/*" style="padding:6px;">
                <small style="color:var(--text-muted);">Leave blank to keep current banner</small>
            </div>
        </div>

        <div style="display:flex;gap:10px;">
            <button type="submit" name="edit_movie" class="btn btn-primary">💾 Save Changes</button>
            <a href="manage-movies.php" class="btn btn-dark">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- ADD MOVIE FORM -->
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:24px;">
    <h4 style="margin-bottom:20px;">➕ Add New Movie</h4>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-row">
            <div class="form-group"><label class="form-label">Title</label><input type="text" class="form-control" name="title" required></div>
            <div class="form-group"><label class="form-label">Genre</label><input type="text" class="form-control" name="genre" placeholder="Action, Drama" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Language</label><input type="text" class="form-control" name="language" required></div>
            <div class="form-group"><label class="form-label">Duration</label><input type="text" class="form-control" name="duration" placeholder="120 min" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Release Date</label><input type="date" class="form-control" name="release_date" required></div>
            <div class="form-group"><label class="form-label">Rating (0-10)</label><input type="number" class="form-control" name="rating" min="0" max="10" step="0.1" required></div>
        </div>
        <div class="form-group"><label class="form-label">Trailer URL (YouTube Embed)</label><input type="url" class="form-control" name="trailer_url" placeholder="https://www.youtube.com/embed/..."></div>
        <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"></textarea></div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">🖼️ Poster (Vertical - JPG/PNG/WebP)</label>
                <input type="file" class="form-control" name="poster" accept="image/*" style="padding:6px;">
            </div>
            <div class="form-group">
                <label class="form-label">🏞️ Banner (Horizontal - JPG/PNG/WebP)</label>
                <input type="file" class="form-control" name="banner_image" accept="image/*" style="padding:6px;">
            </div>
        </div>
        <button type="submit" name="add_movie" class="btn btn-primary">Add Movie</button>
    </form>
</div>

<!-- MOVIES TABLE -->
<div class="table-wrapper">
    <div class="table-header"><h4 class="table-title">🎬 All Movies</h4></div>
    <table>
        <thead><tr><th>Poster</th><th>Title</th><th>Genre</th><th>Language</th><th>Duration</th><th>Rating</th><th>Actions</th></tr></thead>
        <tbody>
<?php while ($m = $movies->fetch_assoc()): ?>
        <tr>
            <td>
                <?php if ($m['poster'] && file_exists('../assets/images/movies/' . $m['poster'])): ?>
                <img src="<?php echo BASE_URL; ?>assets/images/movies/<?php echo htmlspecialchars($m['poster']); ?>" style="height:55px;width:40px;object-fit:cover;border-radius:4px;" alt="">
                <?php else: ?>
                <span style="font-size:1.8rem;">🎬</span>
                <?php endif; ?>
            </td>
            <td style="font-weight:500;"><?php echo htmlspecialchars($m['title']); ?></td>
            <td style="color:var(--text-muted);font-size:0.85rem;"><?php echo htmlspecialchars($m['genre']); ?></td>
            <td><?php echo htmlspecialchars($m['language']); ?></td>
            <td><?php echo htmlspecialchars($m['duration']); ?></td>
            <td><span style="color:var(--gold);">⭐ <?php echo $m['rating']; ?></span></td>
            <td class="action-btns">
                <a href="manage-movies.php?edit=<?php echo $m['movie_id']; ?>" class="btn btn-dark btn-sm">✏️ Edit</a>
                <a href="<?php echo BASE_URL; ?>movie-details.php?id=<?php echo $m['movie_id']; ?>" class="btn btn-dark btn-sm" target="_blank">👁 View</a>
                <a href="manage-movies.php?delete=<?php echo $m['movie_id']; ?>" class="btn btn-sm" style="background:rgba(248,68,100,0.15);color:var(--primary);border:1px solid var(--primary);" onclick="return confirm('Delete this movie?')">🗑 Delete</a>
            </td>
        </tr>
<?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
adminLayout('Manage Movies', $content, 'admin/manage-movies.php');
?>
