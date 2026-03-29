<?php
require_once 'config/config.php';
require_once 'includes/session_guard.php';

require_login(['operator']);
set_no_cache_headers();

$op_id = (int)$_SESSION['operator_id'];
$msg   = '';
$tab   = $_GET['tab'] ?? 'shows';
require_once 'config/cities.php';
$dashCities = BYS_CITIES; // Fixed 50 cities for all dashboard dropdowns


// ── Upload helper ───────────────────────────────────────────────────────────
function uploadImg($field, $dir, $pfx) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return '';
    $ok = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array(mime_content_type($_FILES[$field]['tmp_name']), $ok)) return '';
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $fn  = $pfx.'_'.time().'_'.rand(1000,9999).'.'.$ext;
    return move_uploaded_file($_FILES[$field]['tmp_name'], $dir.$fn) ? $fn : '';
}

// ── ADD EVENT ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_event'])) {
    $name     = trim($_POST['event_name']);
    $desc     = trim($_POST['description']);
    $date     = $_POST['event_date'];
    $loc      = trim($_POST['location'] ?? '');
    $venue_nm = trim($_POST['venue_name'] ?? '');
    $price    = floatval($_POST['ticket_price']);
    $seats    = (int)($_POST['total_seats'] ?? 500);
    $ecity    = $conn->real_escape_string(trim($_POST['event_city'] ?? ''));
    $cat      = $conn->real_escape_string(trim($_POST['category'] ?? 'Other'));
    $ev_img   = uploadImg('poster','assets/images/events/','poster');        // stored in event_image
    $banner   = uploadImg('banner_image','assets/images/events/','banner');
    // Combine venue name + location
    $fullLoc  = $conn->real_escape_string($venue_nm . ($loc ? ', ' . $loc : ''));
    $stmt = $conn->prepare("INSERT INTO events (event_name,category,description,event_date,location,city,ticket_price,total_seats,event_image,banner_image,organizer_id,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,'pending')");
    $stmt->bind_param('ssssssdissi',$name,$cat,$desc,$date,$fullLoc,$ecity,$price,$seats,$ev_img,$banner,$op_id);
    $stmt->execute() ? ($msg='success:Event submitted for admin approval! 🟡') : ($msg='error:Failed to submit event. '.$conn->error);
    $tab='events';
}

// ── UPDATE EVENT MEDIA ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_event_media'])) {
    $eid = (int)$_POST['media_event_id'];
    $owns = $conn->query("SELECT event_id FROM events WHERE event_id=$eid AND organizer_id=$op_id")->num_rows;
    if ($owns) {
        $msgParts = [];
        $ev_img   = uploadImg('new_poster','assets/images/events/','poster');
        $banner   = uploadImg('new_banner','assets/images/events/','banner');
        if ($ev_img) { $conn->query("UPDATE events SET event_image='$ev_img' WHERE event_id=$eid"); $msgParts[]='Poster'; }
        if ($banner) { $conn->query("UPDATE events SET banner_image='$banner' WHERE event_id=$eid"); $msgParts[]='Banner'; }
        if (count($msgParts)>0) { $msg='success:Updated '.implode(' & ',$msgParts).' successfully! ✅'; }
    }
    $tab='events';
}

// ── UPDATE FULL EVENT ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_event'])) {
    $eid      = (int)$_POST['edit_event_id'];
    $owns     = $conn->query("SELECT event_id FROM events WHERE event_id=$eid AND organizer_id=$op_id")->num_rows;
    if ($owns) {
        $ename  = $conn->real_escape_string(trim($_POST['edit_event_name']));
        $ecat   = $conn->real_escape_string(trim($_POST['edit_category'] ?? 'Other'));
        $edesc  = $conn->real_escape_string(trim($_POST['edit_description']));
        $edate  = $conn->real_escape_string($_POST['edit_event_date']);
        $eloc   = $conn->real_escape_string(trim($_POST['edit_venue_name']) . (trim($_POST['edit_location']) ? ', ' . trim($_POST['edit_location']) : ''));
        $ecity  = $conn->real_escape_string(trim($_POST['edit_event_city']));
        $eprice = floatval($_POST['edit_ticket_price']);
        $eseats = (int)($_POST['edit_total_seats'] ?? 500);
        $sets   = "event_name='$ename', category='$ecat', description='$edesc', event_date='$edate', location='$eloc', city='$ecity', ticket_price=$eprice, total_seats=$eseats";
        // Handle new images
        $ev_img = uploadImg('edit_poster','assets/images/events/','poster');
        $banner = uploadImg('edit_banner','assets/images/events/','banner');
        if ($ev_img) $sets .= ", event_image='$ev_img'";
        if ($banner) $sets .= ", banner_image='$banner'";
        $conn->query("UPDATE events SET $sets WHERE event_id=$eid AND organizer_id=$op_id");
        $msg = 'success:Event updated successfully! ✅';
    }
    $tab='events';
}

// ── DELETE EVENT ─────────────────────────────────────────────────────────────
if (isset($_GET['delete_event'])) {
    $eid=(int)$_GET['delete_event'];
    $conn->query("DELETE FROM events WHERE event_id=$eid AND organizer_id=$op_id AND status!='approved'");
    header('Location: operator-dashboard.php?tab=events&msg=success:Event+deleted.'); exit;
}

