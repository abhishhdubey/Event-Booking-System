<?php
require 'config/config.php';
$res = $conn->query("SHOW COLUMNS FROM event_bookings");
while ($r = $res->fetch_assoc()) echo $r['Field'] . "\n";
echo "---\n";
$res = $conn->query("SHOW COLUMNS FROM events");
while ($r = $res->fetch_assoc()) echo $r['Field'] . "\n";
?>
