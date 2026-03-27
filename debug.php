<?php
require_once 'config/config.php';
$res = $conn->query("SELECT s.*, t.theatre_name, t.licence_status, t.city FROM shows s JOIN theatres t ON s.theatre_id = t.theatre_id WHERE s.movie_id = 7");
echo "Total shows for movie 7: " . $res->num_rows . "<br><br>";
while ($row = $res->fetch_assoc()) {
    echo "Show ID: {$row['show_id']} | Theatre: {$row['theatre_name']} | City: {$row['city']} | Date: {$row['show_date']} | Time: {$row['show_time']} | Licence: {$row['licence_status']} | Status: {$row['status']} <br>";
}
?>
