<?php
require_once 'admin-helper.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_event_media'])) {
    $eid = (int)$_POST['media_event_id'];
    $msgParts = [];
    function uploadImgAdmin($field, $dir, $pfx) {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return '';
        $ok = ['image/jpeg','image/png','image/webp','image/gif'];
        if (!in_array(mime_content_type($_FILES[$field]['tmp_name']), $ok)) return '';
        $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
        $fn  = $pfx.'_'.time().'_'.rand(1000,9999).'.'.$ext;
        return move_uploaded_file($_FILES[$field]['tmp_name'], $dir.$fn) ? $fn : '';
    }
    $ev_img   = uploadImgAdmin('new_poster','../assets/images/events/','poster');
    $banner   = uploadImgAdmin('new_banner','../assets/images/events/','banner');
    if ($ev_img) { $conn->query("UPDATE events SET event_image='$ev_img' WHERE event_id=$eid"); $msgParts[]='Poster'; }
    if ($banner) { $conn->query("UPDATE events SET banner_image='$banner' WHERE event_id=$eid"); $msgParts[]='Banner'; }
    header('Location: manage-events.php?done=media_updated'); exit;
}

// Approve / Reject
if (isset($_GET['approve'])) {
    $eid = (int)$_GET['approve'];
    $conn->query("UPDATE events SET status='approved' WHERE event_id=$eid");
    header('Location: manage-events.php?tab=pending&done=approved'); exit;
}
if (isset($_GET['reject'])) {
    $eid = (int)$_GET['reject'];
    $conn->query("UPDATE events SET status='rejected' WHERE event_id=$eid");
    header('Location: manage-events.php?tab=pending&done=rejected'); exit;
}
if (isset($_GET['delete'])) {
    $eid = (int)$_GET['delete'];
    $conn->query("DELETE FROM events WHERE event_id = $eid");
    header('Location: manage-events.php?done=deleted'); exit;
}

if (isset($_GET['done'])) {
    $msgs = ['approved'=>'✅ Event approved!','rejected'=>'❌ Event rejected.','deleted'=>'🗑 Event deleted.', 'media_updated'=>'✅ Event images updated!'];
    $msg = '<div class="alert alert-success">' . ($msgs[$_GET['done']] ?? '') . '</div>';
}

$tab = $_GET['tab'] ?? 'all';
$statusWhere = ($tab === 'pending') ? "AND e.status='pending'" : (($tab === 'approved') ? "AND e.status='approved'" : (($tab === 'rejected') ? "AND e.status='rejected'" : ""));

// Search filters
$searchEvtName = trim($_GET['ename'] ?? '');
$searchOrgName = trim($_GET['oname'] ?? '');
$searchCity    = trim($_GET['city'] ?? '');
$searchDate    = trim($_GET['event_date'] ?? '');
$searchSort    = in_array($_GET['sort_date'] ?? '', ['ASC','DESC']) ? $_GET['sort_date'] : 'DESC';
$searchWhere   = '';
if ($searchEvtName) $searchWhere .= " AND e.event_name LIKE '%" . $conn->real_escape_string($searchEvtName) . "%'";
if ($searchOrgName) $searchWhere .= " AND o.name LIKE '%" . $conn->real_escape_string($searchOrgName) . "%'";
if ($searchCity)    $searchWhere .= " AND e.location LIKE '%" . $conn->real_escape_string($searchCity) . "%'";
if ($searchDate)    $searchWhere .= " AND e.event_date = '" . $conn->real_escape_string($searchDate) . "'";

$events = $conn->query("SELECT e.*, o.name as organizer, o.organization FROM events e LEFT JOIN operators o ON e.organizer_id = o.operator_id WHERE 1=1 $statusWhere $searchWhere ORDER BY e.event_date $searchSort");

// City dropdown options (from events location)
$cityOpts = $conn->query("SELECT DISTINCT e.location FROM events e ORDER BY e.location");
$eventCities = [];
while ($cr = $cityOpts->fetch_assoc()) if (!empty($cr['location'])) $eventCities[] = $cr['location'];

$counts = [];
foreach (['all'=>'','pending'=>"AND e.status='pending'",'approved'=>"AND e.status='approved'",'rejected'=>"AND e.status='rejected'"] as $k=>$w) {
    $r = $conn->query("SELECT COUNT(*) as c FROM events e LEFT JOIN operators o ON e.organizer_id = o.operator_id WHERE 1=1 $w");
    $counts[$k] = $r->fetch_assoc()['c'];
}

ob_start();
?>
<?php echo $msg; ?>