// ── SUBMIT SHOW (with inline custom theatre + one-time licence) ─────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_show'])) {
    $movie_id    = (int)$_POST['movie_id'];
    $theatre_id  = (int)$_POST['theatre_id'];     // 0 if custom
    $licence_no  = trim($_POST['licence_number'] ?? '');
    $show_dates  = $_POST['show_dates']  ?? [];
    $show_times  = $_POST['show_times']  ?? [];
    $languages   = $_POST['languages']   ?? [];
    $prices      = $_POST['prices']      ?? [];

    // --- Handle inline custom theatre creation ---
    if ($theatre_id === 0) {
        $ct_name = $conn->real_escape_string(trim($_POST['custom_theatre_name'] ?? ''));
        $ct_city = $conn->real_escape_string(trim($_POST['custom_theatre_city'] ?? ''));
        $ct_addr = $conn->real_escape_string(trim($_POST['custom_theatre_address'] ?? ''));
        $ct_map  = $conn->real_escape_string(trim($_POST['custom_theatre_map'] ?? ''));
        if (!$ct_name || !$ct_city) {
            $msg='error:Custom theatre name and city are required.'; $tab='shows';
            goto skip_show;
        }
        $conn->query("INSERT INTO theatres (theatre_name,city,location,map_link,is_verified,added_by) VALUES ('$ct_name','$ct_city','$ct_addr','$ct_map',0,$op_id)");
        $theatre_id = (int)$conn->insert_id;
    }

    // --- Licence check ---
    $licRow = $conn->query("SELECT status FROM theatre_licences WHERE theatre_id=$theatre_id AND operator_id=$op_id ORDER BY submitted_at DESC LIMIT 1")->fetch_assoc();
    $licStatus = $licRow['status'] ?? 'none';

    if ($licStatus === 'pending') {
        $msg='error:Licence for this theatre is pending admin approval. Shows will go live once approved.';
        $tab='shows'; goto skip_show;
    }

    if ($licStatus !== 'approved') {
        // Rejected or none — need licence number
        if (!$licence_no) {
            $msg='error:Please enter the licence number for this theatre.';
            $tab='shows'; goto skip_show;
        }

        $escapedLicNo = $conn->real_escape_string($licence_no);
        $autoApprove = false;
        
        // Auto-approve if same licence is already approved for this theatre by another operator
        $checkExistingGlobal = $conn->query("SELECT licence_id FROM theatre_licences WHERE theatre_id=$theatre_id AND licence_number='$escapedLicNo' AND status='approved' LIMIT 1");
        if ($checkExistingGlobal && $checkExistingGlobal->num_rows > 0) {
            $autoApprove = true;
        }

        $newLicState = $autoApprove ? 'approved' : 'pending';

        // Upsert licence
        $existing = $conn->query("SELECT licence_id FROM theatre_licences WHERE theatre_id=$theatre_id AND operator_id=$op_id")->fetch_assoc();
        if ($existing) {
            $lic_id = $existing['licence_id'];
            $conn->query("UPDATE theatre_licences SET licence_number='$escapedLicNo', status='$newLicState', submitted_at=NOW() WHERE licence_id=$lic_id");
        } else {
            $conn->query("INSERT INTO theatre_licences (theatre_id,operator_id,licence_number,status) VALUES ($theatre_id,$op_id,'$escapedLicNo','$newLicState')");
            $lic_id = (int)$conn->insert_id;
        }

        if (!$autoApprove) {
            // Save shows as pending (will be activated on licence+show approval)
            $tname = $conn->query("SELECT theatre_name FROM theatres WHERE theatre_id=$theatre_id")->fetch_assoc()['theatre_name'] ?? 'Unknown';
            $mname = $conn->query("SELECT title FROM movies WHERE movie_id=$movie_id")->fetch_assoc()['title'] ?? 'Unknown';
            $oname = $conn->real_escape_string($_SESSION['operator_name']);
            $added = 0;
            foreach($show_dates as $idx => $sdate) {
                if (!$sdate) continue;
                foreach((array)($show_times[$idx] ?? []) as $tidx => $stime) {
                    if (!$stime) continue;
                    $lang  = $conn->real_escape_string($languages[$idx][$tidx] ?? 'Hindi');
                    $price = floatval($prices[$idx][$tidx] ?? 200);
                    $sdesc = $conn->real_escape_string($sdate);
                    $stesc = $conn->real_escape_string($stime);
                    $conn->query("INSERT INTO shows (movie_id,theatre_id,show_date,show_time,language,price,operator_id,status) VALUES ($movie_id,$theatre_id,'$sdesc','$stesc','$lang',$price,$op_id,'pending')");
                    $added++;
                }
            }
            // Admin notification for licence
            $conn->query("INSERT INTO admin_notifications (type,title,message,reference_id) VALUES ('licence','🏟️ Licence Request: $tname','Organizer $oname submitted licence #$escapedLicNo for $tname to show \"$mname\".',$lic_id)");
            $msg="success:✅ $added show(s) + licence submitted! Shows go live after admin approves your theatre licence.";
            $tab='shows'; goto skip_show;
        }
    }

    // Licence approved — save shows directly as active
    $tname = $conn->query("SELECT theatre_name FROM theatres WHERE theatre_id=$theatre_id")->fetch_assoc()['theatre_name'] ?? 'Unknown';
    $mname = $conn->query("SELECT title FROM movies WHERE movie_id=$movie_id")->fetch_assoc()['title'] ?? 'Unknown';
    $oname = $conn->real_escape_string($_SESSION['operator_name']);
    $added = 0;
    foreach($show_dates as $idx => $sdate) {
        if (!$sdate) continue;
        foreach((array)($show_times[$idx] ?? []) as $tidx => $stime) {
            if (!$stime) continue;
            $lang  = $conn->real_escape_string($languages[$idx][$tidx] ?? 'Hindi');
            $price = floatval($prices[$idx][$tidx] ?? 200);
            $sdesc = $conn->real_escape_string($sdate);
            $stesc = $conn->real_escape_string($stime);
            $conn->query("INSERT INTO shows (movie_id,theatre_id,show_date,show_time,language,price,operator_id,status) VALUES ($movie_id,$theatre_id,'$sdesc','$stesc','$lang',$price,$op_id,'active')");
            $added++;
        }
    }
    $msg="success:✅ $added show(s) added successfully! They are now live on the site.";
    $tab='shows';

    skip_show:;
}

// ── DELETE SHOW ─────────────────────────────────────────────────────────────
if (isset($_GET['delete_show'])) {
    $sid=(int)$_GET['delete_show'];
    $conn->query("DELETE FROM shows WHERE show_id=$sid AND operator_id=$op_id");
    header('Location: operator-dashboard.php?tab=shows&msg=success:Show+deleted.'); exit;
}

// ── ADD VENUE (Venues tab) ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_venue'])) {
    $vn = $conn->real_escape_string(trim($_POST['venue_name']));
    $vc = $conn->real_escape_string(trim($_POST['venue_city']));
    $va = $conn->real_escape_string(trim($_POST['venue_address']));
    $vm = $conn->real_escape_string(trim($_POST['map_link'] ?? ''));
    if (!$vn||!$vc) { $msg='error:Name and city required.'; }
    else { $conn->query("INSERT INTO theatres (theatre_name,city,location,map_link,is_verified,added_by) VALUES ('$vn','$vc','$va','$vm',0,$op_id)"); $msg='success:Venue added!'; }
    $tab='venues';
}

// ── DATA ─────────────────────────────────────────────────────────────────────
$events      = $conn->query("SELECT * FROM events WHERE organizer_id=$op_id ORDER BY event_id DESC");
$movies      = $conn->query("SELECT movie_id,title,language,duration FROM movies WHERE status='active' ORDER BY title");
$theatres    = $conn->query("SELECT * FROM theatres ORDER BY city,theatre_name");
$myShows     = $conn->query("SELECT s.*,m.title as movie_title,t.theatre_name,t.city FROM shows s JOIN movies m ON s.movie_id=m.movie_id JOIN theatres t ON s.theatre_id=t.theatre_id WHERE s.operator_id=$op_id ORDER BY s.show_date DESC,s.show_time DESC");
$myLicences  = $conn->query("SELECT tl.*,t.theatre_name,t.city FROM theatre_licences tl JOIN theatres t ON tl.theatre_id=t.theatre_id WHERE tl.operator_id=$op_id ORDER BY tl.submitted_at DESC");
$myVenues    = $conn->query("SELECT * FROM theatres WHERE added_by=$op_id ORDER BY theatre_id DESC");

$approvedEvt = $conn->query("SELECT COUNT(*) c FROM events WHERE organizer_id=$op_id AND status='approved'")->fetch_assoc()['c'];
$pendingEvt  = $conn->query("SELECT COUNT(*) c FROM events WHERE organizer_id=$op_id AND status='pending'")->fetch_assoc()['c'];
$showCnt     = $conn->query("SELECT COUNT(*) c FROM shows WHERE operator_id=$op_id")->fetch_assoc()['c'];

$movies_arr=[]; $movies->data_seek(0); while($m=$movies->fetch_assoc()) $movies_arr[]=$m;
$theatres_arr=[]; $theatres->data_seek(0); while($t=$theatres->fetch_assoc()) $theatres_arr[]=$t;
// Cities that actually have at least one theatre in DB
$theatreCities = array_values(array_unique(array_column($theatres_arr, 'city')));
sort($theatreCities);

// Build licence map: theatre_id => status (for JS)
$licenceMap = [];
$myLicences->data_seek(0);
while($lic=$myLicences->fetch_assoc()) $licenceMap[(int)$lic['theatre_id']] = $lic['status'];

if (isset($_GET['msg']) && !$msg) { $p=explode(':',$_GET['msg'],2); if(count($p)===2) $msg=$_GET['msg']; }

