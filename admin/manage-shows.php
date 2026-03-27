<?php
require_once 'admin-helper.php';

$msg = '';

// ── Approve show ────────────────────────────────────────────────────────────
if (isset($_GET['approve_show'])) {
    $sid = (int)$_GET['approve_show'];
    $conn->query("UPDATE shows SET status='active' WHERE show_id=$sid");
    $msg = '<div class="alert alert-success">✅ Show approved and now live!</div>';
}

// ── Reject show ─────────────────────────────────────────────────────────────
if (isset($_GET['reject_show'])) {
    $sid = (int)$_GET['reject_show'];
    $conn->query("UPDATE shows SET status='rejected' WHERE show_id=$sid");
    $msg = '<div class="alert alert-success">❌ Show rejected.</div>';
}

// ── Admin-add show ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_show'])) {
    $mid   = (int)$_POST['movie_id'];
    $tid   = (int)$_POST['theatre_id'];
    $date  = $conn->real_escape_string($_POST['show_date']);
    $time  = $conn->real_escape_string($_POST['show_time']);
    $lang  = $conn->real_escape_string($_POST['language'] ?? 'Hindi');
    $price = floatval($_POST['price']);
    $res = $conn->query("INSERT INTO shows (movie_id,theatre_id,show_date,show_time,language,price,status) VALUES ($mid,$tid,'$date','$time','$lang',$price,'active')");
    $msg = $res ? '<div class="alert alert-success">✅ Show added (auto-approved).</div>' : '<div class="alert alert-danger">❌ Failed: '.$conn->error.'</div>';
}

// ── Delete show ─────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $sid = (int)$_GET['delete'];
    $conn->query("DELETE FROM shows WHERE show_id=$sid");
    $msg = '<div class="alert alert-success">✅ Deleted.</div>';
}

$movies   = $conn->query("SELECT movie_id, title FROM movies ORDER BY title");
$theatres = $conn->query("SELECT theatre_id, theatre_name, city FROM theatres ORDER BY city, theatre_name");

