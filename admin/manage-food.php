<?php
require_once 'admin-helper.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_food'])) {
    $name  = $conn->real_escape_string(trim($_POST['food_name']));
    $price = floatval($_POST['price']);
    $conn->query("INSERT INTO foods (food_name, price, image) VALUES ('$name', $price, 'food.jpg')");
    $msg = '<div class="alert alert-success">✅ Food item added!</div>';
}
if (isset($_GET['delete'])) {
    $fid = (int)$_GET['delete'];
    $conn->query("DELETE FROM foods WHERE food_id = $fid");
    $msg = '<div class="alert alert-success">✅ Food item deleted.</div>';
}

$foods = $conn->query("SELECT * FROM foods ORDER BY food_name");

$content = $msg . '
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:24px;">
<h4 style="margin-bottom:16px;">➕ Add Food Item</h4>
<form method="POST">
<div class="form-row">
<div class="form-group"><label class="form-label">Food Name</label><input type="text" class="form-control" name="food_name" required></div>
<div class="form-group"><label class="form-label">Price (₹)</label><input type="number" class="form-control" name="price" min="1" step="0.01" required></div>
</div>
<button type="submit" name="add_food" class="btn btn-primary">Add Item</button>
</form>
</div>
<div class="table-wrapper">
<div class="table-header"><h4 class="table-title">🍿 Food Menu</h4></div>
<table>
<thead><tr><th>ID</th><th>Item</th><th>Price</th><th>Actions</th></tr></thead>
<tbody>';

while ($f = $foods->fetch_assoc()) {
    $content .= '<tr>
    <td>#' . $f['food_id'] . '</td>
    <td style="font-weight:500;">' . htmlspecialchars($f['food_name']) . '</td>
    <td style="color:var(--primary);font-weight:700;">₹' . number_format($f['price'],2) . '</td>
    <td><a href="manage-food.php?delete=' . $f['food_id'] . '" class="btn btn-sm" style="background:rgba(248,68,100,0.15);color:var(--primary);border:1px solid var(--primary);" onclick="return confirm(\'Delete?\')">Delete</a></td>
    </tr>';
}
$content .= '</tbody></table></div>';
adminLayout('Manage Food Menu', $content, 'admin/manage-food.php');
?>
