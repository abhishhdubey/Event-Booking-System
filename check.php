<?php
require_once 'config/config.php';
$result = $conn->query("SELECT * FROM movies WHERE is_slider_ad = 1");
if (!$result) {
    echo "Error: " . $conn->error;
} else {
    echo "Success! Rows: " . $result->num_rows;
}
?>
