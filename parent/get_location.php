<?php
require_once '../config.php';
requireRole('parent');

$trip_id = (int)$_GET['trip_id'];

$data = $conn->query("
    SELECT current_lat, current_lng 
    FROM trips 
    WHERE id = $trip_id
")->fetch_assoc();

echo json_encode($data);