<!DOCTYPE html>
<html>
<head>
    <title>Traysikel Map Diagnosis</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .test { padding: 10px; margin: 10px 0; border-radius: 3px; }
        .ok { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        textarea { width: 100%; height: 200px; font-family: monospace; font-size: 12px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    </style>
</head>
<body>
    <h1>🔍 Traysikel Live Map Diagnosis</h1>

    <div class="box">
        <h2>1️⃣ Leaflet Library Check</h2>
        <button onclick="testLeaflet()">Test Leaflet</button>
        <div id="leafletResult" class="test"></div>
    </div>

    <div class="box">
        <h2>2️⃣ Database & Trip Check</h2>
        <button onclick="checkTrips()">Check Trips</button>
        <div id="tripsResult" class="test"></div>
    </div>

    <div class="box">
        <h2>3️⃣ API Test</h2>
        <label>Trip ID: <input type="number" id="tripId" value="1" style="width: 100px;"></label>
        <button onclick="testAPI()">Test API</button>
        <div id="apiResult" class="test"></div>
    </div>

    <div class="box">
        <h2>4️⃣ Map Container Test</h2>
        <p>A small test map should appear below:</p>
        <div id="testMap" style="width: 100%; height: 300px; border: 2px solid #ddd; border-radius: 5px; background: #e0e0e0;"></div>
        <button onclick="testMapRendering()">Initialize Test Map</button>
        <div id="mapResult" class="test"></div>
    </div>

    <div class="box">
        <h2>📋 Raw Data View</h2>
        <label>Trip ID: <input type="number" id="tripIdRaw" value="1" style="width: 100px;"></label>
        <button onclick="viewRawTrip()">View Raw Trip Data</button>
        <textarea id="rawData" readonly></textarea>
    </div>

    <div class="box">
        <h2>🔗 Quick Links</h2>
        <ul>
            <li><a href="track_trip.php?trip_id=1" target="_blank">Track Trip (ID=1)</a></li>
            <li><a href="track_trip_debug.php?trip_id=1" target="_blank">Debug Track Page (ID=1)</a></li>
            <li><a href="../api/get_live_trip.php?trip_id=1" target="_blank">API Direct (ID=1)</a></li>
            <li><a href="../test_trips.php" target="_blank">Database Test</a></li>
        </ul>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />

    <script>
        function testLeaflet() {
            const result = document.getElementById('leafletResult');
            try {
                if (window.L) {
                    result.innerHTML = '<div class="test ok">✓ Leaflet loaded successfully<br>Version: ' + L.version + '</div>';
                } else {
                    result.innerHTML = '<div class="test error">✗ Leaflet not loaded</div>';
                }
            } catch (e) {
                result.innerHTML = '<div class="test error">✗ Error: ' + e.message + '</div>';
            }
        }

        function checkTrips() {
            const result = document.getElementById('tripsResult');
            result.innerHTML = 'Loading...';
            
            fetch('../api/check_trip.php')
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        result.innerHTML = '<div class="test warning">⚠️ ' + data.error + '</div>';
                    } else {
                        result.innerHTML = '<div class="test ok">✓ Trip data found<br><code>' + JSON.stringify(data, null, 2).substring(0, 200) + '...</code></div>';
                    }
                })
                .catch(e => {
                    result.innerHTML = '<div class="test error">✗ Error: ' + e.message + '</div>';
                });
        }

        function testAPI() {
            const tripId = document.getElementById('tripId').value;
            const result = document.getElementById('apiResult');
            result.innerHTML = 'Testing...';
            
            fetch('../api/get_live_trip.php?trip_id=' + tripId)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const trip = data.trip;
                        result.innerHTML = '<div class="test ok">✓ API working<br>Student: ' + trip.student_name + '<br>Current Pos: ' + trip.current_location.lat.toFixed(4) + ', ' + trip.current_location.lng.toFixed(4) + '</div>';
                    } else {
                        result.innerHTML = '<div class="test error">✗ API Error: ' + data.error + '</div>';
                    }
                })
                .catch(e => {
                    result.innerHTML = '<div class="test error">✗ Error: ' + e.message + '</div>';
                });
        }

        function testMapRendering() {
            const result = document.getElementById('mapResult');
            try {
                const map = L.map('testMap').setView([15.0754, 120.6563], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                L.marker([15.0754, 120.6563]).addTo(map).bindPopup('Test Marker');
                result.innerHTML = '<div class="test ok">✓ Test map rendered successfully</div>';
                console.log('Map rendered');
            } catch (e) {
                result.innerHTML = '<div class="test error">✗ Error: ' + e.message + '</div>';
                console.error(e);
            }
        }

        function viewRawTrip() {
            const tripId = document.getElementById('tripIdRaw').value;
            const textarea = document.getElementById('rawData');
            textarea.value = 'Loading...';
            
            fetch('../api/get_live_trip.php?trip_id=' + tripId)
                .then(r => r.json())
                .then(data => {
                    textarea.value = JSON.stringify(data, null, 2);
                })
                .catch(e => {
                    textarea.value = 'Error: ' + e.message;
                });
        }

        // Run tests on load
        window.addEventListener('load', function() {
            testLeaflet();
        });
    </script>
</body>
</html>
