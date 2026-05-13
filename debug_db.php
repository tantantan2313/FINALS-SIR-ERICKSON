<?php
require_once 'config.php';

// Check trips table structure
echo "=== TRIPS TABLE STRUCTURE ===\n";
$result = $conn->query("DESCRIBE trips");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n=== SAMPLE TRIP DATA ===\n";
$result = $conn->query("SELECT * FROM trips LIMIT 1");
$trip = $result->fetch_assoc();
if ($trip) {
    foreach ($trip as $key => $value) {
        echo "$key: $value\n";
    }
} else {
    echo "No trips found\n";
}
?>
