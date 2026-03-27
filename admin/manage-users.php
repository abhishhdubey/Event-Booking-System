<?php
require_once 'admin-helper.php';

$msg = '';
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE user_id = $uid");
    $msg = '<div class="alert alert-success">✅ User deleted.</div>';
}

// Search filters
$searchName  = trim($_GET['name'] ?? '');
$searchEmail = trim($_GET['email'] ?? '');
$searchPhone = trim($_GET['phone'] ?? '');

$where = "WHERE 1=1";
if ($searchName)  $where .= " AND u.name LIKE '%" . $conn->real_escape_string($searchName) . "%'";
if ($searchEmail) $where .= " AND u.email LIKE '%" . $conn->real_escape_string($searchEmail) . "%'";
if ($searchPhone) $where .= " AND u.phone LIKE '%" . $conn->real_escape_string($searchPhone) . "%'";

$users = $conn->query("SELECT u.*, (SELECT COUNT(*) FROM bookings WHERE user_id = u.user_id) as booking_count FROM users u $where ORDER BY u.created_at DESC");

// Build search bar HTML
$searchBar = '
<form method="GET" style="margin-bottom:20px;">
<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:16px;">
    <div style="flex:1;min-width:140px;">
        <label style="font-size:0.78rem;color:var(--text-muted);display:block;margin-bottom:4px;">👤 Name</label>
        <input type="text" name="name" value="' . htmlspecialchars($searchName) . '" placeholder="Search name..." class="form-control" style="padding:8px 12px;font-size:0.85rem;">
    </div>
    <div style="flex:1;min-width:160px;">
        <label style="font-size:0.78rem;color:var(--text-muted);display:block;margin-bottom:4px;">📧 Email</label>
        <input type="text" name="email" value="' . htmlspecialchars($searchEmail) . '" placeholder="Search email..." class="form-control" style="padding:8px 12px;font-size:0.85rem;">
    </div>
    <div style="flex:1;min-width:130px;">
        <label style="font-size:0.78rem;color:var(--text-muted);display:block;margin-bottom:4px;">📱 Phone</label>
        <input type="text" name="phone" value="' . htmlspecialchars($searchPhone) . '" placeholder="Search phone..." class="form-control" style="padding:8px 12px;font-size:0.85rem;">
    </div>
    <div style="display:flex;gap:8px;align-items:flex-end;">
        <button type="submit" class="btn btn-primary btn-sm" style="padding:8px 18px;font-size:0.85rem;">🔍 Search</button>
        <a href="manage-users.php" class="btn btn-sm" style="padding:8px 14px;font-size:0.85rem;background:var(--bg-dark);border:1px solid var(--border);color:var(--text-muted);">✕ Clear</a>
    </div>
</div>
</form>';

$content = $msg . $searchBar . '<div class="table-wrapper">
<div class="table-header"><h4 class="table-title">👥 All Users</h4><span style="color:var(--text-muted);font-size:0.85rem;">' . $users->num_rows . ' total</span></div>
<table>
<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Bookings</th><th>Joined</th><th>Actions</th></tr></thead>
<tbody>';

while ($u = $users->fetch_assoc()) {
    $content .= '<tr>
    <td>#' . $u['user_id'] . '</td>
    <td style="font-weight:500;">' . htmlspecialchars($u['name']) . '</td>
    <td style="color:var(--text-muted);">' . htmlspecialchars($u['email']) . '</td>
    <td>' . htmlspecialchars($u['phone']) . '</td>
    <td><span class="badge badge-info">' . $u['booking_count'] . '</span></td>
    <td style="color:var(--text-muted);font-size:0.85rem;">' . date('d M Y', strtotime($u['created_at'])) . '</td>
    <td><a href="manage-users.php?delete=' . $u['user_id'] . '" class="btn btn-sm" style="background:rgba(248,68,100,0.15);color:var(--primary);border:1px solid var(--primary);" onclick="return confirm(\'Delete user?\')">Delete</a></td>
    </tr>';
}
$content .= '</tbody></table></div>';
adminLayout('Manage Users', $content, 'admin/manage-users.php');
?>