<!-- Search Bar -->
<form method="GET" style="margin-bottom:18px;">
    <?php if ($tab !== 'all'): ?><input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>"> <?php endif; ?>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;">
        <div style="flex:1;min-width:140px;">
            <label style="font-size:0.78rem;color:var(--text-muted);display:block;margin-bottom:4px;">🎪 Event Name</label>
            <input type="text" name="ename" value="<?php echo htmlspecialchars($searchEvtName); ?>" placeholder="Search event..." class="form-control" style="padding:8px 12px;font-size:0.85rem;">
        </div>
        <div style="flex:1;min-width:140px;">
            <label style="font-size:0.78rem;color:var(--text-muted);display:block;margin-bottom:4px;">👤 Organizer Name</label>
            <input type="text" name="oname" value="<?php echo htmlspecialchars($searchOrgName); ?>" placeholder="Search organizer..." class="form-control" style="padding:8px 12px;font-size:0.85rem;">
        </div>
        <div style="min-width:160px;">
            <label style="font-size:0.78rem;color:var(--text-muted);display:block;margin-bottom:4px;">📍 City / Location</label>
            <select name="city" class="form-control" style="padding:8px 12px;font-size:0.85rem;background:var(--bg-dark);color:var(--text);border:1px solid var(--border);border-radius:8px;">
                <option value="">All Locations</option>
                <?php foreach ($eventCities as $ec): ?>
                <option value="<?php echo htmlspecialchars($ec); ?>" <?php echo ($searchCity === $ec) ? 'selected' : ''; ?>><?php echo htmlspecialchars($ec); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width:150px;">
            <label style="font-size:0.78rem;color:var(--text-muted);display:block;margin-bottom:4px;">📅 Date</label>
            <input type="date" name="event_date" value="<?php echo htmlspecialchars($searchDate); ?>" class="form-control" style="padding:8px 12px;font-size:0.85rem;background:var(--bg-dark);color:var(--text);border:1px solid var(--border);border-radius:8px;">
        </div>
        <div style="min-width:150px;">
            <label style="font-size:0.78rem;color:var(--text-muted);display:block;margin-bottom:4px;">🔄 Sort by Date</label>
            <select name="sort_date" class="form-control" style="padding:8px 12px;font-size:0.85rem;background:var(--bg-dark);color:var(--text);border:1px solid var(--border);border-radius:8px;">
                <option value="DESC" <?php echo $searchSort==='DESC'?'selected':''; ?>>🔽 Newest First</option>
                <option value="ASC"  <?php echo $searchSort==='ASC' ?'selected':''; ?>>🔼 Oldest First</option>
            </select>
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end;">
            <button type="submit" class="btn btn-primary btn-sm" style="padding:8px 18px;font-size:0.85rem;">🔍 Search</button>
            <a href="manage-events.php<?php echo $tab!=='all' ? '?tab='.$tab : ''; ?>" class="btn btn-sm" style="padding:8px 14px;font-size:0.85rem;background:var(--bg-dark);border:1px solid var(--border);color:var(--text-muted);">✕ Clear</a>
        </div>
    </div>
</form>

<!-- Tabs -->
<div style="display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap;">
    <?php
    $tabDefs = [
        'all'      => ['label'=>'All Events',      'color'=>'#6366f1', 'count'=>$counts['all']],
        'pending'  => ['label'=>'🟡 Pending',       'color'=>'#f59e0b', 'count'=>$counts['pending']],
        'approved' => ['label'=>'✅ Approved',       'color'=>'#22c55e', 'count'=>$counts['approved']],
        'rejected' => ['label'=>'❌ Rejected',       'color'=>'#ef4444', 'count'=>$counts['rejected']],
    ];
    foreach ($tabDefs as $key => $td):
        $active = $tab === $key;
    ?>
    <a href="manage-events.php?tab=<?php echo $key; ?>" style="
        text-decoration:none;padding:8px 18px;border-radius:50px;font-size:0.85rem;font-weight:600;
        background:<?php echo $active ? $td['color'] : 'var(--bg-card)'; ?>;
        color:<?php echo $active ? '#fff' : 'var(--text-muted)'; ?>;
        border:1px solid <?php echo $active ? $td['color'] : 'var(--border)'; ?>;">
        <?php echo $td['label']; ?> (<?php echo $td['count']; ?>)
    </a>
    <?php endforeach; ?>
</div>

