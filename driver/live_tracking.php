<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live GPS Tracking - Driver</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .trip-selector {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .form-group {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            flex: 1;
            min-width: 200px;
        }

        button {
            padding: 8px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }

        button:hover {
            background: #0056b3;
        }

        .status-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .status-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
        }

        .status-label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .status-value {
            font-size: 20px;
            font-weight: bold;
        }

        .gps-section {
            background: #f0f9ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 2px solid #007bff;
        }

        .gps-section h2 {
            color: #007bff;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .gps-input-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .gps-input-group input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .button-group button {
            flex: 1;
        }

        .button-group button.secondary {
            background: #6c757d;
        }

        .button-group button.secondary:hover {
            background: #5a6268;
        }

        .info-box {
            background: #e7f3ff;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin-top: 20px;
            border-radius: 4px;
        }

        .info-box strong {
            color: #0056b3;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
            display: none;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
            display: none;
        }

        .current-location {
            background: white;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
            border: 1px solid #ddd;
        }

        .current-location p {
            margin: 8px 0;
            color: #333;
        }

        .accuracy {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .gps-input-group {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📍 Live GPS Tracking System</h1>

        <!-- Trip Selector -->
        <div class="trip-selector">
            <div class="form-group">
                <div style="flex: 1; min-width: 200px;">
                    <label for="tripSelect">Select Active Trip:</label>
                    <select id="tripSelect">
                        <option value="">-- Select a trip --</option>
                    </select>
                </div>
                <button onclick="loadTrip()">Load Trip</button>
            </div>
        </div>

        <!-- Status Info -->
        <div class="status-info" id="statusInfo" style="display: none;">
            <div class="status-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="status-label">Student</div>
                <div class="status-value" id="studentName">-</div>
            </div>
            <div class="status-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="status-label">Trip Status</div>
                <div class="status-value" id="tripStatus">-</div>
            </div>
            <div class="status-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="status-label">Distance to Destination</div>
                <div class="status-value" id="distanceInfo">-</div>
            </div>
        </div>

        <!-- GPS Input Section -->
        <div class="gps-section" id="gpsSection" style="display: none;">
            <h2>🎯 Send Live Location</h2>
            
            <p style="margin-bottom: 15px; color: #555;">
                Your device's GPS coordinates will be automatically sent to track your vehicle in real-time.
            </p>

            <div class="gps-input-group">
                <div>
                    <label for="latitude">Latitude</label>
                    <input type="number" id="latitude" placeholder="e.g., 15.0754" step="0.0001" readonly>
                </div>
                <div>
                    <label for="longitude">Longitude</label>
                    <input type="number" id="longitude" placeholder="e.g., 120.6563" step="0.0001" readonly>
                </div>
            </div>

            <div class="button-group">
                <button onclick="startTracking()">🟢 Start Live Tracking</button>
                <button class="secondary" onclick="stopTracking()">🔴 Stop Tracking</button>
                <button class="secondary" onclick="sendManualLocation()">📤 Send Location Now</button>
            </div>

            <div class="current-location" id="currentLocation" style="display: none;">
                <strong>Current Location:</strong>
                <p>📍 Latitude: <span id="currentLat">-</span></p>
                <p>📍 Longitude: <span id="currentLng">-</span></p>
                <div class="accuracy">
                    Accuracy: <span id="accuracy">-</span> meters<br>
                    Last Updated: <span id="lastUpdate">-</span>
                </div>
            </div>

            <div class="success" id="successMessage">✅ Location sent successfully!</div>
            <div class="error" id="errorMessage">❌ Error sending location</div>
        </div>

        <!-- Info Box -->
        <div class="info-box">
            <strong>💡 How it works:</strong>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Select an active trip from the dropdown</li>
                <li>Click "Start Live Tracking" to enable GPS sharing</li>
                <li>Your location updates every 5 seconds</li>
                <li>Parents can see your real-time location on the map</li>
                <li>Stop tracking when trip is complete</li>
            </ul>
        </div>
    </div>

    <script>
        let currentTrip = null;
        let trackingInterval = null;
        let watchId = null;

        // Load available trips
        function loadAvailableTrips() {
            // This would fetch from your API
            // For now, showing example structure
            const trips = [
                { id: 1, student: 'John Doe', status: 'in_transit', time: '09:30 AM' },
                { id: 2, student: 'Jane Smith', status: 'in_transit', time: '10:15 AM' }
            ];

            const select = document.getElementById('tripSelect');
            select.innerHTML = '<option value="">-- Select a trip --</option>';
            
            trips.forEach(trip => {
                const option = document.createElement('option');
                option.value = trip.id;
                option.textContent = `${trip.student} (${trip.status}) - ${trip.time}`;
                select.appendChild(option);
            });
        }

        // Load selected trip
        function loadTrip() {
            const tripId = document.getElementById('tripSelect').value;
            if (!tripId) {
                alert('Please select a trip');
                return;
            }

            // Simulate loading trip data
            currentTrip = {
                id: tripId,
                student: 'John Doe',
                status: 'in_transit',
                distance: '2.5 km'
            };

            // Update UI
            document.getElementById('statusInfo').style.display = 'grid';
            document.getElementById('gpsSection').style.display = 'block';
            document.getElementById('studentName').textContent = currentTrip.student;
            document.getElementById('tripStatus').textContent = currentTrip.status.toUpperCase();
            document.getElementById('distanceInfo').textContent = currentTrip.distance;
        }

        // Start live tracking
        function startTracking() {
            if (!currentTrip) {
                alert('Please select a trip first');
                return;
            }

            if (navigator.geolocation) {
                document.getElementById('currentLocation').style.display = 'block';
                
                // Get current position once
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        updateLocationUI(position);
                        sendLocation(position);
                    },
                    function(error) {
                        showError('Error getting location: ' + error.message);
                    }
                );

                // Watch position (continuous tracking)
                watchId = navigator.geolocation.watchPosition(
                    function(position) {
                        updateLocationUI(position);
                        sendLocation(position);
                    },
                    function(error) {
                        console.error('Geolocation error:', error);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );

                showSuccess('Live tracking started!');
            } else {
                showError('Geolocation is not supported by your browser');
            }
        }

        // Stop tracking
        function stopTracking() {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
                document.getElementById('currentLocation').style.display = 'none';
                showSuccess('Live tracking stopped');
            }
        }

        // Update location UI
        function updateLocationUI(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = Math.round(position.coords.accuracy);

            document.getElementById('latitude').value = lat.toFixed(6);
            document.getElementById('longitude').value = lng.toFixed(6);
            document.getElementById('currentLat').textContent = lat.toFixed(6);
            document.getElementById('currentLng').textContent = lng.toFixed(6);
            document.getElementById('accuracy').textContent = accuracy;
            document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
        }

        // Send location to server
        function sendLocation(position) {
            const data = new FormData();
            data.append('trip_id', currentTrip.id);
            data.append('lat', position.coords.latitude);
            data.append('lng', position.coords.longitude);
            data.append('accuracy', position.coords.accuracy);

            fetch('/Traysikel/api/update_live_location.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Location sent:', data);
                } else {
                    console.error('Failed to send location:', data.error);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Send location manually
        function sendManualLocation() {
            const lat = document.getElementById('latitude').value;
            const lng = document.getElementById('longitude').value;

            if (!lat || !lng) {
                showError('Please enable location tracking first');
                return;
            }

            sendLocation({
                coords: {
                    latitude: parseFloat(lat),
                    longitude: parseFloat(lng),
                    accuracy: 10
                }
            });

            showSuccess('Location sent to parents!');
        }

        // Helper functions
        function showSuccess(message) {
            const box = document.getElementById('successMessage');
            box.textContent = '✅ ' + message;
            box.style.display = 'block';
            setTimeout(() => box.style.display = 'none', 3000);
        }

        function showError(message) {
            const box = document.getElementById('errorMessage');
            box.textContent = '❌ ' + message;
            box.style.display = 'block';
            setTimeout(() => box.style.display = 'none', 3000);
        }

        // Initialize on page load
        window.addEventListener('load', function() {
            loadAvailableTrips();
        });
    </script>
</body>
</html>
