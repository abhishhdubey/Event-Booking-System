<?php
require_once 'admin-helper.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ads'])) {
    // Reset all to 0
    $conn->query("UPDATE movies SET is_slider_ad = 0");
    
    // Set selected to 1
    if (isset($_POST['slider_movies']) && is_array($_POST['slider_movies'])) {
        $selectedIds = array_map('intval', $_POST['slider_movies']);
        if (!empty($selectedIds)) {
            $idsList = implode(',', $selectedIds);
            $conn->query("UPDATE movies SET is_slider_ad = 1 WHERE movie_id IN ($idsList)");
        }
    }
    
    $msg = '<div class="alert alert-success">✅ Slider ads updated successfully!</div>';
}

$movies = $conn->query("SELECT movie_id, title, poster, banner_image, is_slider_ad FROM movies ORDER BY title ASC");

ob_start();
echo $msg;
?>

<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:24px;">
    <h4 style="margin-bottom:10px;">🖼️ Manage Slider Ads</h4>
    <p style="color:var(--text-muted);margin-bottom:20px;">Select which movies should appear on the homepage slider banner.</p>
    
    <form method="POST">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width:50px;">Select</th>
                        <th>Poster</th>
                        <th>Title</th>
                        <th>Banner Image</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($m = $movies->fetch_assoc()): ?>
                    <tr>
                        <td style="text-align:center;">
                            <input type="checkbox" name="slider_movies[]" value="<?php echo $m['movie_id']; ?>" <?php echo $m['is_slider_ad'] ? 'checked' : ''; ?> style="width:18px;height:18px;cursor:pointer;">
                        </td>
                        <td>
                            <?php if ($m['poster'] && file_exists('../assets/images/movies/' . $m['poster'])): ?>
                            <img src="<?php echo BASE_URL; ?>assets/images/movies/<?php echo htmlspecialchars($m['poster']); ?>" style="height:55px;width:40px;object-fit:cover;border-radius:4px;" alt="">
                            <?php else: ?>
                            <span style="font-size:1.8rem;">🎬</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:500;"><?php echo htmlspecialchars($m['title']); ?></td>
                        <td>
                            <?php if ($m['banner_image']): ?>
                                <span style="color:#10b981;">✅ Uploaded</span>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">❌ Missing (Fallback to gradient)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:20px;">
            <button type="submit" name="update_ads" class="btn btn-primary btn-lg">💾 Save Changes</button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
adminLayout('Manage Slider Ads', $content, 'admin/manage-ads.php');
?>
