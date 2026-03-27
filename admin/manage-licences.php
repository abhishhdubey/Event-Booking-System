<?php
require_once 'admin-helper.php';

$msg = '';

// ── Approve licence (POST for reliability) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_lic'])) {
    $lid = (int)$_POST['licence_id'];
    $conn->query("UPDATE theatre_licences SET status='approved', reviewed_at=NOW() WHERE licence_id=$lid");
    $lic = $conn->query("SELECT tl.*, t.theatre_name, o.name as op_name FROM theatre_licences tl JOIN theatres t ON tl.theatre_id=t.theatre_id JOIN operators o ON tl.operator_id=o.operator_id WHERE tl.licence_id=$lid")->fetch_assoc();
    if ($lic) {
        $tid = (int)$lic['theatre_id'];
        $oid = (int)$lic['operator_id'];
        // Mark theatre as verified
        $conn->query("UPDATE theatres SET is_verified=1 WHERE theatre_id=$tid");
        $conn->query("UPDATE admin_notifications SET is_read=1 WHERE reference_id=$lid AND type='licence'");
        // AUTO-LIVE: activate all pending movie shows for this theatre+organizer
        $activated = $conn->query("UPDATE shows SET status='active' WHERE theatre_id=$tid AND operator_id=$oid AND status='pending'");
        $activatedCount = $conn->affected_rows;
        // Notify about auto-activation
        $tname = $conn->real_escape_string($lic['theatre_name']);
        $oname = $conn->real_escape_string($lic['op_name']);
        $conn->query("INSERT INTO admin_notifications (type,title,message,reference_id) VALUES ('show','✅ Auto-Live: $activatedCount Shows','Licence for $tname by $oname approved. $activatedCount pending shows automatically went live.',$lid)");
    }
    $msg = '<div class="alert alert-success">✅ Theatre licence approved! All pending shows for this theatre are now <strong>live</strong> automatically.</div>';
}

// ── Reject licence (POST) ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_licence'])) {
    $lid    = (int)$_POST['licence_id'];
    $reason = $conn->real_escape_string(trim($_POST['rejection_reason'] ?? 'Licence rejected by admin.'));
    $conn->query("UPDATE theatre_licences SET status='rejected', rejection_note='$reason', reviewed_at=NOW() WHERE licence_id=$lid");
    $conn->query("UPDATE admin_notifications SET is_read=1 WHERE reference_id=$lid AND type='licence'");
    $msg = '<div class="alert alert-success">❌ Licence rejected with reason noted.</div>';
}

// Stats
$pending_count  = $conn->query("SELECT COUNT(*) c FROM theatre_licences WHERE status='pending'")->fetch_assoc()['c'];
$approved_count = $conn->query("SELECT COUNT(*) c FROM theatre_licences WHERE status='approved'")->fetch_assoc()['c'];
$rejected_count = $conn->query("SELECT COUNT(*) c FROM theatre_licences WHERE status='rejected'")->fetch_assoc()['c'];
$licences = $conn->query("SELECT tl.*, t.theatre_name, t.city, t.location, o.name as op_name, o.organization FROM theatre_licences tl JOIN theatres t ON tl.theatre_id=t.theatre_id JOIN operators o ON tl.operator_id=o.operator_id ORDER BY tl.submitted_at DESC");

ob_start();
echo $msg;
?>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
    <div class="stat-card"><div class="stat-icon gold">⏳</div><div class="stat-info"><div class="stat-value"><?php echo $pending_count; ?></div><div class="stat-label">Pending</div></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div class="stat-info"><div class="stat-value"><?php echo $approved_count; ?></div><div class="stat-label">Approved</div></div></div>
    <div class="stat-card"><div class="stat-icon red">❌</div><div class="stat-info"><div class="stat-value"><?php echo $rejected_count; ?></div><div class="stat-label">Rejected</div></div></div>
</div>

