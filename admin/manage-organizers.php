<?php
require_once 'admin-helper.php';

$msg = '';

// Delete organizer
if (isset($_GET['delete'])) {
    $oid = (int)$_GET['delete'];
    // Delete related data first
    $conn->query("DELETE FROM theatre_licences WHERE operator_id = $oid");
    $conn->query("DELETE FROM shows WHERE operator_id = $oid");
    $conn->query("DELETE FROM events WHERE organizer_id = $oid");
    $conn->query("DELETE FROM operators WHERE operator_id = $oid");
    $msg = '<div class="alert alert-success">🗑️ Organizer deleted successfully.</div>';
}

// Search filters
$searchName  = trim($_GET['name'] ?? '');
$searchEmail = trim($_GET['email'] ?? '');
$searchPhone = trim($_GET['phone'] ?? '');
$searchCity  = trim($_GET['city'] ?? '');

$searchWhere = '';
if ($searchName)  $searchWhere .= " AND o.name LIKE '%" . $conn->real_escape_string($searchName) . "%'";
if ($searchEmail) $searchWhere .= " AND o.email LIKE '%" . $conn->real_escape_string($searchEmail) . "%'";
if ($searchPhone) $searchWhere .= " AND o.phone LIKE '%" . $conn->real_escape_string($searchPhone) . "%'";
if ($searchCity)  $searchWhere .= " AND o.city LIKE '%" . $conn->real_escape_string($searchCity) . "%'";

// Get all organizers with stats
$organizers = $conn->query("
    SELECT o.*,
           COUNT(DISTINCT s.show_id) as total_shows,
           COUNT(DISTINCT e.event_id) as total_events,
           COUNT(DISTINCT tl.licence_id) as total_licences,
           SUM(CASE WHEN tl.status='approved' THEN 1 ELSE 0 END) as approved_licences
    FROM operators o
    LEFT JOIN shows s ON s.operator_id = o.operator_id
    LEFT JOIN events e ON e.organizer_id = o.operator_id
    LEFT JOIN theatre_licences tl ON tl.operator_id = o.operator_id
    WHERE 1=1 $searchWhere
    GROUP BY o.operator_id
    ORDER BY o.operator_id DESC
");

// City dropdown
$citiesRes = $conn->query("SELECT DISTINCT city FROM operators WHERE city IS NOT NULL AND city != '' ORDER BY city");
$cities = [];
while ($cr = $citiesRes->fetch_assoc()) $cities[] = $cr['city'];

ob_start();
?>

<?php echo $msg; ?>

<!-- Search Bar -->
<form method="GET" style="margin-bottom:18px;">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;">
        <div style="flex:1;min-width:140px;">
            <label style="font-size:0.78rem;color:var(--text-muted);display:block;margin-bottom:4px;">👤 Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($searchName); ?>" placeholder="Search name..." class="form-control" style="padding:8px 12px;font-size:0.85rem;">
        </div>
        <div style="flex:1;min-width:160px;">
            <label style="font-size:0.78rem;color:var(--text-muted);display:block;margin-bottom:4px;">📧 Email</label>
            <input type="text" name="email" value="<?php echo htmlspecialchars($searchEmail); ?>" placeholder="Search email..." class="form-control" style="padding:8px 12px;font-size:0.85rem;">
        </div>
        <div style="flex:1;min-width:130px;">
            <label style="font-size:0.78rem;color:var(--text-muted);display:block;margin-bottom:4px;">📱 Phone</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($searchPhone); ?>" placeholder="Search phone..." class="form-control" style="padding:8px 12px;font-size:0.85rem;">
        </div>
        <div style="min-width:140px;">
            <label style="font-size:0.78rem;color:var(--text-muted);display:block;margin-bottom:4px;">🏙️ City</label>
            <select name="city" class="form-control" style="padding:8px 12px;font-size:0.85rem;background:var(--bg-dark);color:var(--text);border:1px solid var(--border);border-radius:8px;">
                <option value="">All Cities</option>
                <?php foreach ($cities as $c): ?>
                <option value="<?php echo htmlspecialchars($c); ?>" <?php echo ($searchCity === $c) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end;">
            <button type="submit" class="btn btn-primary btn-sm" style="padding:8px 18px;font-size:0.85rem;">🔍 Search</button>
            <a href="manage-organizers.php" class="btn btn-sm" style="padding:8px 14px;font-size:0.85rem;background:var(--bg-dark);border:1px solid var(--border);color:var(--text-muted);">✕ Clear</a>
        </div>
    </div>
</form>

<style>
.org-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    margin-bottom: 16px;
    overflow: hidden;
    transition: box-shadow .2s;
}
.org-card:hover { box-shadow: 0 4px 24px rgba(248,68,100,.1); }
.org-header {
    display: flex; align-items: center; gap: 16px;
    padding: 18px 20px; cursor: pointer;
    user-select: none;
}
.org-avatar {
    width: 52px; height: 52px; border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), #a855f7);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; font-weight: 700; color: #fff; flex-shrink: 0;
}
.org-info { flex: 1; min-width: 0; }
.org-name { font-weight: 700; font-size: 1rem; margin-bottom:2px; }
.org-sub  { color: var(--text-muted); font-size: .82rem; }
.org-badges { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.org-body {
    display: none; padding: 0 20px 20px;
    border-top: 1px solid var(--border);
    animation: fadeIn .2s;
}
.org-body.open { display: block; }
@keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
.info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; margin: 16px 0; }
.info-cell { background: var(--bg-dark); border-radius: 8px; padding: 12px; }
.info-cell .label { color: var(--text-muted); font-size: .75rem; margin-bottom:4px; }
.info-cell .val   { font-weight: 600; font-size: .95rem; }
.movies-panel, .events-panel { display: none; margin-top: 16px; animation: fadeIn .2s; }
.movies-panel.visible, .events-panel.visible { display: block; }
</style>

