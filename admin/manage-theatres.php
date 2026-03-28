<?php
require_once 'admin-helper.php';
require_once '../config/cities.php';

$msg = '';

// Add theatre with map_link
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_theatre'])) {
    $tname   = $conn->real_escape_string(trim($_POST['theatre_name']));
    $tcity   = $conn->real_escape_string(trim($_POST['city']));
    $tloc    = $conn->real_escape_string(trim($_POST['location']));
    $maplink = $conn->real_escape_string(trim($_POST['map_link'] ?? ''));
    $conn->query("INSERT INTO theatres (theatre_name, city, location, map_link, is_verified) VALUES ('$tname','$tcity','$tloc','$maplink',1)");
    $msg = '<div class="alert alert-success">✅ Theatre added!</div>';
}

// Delete
if (isset($_GET['delete'])) {
    $tid = (int)$_GET['delete'];
    $conn->query("DELETE FROM theatres WHERE theatre_id = $tid");
    $msg = '<div class="alert alert-success">✅ Theatre deleted.</div>';
}

$theatres = $conn->query("SELECT * FROM theatres ORDER BY city, theatre_name");

ob_start();
echo $msg;
?>
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:24px;">
    <h4 style="margin-bottom:16px;">➕ Add Theatre</h4>
    <form method="POST">
        <div class="form-row">
            <div class="form-group"><label class="form-label">Theatre Name *</label><input type="text" class="form-control" name="theatre_name" required></div>
            <div class="form-group"><label class="form-label">City *</label>
                <select class="form-control" name="city" required>
                    <option value="">-- Select City --</option>
                    <?php foreach(BYS_CITIES as $c): ?>
                    <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label class="form-label">Full Address</label><input type="text" class="form-control" name="location"></div>
        <div class="form-group"><label class="form-label">🗺️ Google Maps Link</label><input type="url" class="form-control" name="map_link" placeholder="https://maps.google.com/..."></div>
        <button type="submit" name="add_theatre" class="btn btn-primary">Add Theatre</button>
    </form>
</div>

<div class="table-wrapper">
    <div class="table-header"><h4 class="table-title">🏟️ All Theatres</h4></div>
    <table>
        <thead><tr><th>ID</th><th>Name</th><th>City</th><th>Location</th><th>Map</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while($t=$theatres->fetch_assoc()): ?>
        <tr>
            <td>#<?php echo $t['theatre_id']; ?></td>
            <td style="font-weight:500;"><?php echo htmlspecialchars($t['theatre_name']); ?></td>
            <td><?php echo htmlspecialchars($t['city']); ?></td>
            <td style="color:var(--text-muted);font-size:.85rem;"><?php echo htmlspecialchars($t['location']); ?></td>
            <td><?php if(!empty($t['map_link'])): ?><a href="<?php echo htmlspecialchars($t['map_link']); ?>" target="_blank" class="btn btn-dark btn-sm">🗺️</a><?php else: ?>—<?php endif; ?></td>
            <td><?php echo ($t['is_verified']??0) ? '<span style="color:#22c55e;font-size:.8rem;">✅ Active</span>' : '<span style="color:#f59e0b;font-size:.8rem;">⏳ Pending</span>'; ?></td>
            <td><a href="manage-theatres.php?delete=<?php echo $t['theatre_id']; ?>" class="btn btn-sm" style="background:rgba(248,68,100,.15);color:var(--primary);border:1px solid var(--primary);" onclick="return confirm('Delete?')">Delete</a></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
adminLayout('Manage Theatres', $content, 'admin/manage-theatres.php');
?>
