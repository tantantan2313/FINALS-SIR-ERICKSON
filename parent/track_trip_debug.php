<?php
require_once '../config.php';
requireRole('parent');

$user_id = $_SESSION['user_id'];
$trip_id = (int)$_GET['trip_id'];

// Get trip data
$trip = $conn->query("
    SELECT t.*, s.name as student_name, s.grade, u.full_name as driver_name, u.phone as driver_phone,
           d.vehicle_type, d.vehicle_plate, d.rating
    FROM trips t
    JOIN students s ON t.student_id = s.id
    JOIN users u ON t.driver_id = u.id
    LEFT JOIN drivers d ON u.id = d.user_id
    WHERE t.id = $trip_id AND s.parent_id = $user_id
")->fetch_assoc();

if (!$trip) {
    die("Invalid Trip");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Trip Tracking - Debug</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <!-- Leaflet JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; }
        .container { display: flex; height: 100vh; }
        .map-container { flex: 1; position: relative; }
        #map { width: 100%; height: 100%; }
        .info { position: absolute; top: 10px; right: 10px; background: white; padding: 15px; border-radius: 5px; max-width: 300px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 1000; max-height: 90vh; overflow-y: auto; }
        .info h3 { margin-bottom: 10px; color: #007bff; }
        .info p { margin: 5px 0; font-size: 13px; }
        .error { color: #d32f2f; font-weight: bold; }
        .success { color: #388e3c; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="map-container">
            <div id="map"></div>
            <div class="info">
                <h3>📍 Debug Info</h3>
                <p><strong>Trip ID:</strong> <?= $trip_id ?></p>
                <p><strong>Student:</strong> <?= $trip['student_name'] ?></p>
                <p><strong>Status:</strong> <?= $trip['status'] ?></p>
                <p><strong>Driver:</strong> <?= $trip['driver_name'] ?></p>
                
                <h4 style="margin-top: 15px; margin-bottom: 10px;">Coordinates:</h4>
                <p><span id="coord-status">Loading...</span></p>
                <p><strong>Pickup:</strong> <?= number_format($trip['pickup_lat'], 4) ?>, <?= number_format($trip['pickup_lng'], 4) ?></p>
                <p><strong>Dropoff:</strong> <?= number_format($trip['dropoff_lat'], 4) ?>, <?= number_format($trip['dropoff_lng'], 4) ?></p>
                <p><strong>Current:</strong> <span id="current-pos"><?= number_format($trip['current_lat'] ?? $trip['pickup_lat'], 4) ?>, <?= number_format($trip['current_lng'] ?? $trip['pickup_lng'], 4) ?></span></p>
                
                <h4 style="margin-top: 15px; margin-bottom: 10px;">API Response:</h4>
                <pre id="api-response" style="font-size: 11px; background: #f5f5f5; padding: 10px; border-radius: 3px; max-height: 200px; overflow-y: auto;"></pre>
            </div>
        </div>
    </div>

    <script>
        const TRIP_ID = <?= $trip_id ?>;
        const PICKUP_LAT = <?= $trip['pickup_lat'] ?>;
        const PICKUP_LNG = <?= $trip['pickup_lng'] ?>;
        const DROPOFF_LAT = <?= $trip['dropoff_lat'] ?>;
        const DROPOFF_LNG = <?= $trip['dropoff_lng'] ?>;
        const CURRENT_LAT = <?= $trip['current_lat'] ?? $trip['pickup_lat'] ?>;
        const CURRENT_LNG = <?= $trip['current_lng'] ?? $trip['pickup_lng'] ?>;

        let map;

        function initMap() {
            console.log('Initializing map with center:', PICKUP_LAT, PICKUP_LNG);
            
            try {
                // Create map
                map = L.map('map').setView([PICKUP_LAT, PICKUP_LNG], 15);
                console.log('Map created successfully');
                
                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(map);
                console.log('Tiles added');

                // Add markers
                L.marker([PICKUP_LAT, PICKUP_LNG], { title: 'Pickup' })
                    .addTo(map)
                    .bindPopup('<b>Pickup Location</b>');
                
                L.marker([DROPOFF_LAT, DROPOFF_LNG], { title: 'Dropoff' })
                    .addTo(map)
                    .bindPopup('<b>Drop-off Location</b>');
                    
                L.marker([CURRENT_LAT, CURRENT_LNG], { title: 'Driver' })
                    .addTo(map)
                    .bindPopup('<b>Driver Current Location</b>');
                
                document.getElementById('coord-status').innerHTML = '<span class="success">✓ Map loaded successfully</span>';
                console.log('Markers added');
                
                // Fetch live data
                fetchLiveData();
                setInterval(fetchLiveData, 3000);
                
            } catch (error) {
                console.error('Map initialization error:', error);
                document.getElementById('coord-status').innerHTML = '<span class="error">✗ Error: ' + error.message + '</span>';
            }
        }

        function fetchLiveData() {
            fetch('/Traysikel/api/get_live_trip.php?trip_id=' + TRIP_ID)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('api-response').textContent = JSON.stringify(data, null, 2);
                    if (data.success) {
                        document.getElementById('current-pos').textContent = 
                            data.trip.current_location.lat.toFixed(4) + ', ' + 
                            data.trip.current_location.lng.toFixed(4);
                    }
                })
                .catch(error => {
                    document.getElementById('api-response').textContent = 'Error: ' + error.message;
                    console.error('API Error:', error);
                });
        }

        // Initialize when page loads
        window.addEventListener('load', function() {
            console.log('Page loaded, initializing map');
            initMap();
        });
    </script>
</body>
</html>