<!-- Licences Table -->
<div class="table-wrapper">
    <div class="table-header"><h4 class="table-title">🎫 Theatre Licence Requests</h4></div>
    <?php if(!$licences || $licences->num_rows === 0): ?>
    <p style="text-align:center;padding:30px;color:var(--text-muted);">No licence requests yet.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th><th>Theatre</th><th>City</th><th>Organizer</th><th>Licence #</th><th>Submitted</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while($lic = $licences->fetch_assoc()):
            $s  = $lic['status'];
            $sb = match($s) {
                'approved' => '<span style="background:rgba(34,197,94,.12);color:#22c55e;padding:3px 10px;border-radius:50px;font-size:.75rem;font-weight:700;">✅ Approved</span>',
                'rejected' => '<span style="background:rgba(239,68,68,.12);color:#ef4444;padding:3px 10px;border-radius:50px;font-size:.75rem;font-weight:700;">❌ Rejected</span>',
                default    => '<span style="background:rgba(245,158,11,.12);color:#f59e0b;padding:3px 10px;border-radius:50px;font-size:.75rem;font-weight:700;">⏳ Pending</span>'
            };
        ?>
        <tr>
            <td>#<?php echo $lic['licence_id']; ?></td>
            <td>
                <div style="font-weight:600;"><?php echo htmlspecialchars($lic['theatre_name']); ?></div>
                <div style="font-size:.8rem;color:var(--text-muted);">📍 <?php echo htmlspecialchars($lic['location']); ?></div>
            </td>
            <td><?php echo htmlspecialchars($lic['city']); ?></td>
            <td>
                <div style="font-weight:500;"><?php echo htmlspecialchars($lic['op_name']); ?></div>
                <div style="font-size:.8rem;color:var(--text-muted);"><?php echo htmlspecialchars($lic['organization']); ?></div>
            </td>
            <td style="font-family:monospace;color:var(--primary);font-weight:600;"><?php echo htmlspecialchars($lic['licence_number']); ?></td>
            <td style="font-size:.85rem;"><?php echo date('d M Y, h:i A', strtotime($lic['submitted_at'])); ?></td>
            <td>
                <?php echo $sb; ?>
                <?php if($s === 'rejected' && $lic['rejection_note']): ?>
                <div style="font-size:.78rem;color:#ef4444;margin-top:4px;"><?php echo htmlspecialchars($lic['rejection_note']); ?></div>
                <?php endif; ?>
            </td>
            <td class="action-btns">
                <?php if($s === 'pending'): ?>
                <!-- Approve via POST form -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="licence_id" value="<?php echo $lic['licence_id']; ?>">
                    <button type="submit" name="approve_lic" class="btn btn-sm"
                        style="background:rgba(34,197,94,.15);color:#22c55e;border:1px solid #22c55e;cursor:pointer;">
                        ✅ Approve
                    </button>
                </form>
                <!-- Reject button opens modal -->
                <button class="btn btn-sm"
                    style="background:rgba(239,68,68,.15);color:#ef4444;border:1px solid #ef4444;cursor:pointer;"
                    onclick="openRejectModal(<?php echo $lic['licence_id']; ?>)">
                    ❌ Reject
                </button>
                <?php elseif($s === 'approved'): ?>
                <span style="color:#22c55e;font-size:.85rem;font-weight:600;">✅ Active</span>
                <?php else: ?>
                <span style="color:#ef4444;font-size:.85rem;font-weight:600;">🔒 Closed</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Reject Modal -->
<div id="rejectModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:28px;max-width:440px;width:90%;">
        <h4 style="margin-bottom:16px;">❌ Reject Licence</h4>
        <form method="POST">
            <input type="hidden" name="licence_id" id="rejectLicId">
            <div class="form-group">
                <label class="form-label">Rejection Reason *</label>
                <textarea class="form-control" name="rejection_reason" rows="3" placeholder="e.g. Invalid licence number..." required></textarea>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" name="reject_licence" class="btn btn-primary">Confirm Reject</button>
                <button type="button" class="btn btn-dark" onclick="closeRejectModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(id) {
    document.getElementById('rejectLicId').value = id;
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
</script>

<?php
$content = ob_get_clean();
adminLayout('Theatre Licences', $content, 'admin/manage-licences.php');
?>
