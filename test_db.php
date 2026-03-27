<?php
require 'config/config.php';
$movies = $conn->query("SHOW COLUMNS FROM movies");
echo "MOVIES COLUMNS:\n";
while($r = $movies->fetch_assoc()) echo $r['Field'] . " - " . $r['Type'] . "\n";
echo "\nTHEATRES COLUMNS:\n";
$theatres = $conn->query("SHOW COLUMNS FROM theatres");
while($r = $theatres->fetch_assoc()) echo $r['Field'] . " - " . $r['Type'] . "\n";
?>
