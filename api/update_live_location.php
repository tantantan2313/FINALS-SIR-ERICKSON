<?php
/**
 * Live Location Update API for Drivers
 * Receives GPS coordinates from driver's mobile/browser
 * Returns current status for real-time tracking
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || getUserRole() !== 'driver') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$trip_id = (int)($_POST['trip_id'] ?? 0);
$lat = (float)($_POST['lat'] ?? 0);
$lng = (float)($_POST['lng'] ?? 0);
$accuracy = (float)($_POST['accuracy'] ?? 0);

if (!$trip_id || !$lat || !$lng) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Verify trip belongs to driver and is active
$trip_check = $conn->query("
    SELECT id, status FROM trips 
    WHERE id = $trip_id AND driver_id = $user_id 
    AND status IN ('picked_up', 'in_transit')
");

if ($trip_check->num_rows == 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid trip or trip not active']);
    exit;
}

$trip = $trip_check->fetch_assoc();

// Update current location
$stmt = $conn->prepare("
    UPDATE trips 
    SET current_lat = ?, current_lng = ?, updated_at = NOW()
    WHERE id = ?
");
$stmt->bind_param("ddi", $lat, $lng, $trip_id);
$result = $stmt->execute();
$stmt->close();

if ($result) {
    // Return current trip data
    $trip_data = $conn->query("
        SELECT 
            t.*,
            s.name as student_name,
            s.parent_id,
            u.full_name as driver_name
        FROM trips t
        JOIN students s ON t.student_id = s.id
        JOIN users u ON t.driver_id = u.id
        WHERE t.id = $trip_id
    ")->fetch_assoc();
    
    // Calculate distance to destination
    $distance = calculateDistance(
        $lat, $lng,
        $trip_data['dropoff_lat'], $trip_data['dropoff_lng']
    );
    
    echo json_encode([
        'success' => true,
        'trip_id' => $trip_id,
        'status' => $trip['status'],
        'current_lat' => $lat,
        'current_lng' => $lng,
        'distance_remaining' => round($distance, 2),
        'accuracy' => $accuracy,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update location']);
}

/**
 * Calculate distance between two coordinates (Haversine formula)
 * Returns distance in kilometers
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371; // Radius of earth in km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    
    $c = 2 * asin(sqrt($a));
    $distance = $R * $c;
    
    return $distance;
}
?>