$pageTitle='Organizer Dashboard - BookYourShow';
include 'includes/header.php';
?>
<style>
.dash-tabs{display:flex;gap:4px;border-bottom:2px solid var(--border);margin-bottom:24px;flex-wrap:wrap;}
.dash-tab{padding:10px 22px;border-radius:8px 8px 0 0;background:transparent;border:none;color:var(--text-muted);font-weight:600;cursor:pointer;font-size:.9rem;transition:.2s;}
.dash-tab.active{background:var(--primary);color:#fff;}
.dash-tab:hover:not(.active){background:var(--bg-card);color:var(--text);}
.tab-pane{display:none;}.tab-pane.active{display:block;}
.show-row{display:flex;gap:10px;align-items:flex-end;margin-bottom:10px;flex-wrap:wrap;padding:12px;background:var(--bg-dark);border-radius:8px;}
.show-row .form-group{margin-bottom:0;flex:1;min-width:130px;}
.remove-row{background:rgba(239,68,68,.15);border:1px solid #ef4444;color:#ef4444;border-radius:6px;padding:6px 12px;cursor:pointer;font-size:.8rem;}
.add-date-btn{background:rgba(248,68,100,.12);border:1px solid var(--primary);color:var(--primary);border-radius:8px;padding:8px 18px;cursor:pointer;font-size:.85rem;font-weight:600;margin-top:8px;}
.stat-grid-op{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:28px;}
/* Searchable theatre */
.theatre-picker{position:relative;}
.theatre-search-input{width:100%;padding:10px 14px;background:var(--bg-dark);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:.9rem;}
.theatre-search-input:focus{outline:none;border-color:var(--primary);}
.theatre-dropdown{position:absolute;top:100%;left:0;right:0;background:var(--bg-card);border:1px solid var(--border);border-radius:0 0 10px 10px;max-height:240px;overflow-y:auto;z-index:999;display:none;box-shadow:0 8px 24px rgba(0,0,0,.4);}
.theatre-dropdown.open{display:block;}
.theatre-opt{padding:10px 14px;cursor:pointer;font-size:.88rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;}
.theatre-opt:hover{background:rgba(248,68,100,.1);}
.theatre-opt .lic-tag{font-size:.72rem;padding:2px 8px;border-radius:50px;font-weight:700;}
.theatre-opt.custom-opt{color:var(--primary);font-weight:700;background:rgba(248,68,100,.06);}
/* Custom theatre inline form */
.custom-theatre-box{background:rgba(248,68,100,.06);border:1px solid rgba(248,68,100,.25);border-radius:10px;padding:16px;margin-top:10px;display:none;}
.custom-theatre-box.show{display:block;}
/* Licence box */
.licence-box{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.3);border-radius:10px;padding:16px;margin-top:10px;display:none;}
.licence-box.show{display:block;}
.lic-approved{background:rgba(34,197,94,.12);color:#22c55e;padding:3px 10px;border-radius:50px;font-size:.75rem;font-weight:700;}
.lic-pending{background:rgba(245,158,11,.12);color:#f59e0b;padding:3px 10px;border-radius:50px;font-size:.75rem;font-weight:700;}
.lic-rejected{background:rgba(239,68,68,.12);color:#ef4444;padding:3px 10px;border-radius:50px;font-size:.75rem;font-weight:700;}
/* City filter disabled overlay */
#cityFilterGroup.city-disabled{position:relative;pointer-events:none;}
#cityFilterGroup.city-disabled::after{content:'';position:absolute;inset:0;background:rgba(0,0,0,.35);border-radius:8px;z-index:2;}
.city-disabled-banner{display:none;margin-top:8px;font-size:.8rem;color:#f59e0b;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-radius:6px;padding:6px 10px;}
.city-disabled-banner.show{display:block;}
/* Custom city highlight when active */
#ctCity.city-active-highlight, #ctCity.city-active-highlight + .select2-container .select2-selection{border-color:#f59e0b !important;box-shadow:0 0 0 2px rgba(245,158,11,.25) !important;}
.ct-city-label-active{color:#f59e0b !important;font-weight:700;}
</style>

<div style="max-width:1200px;margin:0 auto;padding:40px 20px;">
<?php if($msg): [$mt,$mm]=explode(':',$msg,2); ?>
<div class="alert alert-<?php echo $mt==='success'?'success':'danger'; ?>"><?php echo htmlspecialchars($mm); ?></div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 style="font-size:1.6rem;font-weight:700;">👋 Welcome, <?php echo htmlspecialchars($_SESSION['operator_name']); ?></h2>
        <p style="color:var(--text-muted);">🏢 <?php echo htmlspecialchars($_SESSION['operator_org']); ?><?php if($_SESSION['operator_city']??''): ?> · 📍 <?php echo htmlspecialchars($_SESSION['operator_city']); ?><?php endif; ?></p>
    </div>
    <a href="logout.php" class="btn btn-outline btn-sm">🚪 Logout</a>
</div>

<div class="stat-grid-op">
    <div class="stat-card"><div class="stat-icon red">🎪</div><div class="stat-info"><div class="stat-value"><?php echo $events->num_rows; ?></div><div class="stat-label">My Events</div></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div class="stat-info"><div class="stat-value"><?php echo $approvedEvt; ?></div><div class="stat-label">Approved</div></div></div>
    <div class="stat-card"><div class="stat-icon gold">⏳</div><div class="stat-info"><div class="stat-value"><?php echo $pendingEvt; ?></div><div class="stat-label">Pending</div></div></div>
    <div class="stat-card"><div class="stat-icon green">📅</div><div class="stat-info"><div class="stat-value"><?php echo $showCnt; ?></div><div class="stat-label">Shows</div></div></div>
</div>

<div class="dash-tabs">
    <button class="dash-tab <?php echo $tab==='events'?'active':''; ?>" onclick="switchTab('events',this)">🎪 My Events</button>
    <button class="dash-tab <?php echo $tab==='shows'?'active':''; ?>"  onclick="switchTab('shows',this)">🎬 Manage Shows</button>
    <button class="dash-tab <?php echo $tab==='venues'?'active':''; ?>" onclick="switchTab('venues',this)">🏢 Theatres/Venues</button>
</div>

<!-- ═══════════ TAB: EVENTS ═══════════ -->
<div id="tab-events" class="tab-pane <?php echo $tab==='events'?'active':''; ?>">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:28px;">
        <h4 style="margin-bottom:18px;">➕ Submit New Event</h4>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group"><label class="form-label">Event Name *</label><input type="text" class="form-control" name="event_name" required></div>
                <div class="form-group"><label class="form-label">Category *</label>
                    <select class="form-control" name="category" required>
                        <option value="">Select Category</option>
                        <option value="Comedy">Comedy</option>
                        <option value="Sports">Sports</option>
                        <option value="Music">Music</option>
                        <option value="Festivals & Fairs">Festivals &amp; Fairs</option>
                        <option value="College Fests">College Fests</option>
                        <option value="Workshops">Workshops</option>
                        <option value="Parties">Parties</option>
                        <option value="Gaming & Esports">Gaming &amp; Esports</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Event Date *</label><input type="date" class="form-control" name="event_date" min="<?php echo date('Y-m-d'); ?>" required></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">City *</label>
                    <select class="form-control select2-city" name="event_city" required><option value="">Select City</option><?php foreach($dashCities as $c): ?><option value="<?php echo $c; ?>"><?php echo $c; ?></option><?php endforeach; ?></select>
                </div>
                <div class="form-group"><label class="form-label">Ticket Price (₹) *</label><input type="number" class="form-control" name="ticket_price" min="1" step="0.01" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">🏟️ Venue Name *</label><input type="text" class="form-control" name="venue_name" placeholder="e.g. MMRDA Grounds, Wankhede Stadium" required></div>
                <div class="form-group"><label class="form-label">Location / Area</label><input type="text" class="form-control" name="location" placeholder="e.g. BKC, Mumbai"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">🗺️ Google Map Link</label><input type="url" class="form-control" name="event_map_link" placeholder="https://maps.google.com/..."></div>
                <div class="form-group"><label class="form-label">Total Seats</label><input type="number" class="form-control" name="total_seats" value="500"></div>
            </div>
            <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"></textarea></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">🖼️ Poster</label><input type="file" class="form-control" name="poster" accept="image/*" style="padding:6px;"></div>
                <div class="form-group"><label class="form-label">🏞️ Banner</label><input type="file" class="form-control" name="banner_image" accept="image/*" style="padding:6px;"></div>
            </div>
            <button type="submit" name="add_event" class="btn btn-primary">📤 Submit for Approval</button>
        </form>
    </div>
    <div class="table-wrapper">
        <div class="table-header"><h4 class="table-title">📋 My Events</h4></div>
        <?php if($events->num_rows===0): ?>
        <p style="text-align:center;padding:30px;color:var(--text-muted);">No events yet.</p>
        <?php else: ?>
        <table>
            <thead><tr><th>Event</th><th>Category</th><th>City</th><th>Date</th><th>Price</th><th>Seats</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php $events->data_seek(0); while($ev=$events->fetch_assoc()):
                $s=$ev['status']??'pending';
                $sb=match($s){'approved'=>'<span class="badge badge-success">✅ Approved</span>','rejected'=>'<span style="color:#ef4444;font-size:.78rem;">❌ Rejected</span>',default=>'<span style="color:#f59e0b;font-size:.78rem;">⏳ Pending</span>'};
            ?>
            <tr>
                <td><div style="font-weight:600;"><?php echo htmlspecialchars($ev['event_name']); ?></div><div style="font-size:.8rem;color:var(--text-muted);">📍 <?php echo htmlspecialchars($ev['location']); ?></div></td>
                <td><span style="font-size:.8rem;background:rgba(255,255,255,0.1);padding:4px 8px;border-radius:4px;"><?php echo htmlspecialchars($ev['category']??'Other'); ?></span></td>
                <td><?php echo htmlspecialchars($ev['city']??'—'); ?></td>
                <td><?php echo date('d M Y',strtotime($ev['event_date'])); ?></td>
                <td style="font-weight:600;color:var(--primary);">₹<?php echo number_format($ev['ticket_price'],0); ?></td>
                <td><?php echo number_format($ev['total_seats']??500,0); ?></td>
                <td><?php echo $sb; ?></td>
                <td style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                    <button type="button" class="btn btn-sm btn-primary edit-event-btn"
                        data-id="<?php echo $ev['event_id']; ?>"
                        data-name="<?php echo htmlspecialchars($ev['event_name'], ENT_QUOTES); ?>"
                        data-cat="<?php echo htmlspecialchars($ev['category']??'Other', ENT_QUOTES); ?>"
                        data-desc="<?php echo htmlspecialchars($ev['description']??'', ENT_QUOTES); ?>"
                        data-date="<?php echo $ev['event_date']; ?>"
                        data-city="<?php echo htmlspecialchars($ev['city']??'', ENT_QUOTES); ?>"
                        data-location="<?php echo htmlspecialchars($ev['location']??'', ENT_QUOTES); ?>"
                        data-price="<?php echo floatval($ev['ticket_price']); ?>"
                        data-seats="<?php echo (int)($ev['total_seats']??500); ?>"
                    >✏️ Edit</button>
                    <?php if($s!=='approved'): ?><a href="?tab=events&delete_event=<?php echo $ev['event_id']; ?>" class="btn btn-sm" style="background:rgba(248,68,100,.15);color:var(--primary);border:1px solid var(--primary);" onclick="return confirm('Delete this event?')">🗑</a><?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════ TAB: SHOWS ═══════════ -->
<div id="tab-shows" class="tab-pane <?php echo $tab==='shows'?'active':''; ?>">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:28px;">
        <h4 style="margin-bottom:18px;">🎬 Add New Show Schedule</h4>
        <form method="POST" id="showForm">
            <div class="form-row">
                <!-- Movie -->
                <div class="form-group">
                    <label class="form-label">🎥 Movie *</label>
                    <select class="form-control" name="movie_id" required>
                        <option value="">-- Select Movie --</option>
                        <?php foreach($movies_arr as $mv): ?>
                        <option value="<?php echo $mv['movie_id']; ?>"><?php echo htmlspecialchars($mv['title']); ?> (<?php echo htmlspecialchars($mv['language']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- City filter — searchable picker -->
                <div class="form-group" id="cityFilterGroup">
                    <label class="form-label" id="cityFilterLabel">🏙️ City</label>
                    <div class="theatre-picker" id="cityPickerWrapper">
                        <input type="text" class="theatre-search-input" id="citySearchInput"
                               placeholder="🔍 All Cities — type to search..."
                               autocomplete="off"
                               oninput="filterCityOpts(this.value)"
                               onclick="openCityDD()">
                        <input type="hidden" id="cityFilter" value="">
                        <div class="theatre-dropdown" id="cityDropdown"></div>
                    </div>
                    <div class="city-disabled-banner" id="cityFilterDisabledNote">🚫 Disabled — city is set in the <strong>Add Custom Theatre</strong> section below.</div>
                </div>
            </div>

            <!-- Searchable Theatre Picker -->
            <div class="form-group">
                <label class="form-label">🏟️ Theatre *</label>
                <div class="theatre-picker">
                    <input type="text" class="theatre-search-input" id="theatreSearchInput" placeholder="🔍 Search or select a theatre..." autocomplete="off" oninput="filterTheatreOpts(this.value)" onclick="openTheatreDD()">
                    <input type="hidden" name="theatre_id" id="theatreIdInput" value="">
                    <div class="theatre-dropdown" id="theatreDropdown">
                        <!-- Options injected by JS -->
                    </div>
                </div>
                <div id="selectedTheatreInfo" style="margin-top:8px;font-size:.85rem;color:var(--text-muted);display:none;"></div>
            </div>

            <!-- Custom theatre inline form -->
            <div class="custom-theatre-box" id="customTheatreBox">
                <h5 style="color:var(--primary);margin-bottom:14px;">🏗️ Add New Theatre</h5>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Theatre Name *</label><input type="text" class="form-control" name="custom_theatre_name" id="ctName" placeholder='e.g. PVR Cinemas, INOX'></div>
                    <!-- City row: shown only when no city is pre-selected from filter -->
                    <div class="form-group" id="ctCityRow">
                        <label class="form-label" id="ctCityLabel">🏙️ City * <small style="color:var(--text-muted);font-weight:400;">(required)</small></label>
                        <select class="form-control select2-city" name="custom_theatre_city" id="ctCity"><option value="">-- Select City --</option><?php foreach($dashCities as $c): ?><option value="<?php echo $c; ?>"><?php echo $c; ?></option><?php endforeach; ?></select>
                        <div style="margin-top:5px;font-size:.78rem;color:#22c55e;">✅ New city? It will appear in the user panel once your show goes live.</div>
                    </div>
                    <!-- City locked display: shown when city pre-selected from filter -->
                    <div class="form-group" id="ctCityLockedRow" style="display:none;">
                        <label class="form-label">🏙️ City <small style="color:var(--text-muted);font-weight:400;">(from filter above)</small></label>
                        <div id="ctCityLockedDisplay" style="padding:10px 14px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.4);border-radius:8px;color:#f59e0b;font-weight:600;font-size:.9rem;">—</div>
                        <input type="hidden" name="custom_theatre_city" id="ctCityHidden" value="">
                        <div style="margin-top:5px;font-size:.78rem;color:var(--text-muted);">📍 City locked to your selection above. Change the city filter to pick a different city.</div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Full Address</label><input type="text" class="form-control" name="custom_theatre_address" placeholder="Area, locality, city"></div>
                    <div class="form-group"><label class="form-label">🗺️ Google Maps Link</label><input type="url" class="form-control" name="custom_theatre_map" placeholder="https://maps.google.com/..."></div>
                </div>
                <p style="font-size:.82rem;color:#f59e0b;margin-top:4px;">⚠️ After adding, provide a licence number below. Shows go live after admin approves the licence.</p>
            </div>

            <!-- Licence field (shown conditionally) -->
            <div class="licence-box" id="licenceBox">
                <label class="form-label" style="color:#f59e0b;">🔑 Theatre Licence Number *</label>
                <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:10px;">This theatre needs a valid licence. Enter it once — reused automatically for future shows at this theatre.</p>
                <input type="text" class="form-control" name="licence_number" id="licenceInput" placeholder="e.g. LIC-2024-MH-001">
            </div>

            <!-- Licence status display -->
            <div id="licenceStatus" style="margin-top:10px;display:none;padding:10px 14px;border-radius:8px;font-size:.85rem;"></div>

            <!-- Dates / Showtimes -->
            <div style="margin-top:18px;">
                <label class="form-label">📅 Show Dates & Times *</label>
                <div id="datesContainer">
                    <div class="date-block" style="background:var(--bg-dark);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:12px;">
                        <div style="display:flex;gap:10px;align-items:center;margin-bottom:12px;flex-wrap:wrap;">
                            <div class="form-group" style="margin-bottom:0;flex:1;min-width:180px;">
                                <label class="form-label" style="font-size:.8rem;">Date</label>
                                <input type="date" class="form-control" name="show_dates[]" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <button type="button" class="btn btn-dark btn-sm" onclick="addTimeRow(this)" style="margin-top:20px;">+ Time</button>
                            <button type="button" onclick="removeDateBlock(this)" class="remove-row" style="margin-top:20px;">🗑 Date</button>
                        </div>
                        <div class="times-container">
                            <div class="show-row">
                                <div class="form-group"><label class="form-label" style="font-size:.8rem;">Language</label>
                                    <select class="form-control" name="languages[0][]"><?php foreach(['Hindi','English','Tamil','Telugu','Kannada','Malayalam','Bengali','Marathi','Punjabi'] as $l): ?><option><?php echo $l; ?></option><?php endforeach; ?></select>
                                </div>
                                <div class="form-group"><label class="form-label" style="font-size:.8rem;">Show Time</label><input type="time" class="form-control" name="show_times[0][]" required></div>
                                <div class="form-group"><label class="form-label" style="font-size:.8rem;">Price (₹)</label><input type="number" class="form-control" name="prices[0][]" min="1" value="200" required></div>
                                <button type="button" class="remove-row" onclick="removeShowRow(this)" style="margin-top:20px;">✕</button>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="add-date-btn" onclick="addDateBlock()">+ Add Another Date</button>
            </div>

            <div style="margin-top:18px;padding:12px;background:rgba(248,68,100,.06);border:1px solid rgba(248,68,100,.15);border-radius:8px;font-size:.84rem;color:var(--text-muted);">
                ℹ️ If your theatre licence is approved, new shows go live instantly! Otherwise, they require admin approval.
            </div>
            <button type="submit" name="add_show" class="btn btn-primary" style="margin-top:14px;">💾 Submit Shows</button>
        </form>
    </div>

    <!-- My Shows table -->
    <div class="table-wrapper">
        <div class="table-header">
            <h4 class="table-title">📋 My Shows</h4>
            <div style="display:flex;gap:8px;">
                <input type="text" id="filterMov" placeholder="Movie..." class="form-control" style="width:150px;padding:6px 10px;" oninput="filterShows()">
                <input type="text" id="filterTh"  placeholder="Theatre..." class="form-control" style="width:150px;padding:6px 10px;" oninput="filterShows()">
            </div>
        </div>
        <?php if(!$myShows||$myShows->num_rows===0): ?>
        <p style="text-align:center;padding:30px;color:var(--text-muted);">No shows yet.</p>
        <?php else: ?>
        <table id="showsTable">
            <thead><tr><th>Movie</th><th>Theatre</th><th>Date</th><th>Lang</th><th>Time</th><th>Price</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php while($s=$myShows->fetch_assoc()):
                $ss=$s['status']??'active';
                $ssb=match($ss){'active'=>'<span class="badge badge-success" style="font-size:.72rem;">✅ Live</span>','pending'=>'<span style="color:#f59e0b;font-size:.78rem;">⏳ Pending</span>',default=>'<span style="color:#ef4444;font-size:.78rem;">❌ '.$ss.'</span>'};
            ?>
            <tr class="show-tr" data-movie="<?php echo strtolower(htmlspecialchars($s['movie_title'])); ?>" data-theatre="<?php echo strtolower(htmlspecialchars($s['theatre_name'])); ?>">
                <td style="font-weight:600;"><?php echo htmlspecialchars($s['movie_title']); ?></td>
                <td style="font-size:.85rem;color:var(--text-muted);"><?php echo htmlspecialchars($s['theatre_name']); ?>, <?php echo htmlspecialchars($s['city']); ?></td>
                <td><?php echo date('d M Y',strtotime($s['show_date'])); ?></td>
                <td><span style="background:rgba(248,68,100,.12);color:var(--primary);padding:2px 8px;border-radius:50px;font-size:.75rem;font-weight:700;"><?php echo htmlspecialchars($s['language']??'Hindi'); ?></span></td>
                <td><?php echo date('h:i A',strtotime($s['show_time'])); ?></td>
                <td style="font-weight:600;color:var(--primary);">₹<?php echo number_format($s['price'],0); ?></td>
                <td><?php echo $ssb; ?></td>
                <td><a href="?tab=shows&delete_show=<?php echo $s['show_id']; ?>" class="btn btn-sm" style="background:rgba(248,68,100,.15);color:var(--primary);border:1px solid var(--primary);" onclick="return confirm('Delete?')">🗑</a></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════ TAB: VENUES ═══════════ -->
<div id="tab-venues" class="tab-pane <?php echo $tab==='venues'?'active':''; ?>">
    <div style="background:rgba(248,68,100,.06);border:1px solid rgba(248,68,100,.2);border-radius:10px;padding:14px 18px;margin-bottom:24px;font-size:.88rem;color:var(--primary);">
        ℹ️ To add a new theatre/venue, go to <strong>Manage Shows</strong> tab → choose <strong>"Add New Custom Theatre"</strong> from the theatre picker.
    </div>

    <!-- Licences -->
    <div class="table-wrapper" style="margin-bottom:24px;">
        <div class="table-header"><h4 class="table-title">🔑 My Theatre Licences</h4></div>
        <?php if(!$myLicences||$myLicences->num_rows===0): ?>
        <p style="text-align:center;padding:20px;color:var(--text-muted);">No licences submitted yet.</p>
        <?php else: $myLicences->data_seek(0); ?>
        <table>
            <thead><tr><th>Theatre</th><th>City</th><th>Licence #</th><th>Submitted</th><th>Status</th></tr></thead>
            <tbody>
            <?php while($lic=$myLicences->fetch_assoc()):
                $ls=$lic['status'];
                $lb=match($ls){'approved'=>'<span class="lic-approved">✅ Approved</span>','rejected'=>'<span class="lic-rejected">❌ Rejected</span>',default=>'<span class="lic-pending">⏳ Pending</span>'};
            ?>
            <tr>
                <td style="font-weight:600;"><?php echo htmlspecialchars($lic['theatre_name']); ?></td>
                <td><?php echo htmlspecialchars($lic['city']); ?></td>
                <td style="font-family:monospace;color:var(--primary);"><?php echo htmlspecialchars($lic['licence_number']); ?></td>
                <td style="font-size:.85rem;"><?php echo date('d M Y',strtotime($lic['submitted_at'])); ?></td>
                <td><?php echo $lb; ?><?php if($ls==='rejected'&&$lic['rejection_note']): ?><br><small style="color:#ef4444;"><?php echo htmlspecialchars($lic['rejection_note']); ?></small><?php endif; ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- My Venues -->
    <div class="table-wrapper">
        <div class="table-header"><h4 class="table-title">🏟️ Venues I Added</h4></div>
        <?php if(!$myVenues||$myVenues->num_rows===0): ?>
        <p style="text-align:center;padding:20px;color:var(--text-muted);">No venues added yet.</p>
        <?php else: $myVenues->data_seek(0); ?>
        <table>
            <thead><tr><th>Name</th><th>City</th><th>Address</th><th>Map</th><th>Status</th></tr></thead>
            <tbody>
            <?php while($v=$myVenues->fetch_assoc()): ?>
            <tr>
                <td style="font-weight:600;"><?php echo htmlspecialchars($v['theatre_name']); ?></td>
                <td><?php echo htmlspecialchars($v['city']); ?></td>
                <td style="font-size:.85rem;color:var(--text-muted);"><?php echo htmlspecialchars($v['location']); ?></td>
                <td><?php if($v['map_link']??''): ?><a href="<?php echo htmlspecialchars($v['map_link']); ?>" target="_blank" class="btn btn-dark btn-sm">🗺️</a><?php else: ?>—<?php endif; ?></td>
                <td><?php echo ($v['is_verified']??0) ? '<span class="lic-approved">✅ Active</span>' : '<span class="lic-pending">⏳ Pending Licence</span>'; ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</div><!-- /main container -->

<!-- Media Update Modal (old, kept for compatibility) -->
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

<!-- ✏️ Full Edit Event Modal -->
<div id="editEventModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);z-index:9999;align-items:center;justify-content:center;overflow-y:auto;">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:28px;width:94%;max-width:600px;position:relative;margin:30px auto;">
        <button type="button" onclick="closeEditEventModal()" style="position:absolute;top:16px;right:16px;background:none;border:none;color:var(--text);font-size:1.3rem;cursor:pointer;">✕</button>
        <h4 style="margin-bottom:6px;">✏️ Edit Event</h4>
        <p id="editEventModalName" style="color:var(--primary);font-weight:600;margin-bottom:18px;font-size:.95rem;"></p>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="edit_event_id" id="editEventId">
            <div class="form-row">
                <div class="form-group"><label class="form-label">Event Name *</label><input type="text" class="form-control" name="edit_event_name" id="editEvName" required></div>
                <div class="form-group"><label class="form-label">Category *</label>
                    <select class="form-control" name="edit_category" id="editEvCat" required>
                        <option value="">Select Category</option>
                        <option value="Comedy">Comedy</option>
                        <option value="Sports">Sports</option>
                        <option value="Music">Music</option>
                        <option value="Festivals & Fairs">Festivals &amp; Fairs</option>
                        <option value="College Fests">College Fests</option>
                        <option value="Workshops">Workshops</option>
                        <option value="Parties">Parties</option>
                        <option value="Gaming & Esports">Gaming &amp; Esports</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Event Date *</label><input type="date" class="form-control" name="edit_event_date" id="editEvDate" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">City *</label>
                    <select class="form-control" name="edit_event_city" id="editEvCity" required>
                        <option value="">Select City</option>
                        <?php foreach($dashCities as $c): ?><option value="<?php echo $c; ?>"><?php echo $c; ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Ticket Price (₹) *</label><input type="number" class="form-control" name="edit_ticket_price" id="editEvPrice" min="1" step="0.01" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">🏟️ Venue Name</label><input type="text" class="form-control" name="edit_venue_name" id="editEvVenue" placeholder="e.g. MMRDA Grounds"></div>
                <div class="form-group"><label class="form-label">Location / Area</label><input type="text" class="form-control" name="edit_location" id="editEvLoc" placeholder="e.g. BKC, Mumbai"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Total Seats</label><input type="number" class="form-control" name="edit_total_seats" id="editEvSeats" value="500"></div>
            </div>
            <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" name="edit_description" id="editEvDesc" rows="3"></textarea></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">🖼️ New Poster (leave blank to keep)</label><input type="file" class="form-control" name="edit_poster" accept="image/*" style="padding:6px;"></div>
                <div class="form-group"><label class="form-label">🏞️ New Banner (leave blank to keep)</label><input type="file" class="form-control" name="edit_banner" accept="image/*" style="padding:6px;"></div>
            </div>
            <div style="display:flex;gap:10px;margin-top:8px;">
                <button type="submit" name="edit_event" class="btn btn-primary" style="flex:1;">💾 Save Changes</button>
                <button type="button" onclick="closeEditEventModal()" class="btn btn-dark" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Data from PHP ──────────────────────────────────────────────────────────
var allTheatres = <?php echo json_encode($theatres_arr); ?>;
var licenceMap  = <?php echo json_encode($licenceMap); ?>; // {theatre_id: status}
var opCity      = '<?php echo htmlspecialchars($_SESSION['operator_city'] ?? ''); ?>';
var allCities   = <?php echo json_encode($dashCities); ?>; // all 50 cities

function openMediaModal(id, name) {
    document.getElementById('mediaEventId').value = id;
    document.getElementById('mediaModalEventName').innerText = name;
    document.getElementById('mediaModal').style.display = 'flex';
}

// ── Edit Event Modal ────────────────────────────────────────────────────────
// Use data-* attributes to safely read values (avoids JS injection from special chars)
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.edit-event-btn');
    if (!btn) return;
    var d = btn.dataset;
    openEditEventModal(
        d.id, d.name, d.cat, d.desc, d.date, d.city, d.location,
        parseFloat(d.price), parseInt(d.seats)
    );
});

function openEditEventModal(id, name, cat, desc, date, city, location, price, seats) {
    document.getElementById('editEventId').value    = id;
    document.getElementById('editEvName').value     = name;
    document.getElementById('editEvCat').value      = cat;
    document.getElementById('editEvDesc').value     = desc;
    document.getElementById('editEvDate').value     = date;
    document.getElementById('editEvPrice').value    = price;
    document.getElementById('editEvSeats').value    = seats;
    document.getElementById('editEventModalName').textContent = name;

    // Populate venue & location fields by splitting on ', '
    // location stored as "VenueName, Area" — split at first ', '
    var parts = location.split(', ');
    document.getElementById('editEvVenue').value = parts[0] || '';
    document.getElementById('editEvLoc').value   = parts.slice(1).join(', ') || '';

    // Set the city dropdown
    var citySel = document.getElementById('editEvCity');
    for (var i = 0; i < citySel.options.length; i++) {
        if (citySel.options[i].value === city) { citySel.selectedIndex = i; break; }
    }
    // Refresh Select2 if present
    if (window.jQuery && $('#editEvCity').data('select2')) {
        $('#editEvCity').trigger('change');
    }

    var modal = document.getElementById('editEventModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeEditEventModal() {
    document.getElementById('editEventModal').style.display = 'none';
    document.body.style.overflow = '';
}

// Close edit modal on backdrop click
document.getElementById('editEventModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditEventModal();
});

// Filtered set
var filteredTheatres = allTheatres.slice();

// ── Tab switch ──────────────────────────────────────────────────────────────
function switchTab(tab, btn) {
    document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.dash-tab').forEach(b=>b.classList.remove('active'));
    document.getElementById('tab-'+tab).classList.add('active');
    btn.classList.add('active');
    history.replaceState(null,'','?tab='+tab);
}

// ── City filter ─────────────────────────────────────────────────────────────
function filterTheatres() {
    var city = document.getElementById('cityFilter').value;
    filteredTheatres = allTheatres.filter(t => !city || t.city === city);
    // Reset any open custom theatre form or selection
    document.getElementById('theatreSearchInput').value = '';
    document.getElementById('theatreIdInput').value = '';
    document.getElementById('selectedTheatreInfo').style.display = 'none';
    document.getElementById('customTheatreBox').classList.remove('show');
    hideAllBoxes();
    renderDropdown('');
}

// ── Theatre dropdown ─────────────────────────────────────────────────────────
function openTheatreDD() {
    renderDropdown(document.getElementById('theatreSearchInput').value);
    document.getElementById('theatreDropdown').classList.add('open');
}

function filterTheatreOpts(q) {
    document.getElementById('theatreDropdown').classList.add('open');
    renderDropdown(q);
    // Reset selection
    document.getElementById('theatreIdInput').value = '';
    document.getElementById('selectedTheatreInfo').style.display='none';
    hideAllBoxes();
}

function renderDropdown(q) {
    var dd = document.getElementById('theatreDropdown');
    var selectedCity = document.getElementById('cityFilter').value;
    q = q.toLowerCase();
    var html = '';
    // ── Add New Theatre always at TOP ──
    var addLabel = selectedCity ? '➕ Add New Theatre in ' + escH(selectedCity) : '➕ Add New Theatre (pick city below)';
    html += '<div class="theatre-opt custom-opt" onclick="selectCustomTheatre()">' + addLabel + '</div>';
    var matches = filteredTheatres.filter(function(t) {
        return !q || t.theatre_name.toLowerCase().includes(q) || t.city.toLowerCase().includes(q);
    });
    if (matches.length === 0 && selectedCity) {
        // No theatres in selected city
        html += '<div class="theatre-opt" style="color:var(--text-muted);cursor:default;justify-content:center;flex-direction:column;gap:4px;text-align:center;padding:16px 14px;">'
              + '<span style="font-size:1.3rem;">📭</span>'
              + '<span style="font-size:.85rem;">No theatres in <strong style="color:var(--text);">' + escH(selectedCity) + '</strong> yet.</span>'
              + '</div>';
    } else {
        matches.forEach(function(t) {
            var ls = licenceMap[t.theatre_id];
            var tag = '';
            if      (ls === 'approved') tag = '<span class="lic-tag lic-approved">✅ Licence OK</span>';
            else if (ls === 'pending')  tag = '<span class="lic-tag lic-pending">⏳ Pending</span>';
            else if (ls === 'rejected') tag = '<span class="lic-tag lic-rejected">❌ Rejected</span>';
            else                        tag = '<span class="lic-tag" style="color:var(--text-muted);font-size:.72rem;">⚠️ Needs Licence</span>';
            html += '<div class="theatre-opt" onclick="selectTheatre('+t.theatre_id+',\''+escQ(t.theatre_name)+'\',\''+escQ(t.city)+'\',\''+ls+'\')">'
                  + '<span>' + escH(t.theatre_name) + ' <span style="color:var(--text-muted);font-size:.8rem;">— ' + escH(t.city) + '</span></span>' + tag + '</div>';
        });
    }
    dd.innerHTML = html;
}

function selectTheatre(id, name, city, licStatus) {
    document.getElementById('theatreSearchInput').value = name + ' — ' + city;
    document.getElementById('theatreIdInput').value = id;
    document.getElementById('theatreDropdown').classList.remove('open');
    document.getElementById('customTheatreBox').classList.remove('show');

    var infoDiv = document.getElementById('selectedTheatreInfo');
    var licBox  = document.getElementById('licenceBox');
    var licStat = document.getElementById('licenceStatus');

    hideAllBoxes();

    if (licStatus === 'approved') {
        licStat.style.display='block';
        licStat.style.background='rgba(34,197,94,.1)';
        licStat.style.border='1px solid rgba(34,197,94,.3)';
        licStat.innerHTML='✅ <strong style="color:#22c55e;">Licence already approved</strong> — you can freely add shows to this theatre!';
    } else if (licStatus === 'pending') {
        licStat.style.display='block';
        licStat.style.background='rgba(245,158,11,.1)';
        licStat.style.border='1px solid rgba(245,158,11,.3)';
        licStat.innerHTML='⏳ <strong style="color:#f59e0b;">Licence pending approval.</strong> Fill dates/times below — shows will go live once admin approves.';
    } else if (licStatus === 'rejected') {
        licStat.style.display='block';
        licStat.style.background='rgba(239,68,68,.1)';
        licStat.style.border='1px solid rgba(239,68,68,.3)';
        licStat.innerHTML='❌ <strong style="color:#ef4444;">Licence rejected.</strong> Please enter a new licence number below.';
        licBox.classList.add('show');
        document.getElementById('licenceInput').required = true;
    } else {
        // Needs licence
        licBox.classList.add('show');
        licStat.style.display='block';
        licStat.style.background='rgba(245,158,11,.08)';
        licStat.style.border='1px solid rgba(245,158,11,.2)';
        licStat.innerHTML='⚠️ This theatre requires a <strong>licence number</strong> (one-time). Enter it below and submit — you won\'t need to enter it again for this theatre.';
        document.getElementById('licenceInput').required = true;
    }

    infoDiv.style.display='block';
    infoDiv.innerHTML = '🏟️ <strong>' + escH(name) + '</strong> — ' + escH(city);
}

function selectCustomTheatre() {
    var selectedCity = document.getElementById('cityFilter').value;
    var label = selectedCity ? '➕ Adding theatre in ' + selectedCity : '➕ Add New Theatre';
    document.getElementById('theatreSearchInput').value = label;
    document.getElementById('theatreIdInput').value = '0';  // 0 = custom
    document.getElementById('theatreDropdown').classList.remove('open');
    hideAllBoxes();
    document.getElementById('customTheatreBox').classList.add('show');
    document.getElementById('licenceBox').classList.add('show');
    document.getElementById('licenceInput').required = true;
    document.getElementById('licenceStatus').style.display='block';
    document.getElementById('licenceStatus').style.background='rgba(248,68,100,.08)';
    document.getElementById('licenceStatus').style.border='1px solid rgba(248,68,100,.2)';
    document.getElementById('licenceStatus').innerHTML='ℹ️ New theatre — fill in the name, address and licence number below. Admin will review and approve.';
    document.getElementById('selectedTheatreInfo').style.display='none';

    if (selectedCity) {
        // City already known from filter — lock it, hide picker, strip its name so only hidden submits
        document.getElementById('ctCityRow').style.display = 'none';
        document.getElementById('ctCity').removeAttribute('required');
        document.getElementById('ctCity').removeAttribute('name'); // prevent duplicate POST field
        document.getElementById('ctCity').value = '';
        document.getElementById('ctCityLockedRow').style.display = '';
        document.getElementById('ctCityLockedDisplay').textContent = '📍 ' + selectedCity;
        document.getElementById('ctCityHidden').value = selectedCity;
        document.getElementById('cityFilterDisabledNote').classList.remove('show');
        document.getElementById('cityFilterGroup').classList.remove('city-disabled');
    } else {
        // No city pre-selected — show the city picker in the form, restore its name
        document.getElementById('ctCityRow').style.display = '';
        document.getElementById('ctCity').setAttribute('name', 'custom_theatre_city');
        document.getElementById('ctCity').setAttribute('required', 'required');
        document.getElementById('ctCityLockedRow').style.display = 'none';
        document.getElementById('ctCityHidden').value = '';
        // Disable top filter since city comes from custom form
        var si = document.getElementById('citySearchInput');
        si.disabled = true;
        si.style.opacity = '0.4';
        document.getElementById('cityFilterDisabledNote').classList.add('show');
        document.getElementById('cityFilterGroup').classList.add('city-disabled');
    }
}

function hideAllBoxes() {
    document.getElementById('licenceBox').classList.remove('show');
    document.getElementById('licenceStatus').style.display='none';
    document.getElementById('licenceInput').required = false;
    // Re-enable top city filter whenever boxes reset
    enableCityFilter();
}

function enableCityFilter() {
    var si = document.getElementById('citySearchInput');
    si.disabled = false;
    si.style.opacity = '';
    document.getElementById('cityFilterDisabledNote').classList.remove('show');
    document.getElementById('cityFilterGroup').classList.remove('city-disabled');
    // Reset custom theatre city state — restore select name & visibility
    document.getElementById('ctCity').setAttribute('name', 'custom_theatre_city');
    document.getElementById('ctCityRow').style.display = '';
    document.getElementById('ctCityLockedRow').style.display = 'none';
    document.getElementById('ctCityHidden').value = '';
}

// Close dropdowns on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.theatre-picker')) {
        document.getElementById('theatreDropdown').classList.remove('open');
        document.getElementById('cityDropdown').classList.remove('open');
    }
});

// ── City picker functions ────────────────────────────────────────────────────
function openCityDD() {
    renderCityDropdown(document.getElementById('citySearchInput').value);
    document.getElementById('cityDropdown').classList.add('open');
}

function filterCityOpts(q) {
    document.getElementById('cityDropdown').classList.add('open');
    renderCityDropdown(q);
}

function renderCityDropdown(q) {
    var dd = document.getElementById('cityDropdown');
    q = (q || '').toLowerCase().trim();
    var filtered = allCities.filter(function(c) {
        return !q || c.toLowerCase().includes(q);
    });
    var html = '';
    // "All Cities" option always first
    html += '<div class="theatre-opt" style="color:var(--primary);font-weight:600;" onclick="selectCity(\'\', \'All Cities\')">🌐 All Cities</div>';
    if (filtered.length === 0) {
        html += '<div class="theatre-opt" style="color:var(--text-muted);cursor:default;justify-content:center;">No cities match "' + escH(q) + '"</div>';
    } else {
        filtered.forEach(function(c) {
            html += '<div class="theatre-opt" onclick="selectCity(\'' + escQ(c) + '\', \'' + escQ(c) + '\')">' + escH(c) + '</div>';
        });
    }
    dd.innerHTML = html;
}

function selectCity(value, label) {
    document.getElementById('cityFilter').value = value;
    document.getElementById('citySearchInput').value = value ? label : '';
    document.getElementById('citySearchInput').placeholder = value ? '' : '🔍 All Cities — type to search...';
    document.getElementById('cityDropdown').classList.remove('open');
    filterTheatres();
}

function escQ(s) { return s.replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
function escH(s) { var d=document.createElement('div');d.textContent=s;return d.innerHTML; }

// ── Dynamic date/time rows ──────────────────────────────────────────────────
var dIdx = 0;
function addDateBlock() {
    dIdx++;
    var i = dIdx;
    var c = document.getElementById('datesContainer');
    var b = document.createElement('div');
    b.className='date-block';
    b.style.cssText='background:var(--bg-dark);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:12px;';
    b.innerHTML=`<div style="display:flex;gap:10px;align-items:center;margin-bottom:12px;flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;flex:1;min-width:180px;"><label class="form-label" style="font-size:.8rem;">Date</label>
        <input type="date" class="form-control" name="show_dates[]" min="${new Date().toISOString().split('T')[0]}" required></div>
        <button type="button" class="btn btn-dark btn-sm" onclick="addTimeRow(this)" style="margin-top:20px;">+ Time</button>
        <button type="button" onclick="removeDateBlock(this)" class="remove-row" style="margin-top:20px;">🗑 Date</button>
    </div>
    <div class="times-container"><div class="show-row">
        <div class="form-group"><label class="form-label" style="font-size:.8rem;">Language</label>
        <select class="form-control" name="languages[${i}][]"><option>Hindi</option><option>English</option><option>Tamil</option><option>Telugu</option><option>Kannada</option><option>Malayalam</option><option>Bengali</option><option>Marathi</option><option>Punjabi</option></select></div>
        <div class="form-group"><label class="form-label" style="font-size:.8rem;">Show Time</label><input type="time" class="form-control" name="show_times[${i}][]" required></div>
        <div class="form-group"><label class="form-label" style="font-size:.8rem;">Price (₹)</label><input type="number" class="form-control" name="prices[${i}][]" min="1" value="200" required></div>
        <button type="button" class="remove-row" onclick="removeShowRow(this)" style="margin-top:20px;">✕</button>
    </div></div>`;
    c.appendChild(b);
}

function addTimeRow(btn) {
    var block=btn.closest('.date-block');
    var tc=block.querySelector('.times-container');
    var idx=Array.from(document.querySelectorAll('.date-block')).indexOf(block);
    var row=document.createElement('div'); row.className='show-row';
    row.innerHTML=`<div class="form-group"><label class="form-label" style="font-size:.8rem;">Language</label>
        <select class="form-control" name="languages[${idx}][]"><option>Hindi</option><option>English</option><option>Tamil</option><option>Telugu</option><option>Kannada</option><option>Malayalam</option><option>Bengali</option><option>Marathi</option><option>Punjabi</option></select></div>
        <div class="form-group"><label class="form-label" style="font-size:.8rem;">Show Time</label><input type="time" class="form-control" name="show_times[${idx}][]" required></div>
        <div class="form-group"><label class="form-label" style="font-size:.8rem;">Price (₹)</label><input type="number" class="form-control" name="prices[${idx}][]" min="1" value="200" required></div>
        <button type="button" class="remove-row" onclick="removeShowRow(this)" style="margin-top:20px;">✕</button>`;
    tc.appendChild(row);
}
function removeShowRow(btn){var r=btn.closest('.show-row');var tc=r.closest('.times-container');if(tc.querySelectorAll('.show-row').length>1)r.remove();else alert('At least one time required.');}
function removeDateBlock(btn){var bs=document.querySelectorAll('.date-block');if(bs.length>1)btn.closest('.date-block').remove();else alert('At least one date required.');}

// ── Shows table filter ──────────────────────────────────────────────────────
function filterShows(){
    var mv=document.getElementById('filterMov').value.toLowerCase();
    var th=document.getElementById('filterTh').value.toLowerCase();
    document.querySelectorAll('.show-tr').forEach(function(r){
        r.style.display=(!mv||r.dataset.movie.includes(mv))&&(!th||r.dataset.theatre.includes(th))?'':'none';
    });
}

// ── Init ────────────────────────────────────────────────────────────────────
(function(){
    if (opCity) {
        selectCity(opCity, opCity); // pre-select operator's city
    }
    renderDropdown('');
    renderCityDropdown('');
})();
$(document).ready(function() {
    $('.select2-city').select2({
        placeholder: '🔍 Search city...',
        width: '100%'
    });
});
</script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<?php include 'includes/footer.php'; ?>
