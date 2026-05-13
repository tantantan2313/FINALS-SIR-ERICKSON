<?php
require_once '../config.php';
requireRole('parent');

$user_id = $_SESSION['user_id'];
$trip_id = (int)$_GET['trip_id'];

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
    <title>Live Trip Tracking - <?= htmlspecialchars($trip['student_name']) ?></title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; width: 100%; overflow: hidden; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .container {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        .map-container {
            flex: 1;
            position: relative;
            background: #e0e0e0;
            overflow: hidden;
        }

        #map {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }

        .info-panel {
            width: 380px;
            background: white;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .panel-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .panel-header h2 { font-size: 18px; margin-top: 10px; }

        .student-info {
            padding: 20px;
            border-bottom: 1px solid #eee;
            background: #f9f9f9;
        }

        .student-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .student-avatar {
            width: 60px;
            height: 60px;
            background: #007bff;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-right: 15px;
            font-weight: bold;
        }

        .student-details h3 { font-size: 18px; color: #333; margin-bottom: 3px; }
        .student-details p { font-size: 13px; color: #666; }

        .driver-info {
            padding: 20px;
            border-bottom: 1px solid #eee;
            background: white;
        }

        .driver-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .driver-avatar {
            width: 50px;
            height: 50px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 12px;
        }

        .driver-details h3 { font-size: 16px; color: #333; margin-bottom: 3px; }
        .driver-rating { color: #ffc107; font-size: 14px; }
        .driver-phone { font-size: 12px; color: #666; margin-top: 5px; }

        .vehicle-info {
            display: flex;
            justify-content: space-between;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #eee;
            font-size: 13px;
        }

        .vehicle-info label { color: #999; font-weight: 600; display: block; margin-bottom: 3px; }
        .vehicle-info span { color: #333; }

        .trip-progress {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .eta-section {
            display: flex;
            justify-content: space-around;
            text-align: center;
            margin-bottom: 20px;
        }

        .eta-label { font-size: 12px; color: #666; margin-bottom: 5px; text-transform: uppercase; }
        .eta-value { font-size: 24px; font-weight: bold; color: #007bff; }

        .progress-bar { width: 100%; height: 6px; background: #eee; border-radius: 3px; overflow: hidden; margin-top: 15px; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s ease; }

        .location-details { padding: 20px; border-bottom: 1px solid #eee; }
        .location-item { display: flex; margin-bottom: 15px; }
        .location-icon { font-size: 20px; margin-right: 15px; min-width: 25px; }
        .location-text h4 { font-size: 13px; color: #666; margin-bottom: 3px; font-weight: 600; text-transform: uppercase; }
        .location-text p { font-size: 14px; color: #333; line-height: 1.4; }

        .call-button {
            padding: 20px;
            display: flex;
            gap: 10px;
        }

        .call-button a {
            flex: 1;
            padding: 12px;
            text-align: center;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .call-button a:hover { background: #0056b3; }

        .loading { text-align: center; color: #999; padding: 10px; font-size: 12px; }

        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .info-panel { width: 100%; height: 50%; order: 2; }
            .map-container { height: 50%; order: 1; }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="map-container">
            <div id="map"></div>
        </div>

        <div class="info-panel">
            <div class="panel-header">
                <div style="font-size: 12px; opacity: 0.9; margin-bottom: 5px; text-transform: uppercase;"><?= ucfirst($trip['status']) ?></div>
                <h2>Live Trip Tracking</h2>
            </div>

            <div class="student-info">
                <div class="student-header">
                    <div class="student-avatar"><?= substr($trip['student_name'], 0, 1) ?></div>
                    <div class="student-details">
                        <h3><?= htmlspecialchars($trip['student_name']) ?></h3>
                        <p><?= htmlspecialchars($trip['grade']) ?></p>
                    </div>
                </div>
            </div>

            <div class="driver-info">
                <div class="driver-header">
                    <div class="driver-avatar">👨‍💼</div>
                    <div class="driver-details">
                        <h3><?= htmlspecialchars($trip['driver_name']) ?></h3>
                        <div class="driver-rating">⭐ <?= $trip['rating'] ? number_format($trip['rating'], 1) : 'New' ?></div>
                        <div class="driver-phone">📞 <?= htmlspecialchars($trip['driver_phone']) ?></div>
                    </div>
                </div>
                <div class="vehicle-info">
                    <div>
                        <label>Vehicle</label>
                        <span><?= htmlspecialchars($trip['vehicle_type']) ?></span>
                    </div>
                    <div>
                        <label>Plate</label>
                        <span><?= htmlspecialchars($trip['vehicle_plate']) ?></span>
                    </div>
                </div>
            </div>

            <div class="trip-progress">
                <div class="eta-section">
                    <div>
                        <div class="eta-label">ETA</div>
                        <div class="eta-value" id="eta">-</div>
                    </div>
                    <div>
                        <div class="eta-label">Distance</div>
                        <div class="eta-value" id="distance">-</div>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill" style="width: 0%"></div>
                </div>
                <div class="loading" id="updateStatus">Updating location...</div>
            </div>

            <div class="location-details">
                <div class="location-item">
                    <div class="location-icon">📍</div>
                    <div class="location-text">
                        <h4>Pickup Location</h4>
                        <p id="pickupLocation">Getting location...</p>
                    </div>
                </div>
                <div class="location-item">
                    <div class="location-icon">🎯</div>
                    <div class="location-text">
                        <h4>Drop-off Location</h4>
                        <p id="dropoffLocation">Getting location...</p>
                    </div>
                </div>
            </div>

            <div class="call-button">
                <a href="tel:<?= $trip['driver_phone'] ?>">📞 Call Driver</a>
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $trip['driver_phone']) ?>" target="_blank">💬 WhatsApp</a>
            </div>
        </div>
    </div>

    <script>
        const TRIP_ID = <?= $trip_id ?>;
        const PICKUP_LAT = <?= (float)$trip['pickup_lat'] ?>;
        const PICKUP_LNG = <?= (float)$trip['pickup_lng'] ?>;
        const DROPOFF_LAT = <?= (float)$trip['dropoff_lat'] ?>;
        const DROPOFF_LNG = <?= (float)$trip['dropoff_lng'] ?>;

        let map, driverMarker, pickupMarker, dropoffMarker, polyline;
        let routePath = [];

        function initMap() {
            console.log('🗺️ Initializing map...');
            try {
                // Create map
                map = L.map('map', {
                    center: [PICKUP_LAT, PICKUP_LNG],
                    zoom: 15,
                    zoomControl: true,
                    attributionControl: true
                });

                console.log('✓ Map created');

                // Add tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(map);

                console.log('✓ Tiles added');

                // Markers
                pickupMarker = L.marker([PICKUP_LAT, PICKUP_LNG], {
                    title: 'Pickup'
                }).addTo(map).bindPopup('<b>📍 Pickup</b>');

                dropoffMarker = L.marker([DROPOFF_LAT, DROPOFF_LNG], {
                    title: 'Dropoff'
                }).addTo(map).bindPopup('<b>🎯 Drop-off</b>');

                driverMarker = L.circleMarker([PICKUP_LAT, PICKUP_LNG], {
                    radius: 8,
                    fillColor: '#007bff',
                    color: 'white',
                    weight: 3,
                    fillOpacity: 1
                }).addTo(map).bindPopup('<b>Driver</b>');

                polyline = L.polyline([], {
                    color: '#007bff',
                    weight: 3,
                    opacity: 0.7
                }).addTo(map);

                console.log('✓ Map ready! Starting updates...');
                updateLiveLocation();
                setInterval(updateLiveLocation, 3000);

            } catch (error) {
                console.error('❌ Map error:', error);
            }
        }

        function updateLiveLocation() {
            fetch('/Traysikel/api/get_live_trip.php?trip_id=' + TRIP_ID)
                .then(r => r.json())
                .then(data => {
                    if (data.success && map) {
                        const trip = data.trip;
                        const lat = trip.current_location.lat;
                        const lng = trip.current_location.lng;

                        if (lat && lng) {
                            driverMarker.setLatLng([lat, lng]);
                            routePath.push([lat, lng]);
                            polyline.setLatLngs(routePath);
                            map.panTo([lat, lng]);
                        }

                        document.getElementById('eta').textContent = trip.eta_minutes ? trip.eta_minutes + ' min' : '-';
                        document.getElementById('distance').textContent = trip.distance_remaining_km + ' km';

                        const progress = Math.min(100, ((10 - trip.distance_remaining_km) / 10) * 100);
                        document.getElementById('progressFill').style.width = progress + '%';
                        document.getElementById('updateStatus').textContent = 'Last updated: ' + new Date().toLocaleTimeString();
                    }
                })
                .catch(e => console.error('Update error:', e));
        }

        window.addEventListener('load', function() {
            console.log('Page loaded');
            setTimeout(() => {
                initMap();
                document.getElementById('pickupLocation').textContent = PICKUP_LAT.toFixed(4) + ', ' + PICKUP_LNG.toFixed(4);
                document.getElementById('dropoffLocation').textContent = DROPOFF_LAT.toFixed(4) + ', ' + DROPOFF_LNG.toFixed(4);
            }, 300);
        });
    </script>
</body>
</html>
