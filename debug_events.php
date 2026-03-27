<?php
require_once 'config/config.php';
require_once 'includes/session_guard.php';
echo "<pre>";
echo "=== SESSION DATA ===\n";
foreach($_SESSION as $k => $v) echo "$k => $v\n";

$op_id = (int)($_SESSION['operator_id'] ?? 0);
echo "\n=== op_id used in query: $op_id ===\n\n";

// Show all events
$res = $conn->query("SELECT event_id, event_name, organizer_id, status FROM events ORDER BY event_id DESC");
echo "ALL EVENTS IN DB:\n";
while($r = $res->fetch_assoc()) {
    $match = ($r['organizer_id'] == $op_id) ? '<<< MATCHES YOU' : '';
    echo "ID:{$r['event_id']} | organizer_id:{$r['organizer_id']} | {$r['event_name']} | {$r['status']} $match\n";
}

echo "\n=== My Events Query Result ===\n";
$my = $conn->query("SELECT event_id, event_name FROM events WHERE organizer_id=$op_id");
echo "Count: " . $my->num_rows . "\n";
while($r = $my->fetch_assoc()) echo "  - {$r['event_name']}\n";
echo "</pre>";
?>