<?php if (!$organizers || $organizers->num_rows === 0): ?>
<p style="text-align:center;padding:40px;color:var(--text-muted);">No organizers found.</p>
<?php else: ?>

<?php while ($o = $organizers->fetch_assoc()):
    $initials = strtoupper(substr($o['name'], 0, 1));
    $oid = $o['operator_id'];
    $licStatus = ($o['approved_licences'] > 0) ? 'active' : ($o['total_licences'] > 0 ? 'pending' : 'none');
    $statusColor = ['active'=>'#22c55e','pending'=>'#f59e0b','none'=>'var(--text-muted)'][$licStatus];
    $statusLabel = ['active'=>'✅ Active','pending'=>'⏳ Pending','none'=>'No Licence'][$licStatus];

    // Phone - handle both 'phone' and 'phone_number' column names gracefully
    $phone = $o['phone'] ?? $o['phone_number'] ?? '';

    // Fetch shows
    $shows = $conn->query("
        SELECT s.*, m.title, t.theatre_name, t.city
        FROM shows s
        JOIN movies m ON s.movie_id = m.movie_id
        JOIN theatres t ON s.theatre_id = t.theatre_id
        WHERE s.operator_id = $oid
        ORDER BY s.show_date DESC LIMIT 20
    ");

    // Fetch events  
    $events = $conn->query("
        SELECT * FROM events WHERE organizer_id = $oid
        ORDER BY event_date DESC LIMIT 20
    ");
?>
<div class="org-card" id="orgCard_<?php echo $oid; ?>">
    <div class="org-header" onclick="toggleOrg(<?php echo $oid; ?>)">
        <div class="org-avatar"><?php echo $initials; ?></div>
        <div class="org-info">
            <div class="org-name"><?php echo htmlspecialchars($o['name']); ?></div>
            <div class="org-sub">
                🏢 <?php echo htmlspecialchars($o['organization']); ?> &nbsp;|&nbsp;
                📍 <?php echo htmlspecialchars($o['city'] ?? '—'); ?>
                <?php if (!empty($phone)): ?>&nbsp;|&nbsp; 📱 <?php echo htmlspecialchars($phone); ?><?php endif; ?>
            </div>
        </div>
        <div class="org-badges">
            <span style="background:rgba(248,68,100,.12);color:var(--primary);padding:3px 10px;border-radius:50px;font-size:.75rem;font-weight:700;">🎬 <?php echo $o['total_shows']; ?> Shows</span>
            <span style="background:rgba(99,102,241,.12);color:#818cf8;padding:3px 10px;border-radius:50px;font-size:.75rem;font-weight:700;">🎪 <?php echo $o['total_events']; ?> Events</span>
            <span style="background:rgba(34,197,94,.1);color:<?php echo $statusColor; ?>;padding:3px 10px;border-radius:50px;font-size:.75rem;font-weight:600;"><?php echo $statusLabel; ?></span>
            <span style="color:var(--text-muted);font-size:1.2rem;transition:transform .2s;" id="chevron_<?php echo $oid; ?>">▼</span>
        </div>
    </div>

    <div class="org-body" id="orgBody_<?php echo $oid; ?>">
        <!-- Detail Info Grid -->
        <div class="info-grid">
            <div class="info-cell"><div class="label">📧 Email</div><div class="val" style="font-size:.82rem;word-break:break-all;"><?php echo htmlspecialchars($o['email']); ?></div></div>
            <div class="info-cell"><div class="label">📱 Phone</div><div class="val"><?php echo !empty($phone) ? htmlspecialchars($phone) : '—'; ?></div></div>
            <div class="info-cell"><div class="label">📅 Joined</div><div class="val"><?php echo date('d M Y', strtotime($o['created_at'] ?? date('Y-m-d'))); ?></div></div>
            <div class="info-cell"><div class="label">🎫 Licences</div><div class="val"><?php echo $o['approved_licences']; ?> approved / <?php echo $o['total_licences']; ?> total</div></div>
            <div class="info-cell"><div class="label">🎬 Total Shows</div><div class="val"><?php echo $o['total_shows']; ?></div></div>
            <div class="info-cell"><div class="label">🎪 Total Events</div><div class="val"><?php echo $o['total_events']; ?></div></div>
        </div>

        <!-- Action buttons -->
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
            <button class="btn btn-sm" style="background:rgba(248,68,100,.15);color:var(--primary);border:1px solid var(--primary);"
                    onclick="togglePanel(<?php echo $oid; ?>,'movies')">🎬 Movies / Shows</button>
            <button class="btn btn-sm" style="background:rgba(99,102,241,.15);color:#818cf8;border:1px solid #818cf8;"
                    onclick="togglePanel(<?php echo $oid; ?>,'events')">🎪 Events</button>
            <a href="manage-licences.php" class="btn btn-sm" style="background:rgba(245,158,11,.12);color:#f59e0b;border:1px solid #f59e0b;">🎫 View Licences</a>
            <a href="manage-organizers.php?delete=<?php echo $oid; ?>" class="btn btn-sm" 
               style="background:rgba(248,68,100,0.15);color:var(--primary);border:1px solid var(--primary);margin-left:auto;"
               onclick="return confirm('Delete this organizer? This will also remove their shows, events, and licences.')">🗑️ Delete</a>
        </div>

        <!-- Movies Panel -->
        <div class="movies-panel" id="movies_<?php echo $oid; ?>">
            <h5 style="margin:12px 0 10px;font-size:.9rem;">🎬 Shows Listed by <?php echo htmlspecialchars($o['name']); ?></h5>
            <?php if (!$shows || $shows->num_rows === 0): ?>
            <p style="color:var(--text-muted);font-size:.85rem;padding:10px 0;">No shows listed yet.</p>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table style="width:100%;font-size:.82rem;">
                <thead><tr style="color:var(--text-muted);">
                    <th style="padding:6px 8px;text-align:left;">Movie</th>
                    <th style="padding:6px 8px;">Theatre</th>
                    <th style="padding:6px 8px;">Date</th>
                    <th style="padding:6px 8px;">Time</th>
                    <th style="padding:6px 8px;">Price</th>
                    <th style="padding:6px 8px;">Status</th>
                </tr></thead>
                <tbody>
                <?php while($s=$shows->fetch_assoc()):
                    $sc = ['active'=>'#22c55e','pending'=>'#f59e0b','rejected'=>'#ef4444'][$s['status']] ?? 'var(--text-muted)';
                ?>
                <tr style="border-top:1px solid var(--border);">
                    <td style="padding:7px 8px;font-weight:600;"><?php echo htmlspecialchars($s['title']); ?></td>
                    <td style="padding:7px 8px;color:var(--text-muted);"><?php echo htmlspecialchars($s['theatre_name'].', '.$s['city']); ?></td>
                    <td style="padding:7px 8px;"><?php echo date('d M Y', strtotime($s['show_date'])); ?></td>
                    <td style="padding:7px 8px;"><?php echo date('h:i A', strtotime($s['show_time'])); ?></td>
                    <td style="padding:7px 8px;font-weight:600;color:var(--primary);">₹<?php echo number_format($s['price'],0); ?></td>
                    <td style="padding:7px 8px;"><span style="color:<?php echo $sc; ?>;font-weight:600;font-size:.75rem;"><?php echo strtoupper($s['status']); ?></span></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Events Panel -->
        <div class="events-panel" id="events_<?php echo $oid; ?>">
            <h5 style="margin:12px 0 10px;font-size:.9rem;">🎪 Events by <?php echo htmlspecialchars($o['name']); ?></h5>
            <?php if (!$events || $events->num_rows === 0): ?>
            <p style="color:var(--text-muted);font-size:.85rem;padding:10px 0;">No events submitted yet.</p>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table style="width:100%;font-size:.82rem;">
                <thead><tr style="color:var(--text-muted);">
                    <th style="padding:6px 8px;text-align:left;">Event</th>
                    <th style="padding:6px 8px;">Location</th>
                    <th style="padding:6px 8px;">Date</th>
                    <th style="padding:6px 8px;">Price</th>
                    <th style="padding:6px 8px;">Status</th>
                    <th style="padding:6px 8px;">Action</th>
                </tr></thead>
                <tbody>
                <?php while($ev=$events->fetch_assoc()):
                    $ec = ['approved'=>'#22c55e','pending'=>'#f59e0b','rejected'=>'#ef4444'][$ev['status']] ?? 'var(--text-muted)';
                ?>
                <tr style="border-top:1px solid var(--border);">
                    <td style="padding:7px 8px;font-weight:600;"><?php echo htmlspecialchars($ev['event_name']); ?></td>
                    <td style="padding:7px 8px;color:var(--text-muted);font-size:.78rem;"><?php echo htmlspecialchars($ev['location']); ?></td>
                    <td style="padding:7px 8px;"><?php echo date('d M Y', strtotime($ev['event_date'])); ?></td>
                    <td style="padding:7px 8px;font-weight:600;color:var(--primary);">₹<?php echo number_format($ev['ticket_price'],0); ?></td>
                    <td style="padding:7px 8px;"><span style="color:<?php echo $ec; ?>;font-weight:600;font-size:.75rem;"><?php echo strtoupper($ev['status']); ?></span></td>
                    <td style="padding:7px 8px;">
                        <a href="manage-events.php?approve=<?php echo $ev['event_id']; ?>" class="btn btn-sm" style="font-size:.72rem;padding:2px 8px;background:rgba(34,197,94,.12);color:#22c55e;border:1px solid #22c55e;">✅ Approve</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endwhile; ?>

<?php endif; ?>

<script>
function toggleOrg(id) {
    var body = document.getElementById('orgBody_'+id);
    var chev = document.getElementById('chevron_'+id);
    var isOpen = body.classList.contains('open');
    body.classList.toggle('open', !isOpen);
    chev.style.transform = isOpen ? '' : 'rotate(180deg)';
}
function togglePanel(oid, type) {
    var mp = document.getElementById('movies_'+oid);
    var ep = document.getElementById('events_'+oid);
    if (type === 'movies') {
        var showing = mp.classList.contains('visible');
        mp.classList.toggle('visible', !showing);
        ep.classList.remove('visible');
    } else {
        var showing = ep.classList.contains('visible');
        ep.classList.toggle('visible', !showing);
        mp.classList.remove('visible');
    }
}
</script>

<?php
$content = ob_get_clean();
adminLayout('Organizers', $content, 'admin/manage-organizers.php');
?>
