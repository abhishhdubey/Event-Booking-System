<?php
require_once 'admin-helper.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code   = strtoupper($conn->real_escape_string(trim($_POST['coupon_code'])));
    $disc   = (int)$_POST['discount_percent'];
    $expiry = $conn->real_escape_string($_POST['expiry_date']);
    $conn->query("INSERT INTO coupons (coupon_code, discount_percent, expiry_date) VALUES ('$code', $disc, '$expiry')");
    $msg = '<div class="alert alert-success">✅ Coupon added!</div>';
}

if (isset($_GET['delete'])) {
    $cid = (int)$_GET['delete'];
    $conn->query("DELETE FROM coupons WHERE coupon_id = $cid");
    $msg = '<div class="alert alert-success">✅ Coupon deleted.</div>';
}

$coupons = $conn->query("SELECT * FROM coupons ORDER BY expiry_date DESC");

$content = $msg . '
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:24px;">
<h4 style="margin-bottom:16px;">➕ Add Coupon</h4>
<form method="POST">
<div class="form-row">
<div class="form-group"><label class="form-label">Coupon Code</label><input type="text" class="form-control" name="coupon_code" placeholder="e.g. SAVE20" required></div>
<div class="form-group"><label class="form-label">Discount (%)</label><input type="number" class="form-control" name="discount_percent" min="1" max="100" required></div>
</div>
<div class="form-group"><label class="form-label">Expiry Date</label><input type="date" class="form-control" name="expiry_date" required></div>
<button type="submit" name="add_coupon" class="btn btn-primary">Add Coupon</button>
</form>
</div>
<div class="table-wrapper">
<div class="table-header"><h4 class="table-title">🏷️ All Coupons</h4></div>
<table>
<thead><tr><th>Code</th><th>Discount</th><th>Expiry</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>';

while ($c = $coupons->fetch_assoc()) {
    $isExpired = $c['expiry_date'] < date('Y-m-d');
    $badge = $isExpired ? '<span class="badge badge-danger">Expired</span>' : '<span class="badge badge-success">Active</span>';
    $content .= '<tr>
    <td style="font-weight:700;color:var(--primary);">' . htmlspecialchars($c['coupon_code']) . '</td>
    <td><span class="badge badge-info">' . $c['discount_percent'] . '% OFF</span></td>
    <td>' . date('d M Y', strtotime($c['expiry_date'])) . '</td>
    <td>' . $badge . '</td>
    <td><a href="manage-coupons.php?delete=' . $c['coupon_id'] . '" class="btn btn-sm" style="background:rgba(248,68,100,0.15);color:var(--primary);border:1px solid var(--primary);" onclick="return confirm(\'Delete?\')">Delete</a></td>
    </tr>';
}
$content .= '</tbody></table></div>';
adminLayout('Manage Coupons', $content, 'admin/manage-coupons.php');
?>
