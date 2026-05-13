<?php
require_once '../config.php';
requireRole('driver');

$user_id = $_SESSION['user_id'];

$trip_id = (int)$_POST['trip_id'];
$lat = (float)$_POST['lat'];
$lng = (float)$_POST['lng'];

$check = $conn->query("
    SELECT id FROM trips 
    WHERE id=$trip_id AND driver_id=$user_id
");

if ($check->num_rows > 0) {
    $conn->query("
        UPDATE trips 
        SET current_lat=$lat, current_lng=$lng 
        WHERE id=$trip_id
    ");

    echo "OK";
} else {
    echo "ERROR";
}