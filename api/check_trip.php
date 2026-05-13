<?php
// api/check_trip.php
// Diagnostic API endpoint that returns a sample trip record for testing trip connectivity.
require_once '../config.php';

// Get sample trip
$result = $conn->query("
    SELECT t.*, s.name as student_name
    FROM trips t
    JOIN students s ON t.student_id = s.id
    LIMIT 1
");

if ($result && $result->num_rows > 0) {
    $trip = $result->fetch_assoc();
    echo json_encode($trip, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} else {
    echo json_encode(['error' => 'No trips found', 'message' => 'Create a trip first']);
}
?>