<!-- Events Table -->
<div class="table-wrapper">
    <div class="table-header"><h4 class="table-title">🎪 Events</h4></div>
    <table>
        <thead><tr>
            <th>Poster</th><th>Event</th><th>Organizer</th><th>Date</th><th>Price</th><th>Status</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php if ($events->num_rows === 0): ?>
        <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-muted);">No events found.</td></tr>
        <?php else: while($e = $events->fetch_assoc()): ?>
        <?php
            $status = $e['status'] ?? 'approved';
            $statusBadge = match($status) {
                'pending'  => '<span style="background:rgba(245,158,11,0.15);color:#f59e0b;padding:3px 10px;border-radius:50px;font-size:0.78rem;font-weight:700;">🟡 Pending</span>',
                'approved' => '<span style="background:rgba(34,197,94,0.12);color:#22c55e;padding:3px 10px;border-radius:50px;font-size:0.78rem;font-weight:700;">✅ Approved</span>',
                'rejected' => '<span style="background:rgba(239,68,68,0.12);color:#ef4444;padding:3px 10px;border-radius:50px;font-size:0.78rem;font-weight:700;">❌ Rejected</span>',
                default    => ''
            };
        ?>
        <tr>
            <td>
            <?php if (!empty($e['event_image']) && file_exists('../assets/images/events/' . $e['event_image'])): ?>
                <img src="<?php echo BASE_URL; ?>assets/images/events/<?php echo htmlspecialchars($e['event_image']); ?>" style="height:55px;width:40px;object-fit:cover;border-radius:4px;" alt="">
                <?php else: ?>
                <span style="font-size:1.8rem;">🎪</span>
                <?php endif; ?>
            </td>
            <td>
                <div style="font-weight:600;"><?php echo htmlspecialchars($e['event_name']); ?></div>
                <div style="color:var(--text-muted);font-size:0.8rem;">📍 <?php echo htmlspecialchars($e['location']); ?></div>
            </td>
            <td style="color:var(--text-muted);">
                <?php echo htmlspecialchars($e['organizer'] ?? 'N/A'); ?>
                <?php if($e['organization']): ?><br><small><?php echo htmlspecialchars($e['organization']); ?></small><?php endif; ?>
            </td>
            <td><?php echo date('d M Y', strtotime($e['event_date'])); ?></td>
            <td style="color:var(--primary);font-weight:700;">₹<?php echo number_format($e['ticket_price'],0); ?></td>
            <td><?php echo $statusBadge; ?></td>
            <td class="action-btns" style="gap:6px;flex-wrap:wrap;">
                <?php if ($status === 'pending'): ?>
                <a href="manage-events.php?approve=<?php echo $e['event_id']; ?>" class="btn btn-sm" style="background:rgba(34,197,94,0.15);color:#22c55e;border:1px solid #22c55e;" onclick="return confirm('Approve this event?')">✅ Approve</a>
                <a href="manage-events.php?reject=<?php echo $e['event_id']; ?>" class="btn btn-sm" style="background:rgba(239,68,68,0.12);color:#ef4444;border:1px solid #ef4444;" onclick="return confirm('Reject this event?')">❌ Reject</a>
                <?php elseif ($status === 'approved'): ?>
                <a href="manage-events.php?reject=<?php echo $e['event_id']; ?>" class="btn btn-sm" style="background:rgba(245,158,11,0.15);color:#f59e0b;border:1px solid #f59e0b;" onclick="return confirm('Revoke approval?')">↩ Revoke</a>
                <?php elseif ($status === 'rejected'): ?>
                <a href="manage-events.php?approve=<?php echo $e['event_id']; ?>" class="btn btn-sm" style="background:rgba(34,197,94,0.15);color:#22c55e;border:1px solid #22c55e;" onclick="return confirm('Approve this event?')">✅ Approve</a>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-dark" onclick="openMediaModal(<?php echo $e['event_id']; ?>, '<?php echo htmlspecialchars(addslashes($e['event_name'])); ?>')">🖼️ Edit Images</button>
                <a href="<?php echo BASE_URL; ?>event-details.php?id=<?php echo $e['event_id']; ?>" class="btn btn-dark btn-sm" target="_blank">👁 View</a>
                <a href="manage-events.php?delete=<?php echo $e['event_id']; ?>" class="btn btn-sm" style="background:rgba(248,68,100,0.15);color:var(--primary);border:1px solid var(--primary);" onclick="return confirm('Delete this event permanently?')">🗑</a>
            </td>
        </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<!-- Media Update Modal -->
<div id="mediaModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:24px;width:90%;max-width:400px;position:relative;">
        <button type="button" onclick="document.getElementById('mediaModal').style.display='none'" style="position:absolute;top:15px;right:15px;background:none;border:none;color:var(--text);font-size:1.2rem;cursor:pointer;">✕</button>
        <h4 style="margin-bottom:15px;">Update Event Images</h4>
        <p id="mediaModalEventName" style="color:var(--primary);font-weight:600;margin-bottom:15px;"></p>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="media_event_id" id="mediaEventId">
            <div class="form-group"><label class="form-label">🖼️ New Vertical Poster</label><input type="file" class="form-control" name="new_poster" accept="image/*" style="padding:6px;"></div>
            <div class="form-group"><label class="form-label">🏞️ New Horizontal Banner</label><input type="file" class="form-control" name="new_banner" accept="image/*" style="padding:6px;"></div>
            <button type="submit" name="update_event_media" class="btn btn-primary" style="width:100%;">Upload Images</button>
            <p style="font-size:0.8rem;color:var(--text-muted);margin-top:10px;text-align:center;">Leave a field blank to keep the current image.</p>
        </form>
    </div>
</div>
<script>
function openMediaModal(id, name) {
    document.getElementById('mediaEventId').value = id;
    document.getElementById('mediaModalEventName').innerText = name;
    document.getElementById('mediaModal').style.display = 'flex';
}
</script>

<?php
$content = ob_get_clean();
adminLayout('Manage Events', $content, 'admin/manage-events.php');
?>
