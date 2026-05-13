<?php
/**
 * Get Live Trip Data API
 * Used by parents to get real-time trip information
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$trip_id = (int)($_GET['trip_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$trip_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing trip_id']);
    exit;
}

// Verify access - parent can only see their child's trips
if (getUserRole() === 'parent') {
    $trip = $conn->query("
        SELECT t.*, s.parent_id 
        FROM trips t
        JOIN students s ON t.student_id = s.id
        WHERE t.id = $trip_id AND s.parent_id = $user_id
    ")->fetch_assoc();
} else {
    $trip = $conn->query("
        SELECT * FROM trips WHERE id = $trip_id
    ")->fetch_assoc();
}

if (!$trip) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid trip or no access']);
    exit;
}

// Get full trip details
$full_trip = $conn->query("
    SELECT 
        t.*,
        s.name as student_name,
        s.address as student_address,
        s.grade as student_grade,
        u.full_name as driver_name,
        u.phone as driver_phone,
        d.vehicle_type,
        d.vehicle_plate,
        d.rating as driver_rating
    FROM trips t
    JOIN students s ON t.student_id = s.id
    JOIN users u ON t.driver_id = u.id
    LEFT JOIN drivers d ON u.id = d.user_id
    WHERE t.id = $trip_id
")->fetch_assoc();

// Calculate ETA and distance
$distance = 0;
$eta_minutes = null;

if ($full_trip['current_lat'] && $full_trip['current_lng']) {
    $distance = calculateDistance(
        $full_trip['current_lat'], $full_trip['current_lng'],
        $full_trip['dropoff_lat'], $full_trip['dropoff_lng']
    );
    
    // Estimate 30 km/h average speed
    $eta_minutes = ceil(($distance / 30) * 60);
}

echo json_encode([
    'success' => true,
    'trip' => [
        'id' => $full_trip['id'],
        'status' => $full_trip['status'],
        'student_name' => $full_trip['student_name'],
        'student_address' => $full_trip['student_address'],
        'student_grade' => $full_trip['student_grade'],
        'driver_name' => $full_trip['driver_name'],
        'driver_phone' => $full_trip['driver_phone'],
        'driver_rating' => $full_trip['driver_rating'],
        'vehicle_type' => $full_trip['vehicle_type'],
        'vehicle_plate' => $full_trip['vehicle_plate'],
        'trip_date' => $full_trip['trip_date'],
        'pickup_time' => $full_trip['pickup_time'],
        'dropoff_time' => $full_trip['dropoff_time'],
        'current_location' => [
            'lat' => (float)$full_trip['current_lat'] ?: (float)$full_trip['pickup_lat'],
            'lng' => (float)$full_trip['current_lng'] ?: (float)$full_trip['pickup_lng']
        ],
        'pickup_location' => [
            'lat' => (float)$full_trip['pickup_lat'],
            'lng' => (float)$full_trip['pickup_lng']
        ],
        'dropoff_location' => [
            'lat' => (float)$full_trip['dropoff_lat'],
            'lng' => (float)$full_trip['dropoff_lng']
        ],
        'distance_remaining_km' => round($distance, 2),
        'eta_minutes' => $eta_minutes,
        'notes' => $full_trip['notes'],
        'updated_at' => $full_trip['updated_at'] ?? $full_trip['created_at']
    ]
]);

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