// ── ALL pending shows (with licence status for that operator/theatre) ────────
$pendingShows = $conn->query("
    SELECT s.*, m.title as movie_title, t.theatre_name, t.city,
           o.name as op_name,
           tl.status as lic_status
    FROM shows s
    JOIN movies m ON s.movie_id = m.movie_id
    JOIN theatres t ON s.theatre_id = t.theatre_id
    LEFT JOIN operators o ON s.operator_id = o.operator_id
    LEFT JOIN theatre_licences tl
           ON tl.theatre_id = s.theatre_id AND tl.operator_id = s.operator_id
    WHERE s.status = 'pending'
    ORDER BY s.show_id DESC
");

// All active/live shows
$shows = $conn->query("
    SELECT s.*, m.title, t.theatre_name, t.city
    FROM shows s
    JOIN movies m ON s.movie_id = m.movie_id
    JOIN theatres t ON s.theatre_id = t.theatre_id
    WHERE s.status = 'active'
    ORDER BY s.show_date DESC, s.show_time DESC
    LIMIT 80
");

$movOpts = $thOpts = '';
while($m=$movies->fetch_assoc())   $movOpts .= '<option value="'.$m['movie_id'].'">'.htmlspecialchars($m['title']).'</option>';
while($t=$theatres->fetch_assoc()) $thOpts  .= '<option value="'.$t['theatre_id'].'">'.htmlspecialchars($t['theatre_name'].', '.$t['city']).'</option>';
$langOpts = '';
foreach(['Hindi','English','Tamil','Telugu','Kannada','Malayalam','Bengali','Marathi','Punjabi'] as $l) $langOpts .= "<option>$l</option>";

ob_start();
echo $msg;

// ── Pending shows section ───────────────────────────────────────────────────
$pCount = $pendingShows ? $pendingShows->num_rows : 0;
?>
<div class="table-wrapper" style="margin-bottom:28px;">
    <div class="table-header">
        <h4 class="table-title">⏳ Pending Show Approvals
            <?php if($pCount > 0): ?>
            <span style="background:rgba(245,158,11,.12);color:#f59e0b;padding:2px 10px;border-radius:50px;font-size:.78rem;font-weight:700;margin-left:8px;"><?php echo $pCount; ?></span>
            <?php endif; ?>
        </h4>
    </div>
    <?php if($pCount === 0): ?>
    <p style="text-align:center;padding:30px;color:var(--text-muted);">No pending shows.</p>
    <?php else: ?>
    <table>
        <thead><tr>
            <th>Movie</th><th>Theatre</th><th>Organizer</th>
            <th>Date</th><th>Lang</th><th>Time</th><th>Price</th>
            <th>Licence</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php while($s=$pendingShows->fetch_assoc()):
            $ls = $s['lic_status'] ?? null;
            // Licence badge
            if ($ls === 'approved') {
                $licBadge = '<span style="background:rgba(34,197,94,.12);color:#22c55e;padding:2px 8px;border-radius:50px;font-size:.72rem;font-weight:700;">✅ Approved</span>';
                $canApprove = true;
            } elseif ($ls === 'pending') {
                $licBadge = '<span style="background:rgba(245,158,11,.12);color:#f59e0b;padding:2px 8px;border-radius:50px;font-size:.72rem;font-weight:700;">⏳ Lic Pending</span>';
                $canApprove = false;
            } elseif ($ls === 'rejected') {
                $licBadge = '<span style="background:rgba(239,68,68,.12);color:#ef4444;padding:2px 8px;border-radius:50px;font-size:.72rem;font-weight:700;">❌ Lic Rejected</span>';
                $canApprove = false;
            } elseif (is_null($s['operator_id'])) {
                // Admin-added show with no operator — auto allow
                $licBadge = '<span style="color:var(--text-muted);font-size:.72rem;">Admin</span>';
                $canApprove = true;
            } else {
                $licBadge = '<span style="background:rgba(239,68,68,.12);color:#ef4444;padding:2px 8px;border-radius:50px;font-size:.72rem;font-weight:700;">⚠️ No Licence</span>';
                $canApprove = false;
            }
        ?>
        <tr style="background:rgba(245,158,11,.04);">
            <td style="font-weight:600;"><?php echo htmlspecialchars($s['movie_title']); ?></td>
            <td style="font-size:.85rem;"><?php echo htmlspecialchars($s['theatre_name'].', '.$s['city']); ?></td>
            <td style="color:var(--text-muted);font-size:.85rem;"><?php echo htmlspecialchars($s['op_name'] ?? '—'); ?></td>
            <td><?php echo date('d M Y',strtotime($s['show_date'])); ?></td>
            <td><span style="background:rgba(248,68,100,.12);color:var(--primary);padding:2px 8px;border-radius:50px;font-size:.75rem;font-weight:700;"><?php echo htmlspecialchars($s['language']??'Hindi'); ?></span></td>
            <td><?php echo date('h:i A',strtotime($s['show_time'])); ?></td>
            <td style="font-weight:600;color:var(--primary);">₹<?php echo number_format($s['price'],0); ?></td>
            <td><?php echo $licBadge; ?></td>
            <td class="action-btns">
                <?php if($canApprove): ?>
                <a href="manage-shows.php?approve_show=<?php echo $s['show_id']; ?>"
                   class="btn btn-sm"
                   style="background:rgba(34,197,94,.15);color:#22c55e;border:1px solid #22c55e;"
                   onclick="return confirm('Approve and make live?')">✅ Approve</a>
                <?php else: ?>
                <span class="btn btn-sm"
                      style="opacity:.45;cursor:not-allowed;background:rgba(34,197,94,.08);color:#22c55e;border:1px solid #22c55e;"
                      title="Approve the theatre licence first">✅ Approve</span>
                <?php endif; ?>
                <a href="manage-shows.php?reject_show=<?php echo $s['show_id']; ?>"
                   class="btn btn-sm"
                   style="background:rgba(239,68,68,.15);color:#ef4444;border:1px solid #ef4444;"
                   onclick="return confirm('Reject this show?')">❌ Reject</a>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <p style="font-size:.82rem;color:var(--text-muted);padding:8px 0 0;">
        💡 <strong>Tip:</strong> Shows with "⏳ Lic Pending" — go to
        <a href="manage-licences.php" style="color:var(--primary);">Manage Licences</a> to approve the theatre licence first, then come back to approve the show.
    </p>
    <?php endif; ?>
</div>

<!-- Admin Add Show Form -->
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:24px;">
    <h4 style="margin-bottom:16px;">➕ Add Show (Admin)</h4>
    <form method="POST">
        <div class="form-row">
            <div class="form-group"><label class="form-label">Movie</label><select class="form-control" name="movie_id" required><?php echo $movOpts; ?></select></div>
            <div class="form-group"><label class="form-label">Theatre</label><select class="form-control" name="theatre_id" required><?php echo $thOpts; ?></select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Date</label><input type="date" class="form-control" name="show_date" required></div>
            <div class="form-group"><label class="form-label">Time</label><input type="time" class="form-control" name="show_time" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Language</label><select class="form-control" name="language"><?php echo $langOpts; ?></select></div>
            <div class="form-group"><label class="form-label">Price (₹)</label><input type="number" class="form-control" name="price" min="1" step="0.01" required></div>
        </div>
        <button type="submit" name="add_show" class="btn btn-primary">Add Show (Auto Approved)</button>
    </form>
</div>

<!-- Live Shows Table -->
<div class="table-wrapper">
    <div class="table-header"><h4 class="table-title">📅 Live Shows</h4></div>
    <table>
        <thead><tr><th>ID</th><th>Movie</th><th>Theatre</th><th>Lang</th><th>Date</th><th>Time</th><th>Price</th><th>Actions</th></tr></thead>
        <tbody>
<?php while($s=$shows->fetch_assoc()): ?>
        <tr>
            <td>#<?php echo $s['show_id']; ?></td>
            <td><?php echo htmlspecialchars($s['title']); ?></td>
            <td style="color:var(--text-muted);font-size:.85rem;"><?php echo htmlspecialchars($s['theatre_name'].', '.$s['city']); ?></td>
            <td><span style="background:rgba(248,68,100,.12);color:var(--primary);padding:2px 8px;border-radius:50px;font-size:.75rem;font-weight:700;"><?php echo htmlspecialchars($s['language']??'Hindi'); ?></span></td>
            <td><?php echo date('d M Y',strtotime($s['show_date'])); ?></td>
            <td><?php echo date('h:i A',strtotime($s['show_time'])); ?></td>
            <td style="font-weight:600;color:var(--primary);">₹<?php echo number_format($s['price'],0); ?></td>
            <td><a href="manage-shows.php?delete=<?php echo $s['show_id']; ?>" class="btn btn-sm" style="background:rgba(248,68,100,.15);color:var(--primary);border:1px solid var(--primary);" onclick="return confirm('Delete?')">Delete</a></td>
        </tr>
<?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
adminLayout('Manage Shows', $content, 'admin/manage-shows.php');
?>
