<?php
require_once 'config.php';

// Test database connection
echo "=== DATABASE CONNECTION TEST ===\n";
if ($conn->connect_error) {
    echo "ERROR: " . $conn->connect_error;
    exit;
}
echo "✓ Connected\n\n";

// Check if trips table exists and has data
echo "=== TRIPS TABLE DATA ===\n";
$result = $conn->query("SELECT * FROM trips LIMIT 5");

if (!$result) {
    echo "ERROR: " . $conn->error . "\n";
    exit;
}

if ($result->num_rows == 0) {
    echo "⚠️ No trips found in database\n";
    echo "You need to create a trip first\n";
    echo "\nTo create a trip:\n";
    echo "1. Visit: http://localhost/Traysikel/driver/manage_trips.php\n";
    echo "2. Create a new trip\n";
    echo "3. Set pickup and dropoff locations\n";
    echo "4. Then try tracking\n";
} else {
    echo "✓ Found " . $result->num_rows . " trips\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "Trip ID: " . $row['id'] . "\n";
        echo "  Student ID: " . $row['student_id'] . "\n";
        echo "  Status: " . $row['status'] . "\n";
        echo "  Pickup: " . $row['pickup_lat'] . ", " . $row['pickup_lng'] . "\n";
        echo "  Dropoff: " . $row['dropoff_lat'] . ", " . $row['dropoff_lng'] . "\n";
        echo "  Current: " . ($row['current_lat'] ?? 'NULL') . ", " . ($row['current_lng'] ?? 'NULL') . "\n";
        echo "\n";
    }
}
?>
