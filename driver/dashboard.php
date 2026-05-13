<?php
require_once '../config.php';
requireRole('driver');

$user_id = $_SESSION['user_id'];

// Check if driver is approved
$driver_check = $conn->query("SELECT status FROM drivers WHERE user_id = $user_id");
$driver_status = $driver_check->fetch_assoc()['status'];

if ($driver_status !== 'approved') {
    echo "<div class='container'><h1>Account Pending Approval</h1><p>Your driver account is still pending admin approval.</p><a href='../logout.php'>Logout</a></div>";
    exit();
}

// Get assigned students
$assigned_students = $conn->query("
    SELECT s.*, u.full_name as parent_name, u.phone as parent_phone
    FROM students s
    JOIN users u ON s.parent_id = u.id
    WHERE s.driver_id = $user_id
");

// Get today's trips
$today_trips = $conn->query("
    SELECT t.*, s.name as student_name, s.address, u.full_name as parent_name, u.phone as parent_phone
    FROM trips t
    JOIN students s ON t.student_id = s.id
    JOIN users u ON s.parent_id = u.id
    WHERE t.driver_id = $user_id AND t.trip_date = CURDATE()
    ORDER BY t.pickup_time
");

// Get quick statistics
$quick_stats = $conn->query("
    SELECT
        COUNT(*) as total_trips,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_trips,
        COUNT(CASE WHEN status IN ('picked_up', 'in_transit') THEN 1 END) as active_trips,
        COUNT(CASE WHEN trip_date = CURDATE() THEN 1 END) as today_trips,
        COUNT(CASE WHEN trip_date = CURDATE() AND status IN ('picked_up', 'in_transit', 'completed') THEN 1 END) as today_active
    FROM trips
    WHERE driver_id = $user_id
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - School Transport System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="nav">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_trips.php">Manage Trips</a></li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="trip_history.php">Trip History</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>

        <h1>Driver Dashboard</h1>

        <div class="quick-stats-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px;">
            <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; text-align: center;">
                <div style="color: #1976d2; font-size: 24px; font-weight: bold;"><?php echo $quick_stats['total_trips']; ?></div>
                <div style="color: #666; font-size: 12px; margin-top: 5px;">Total Trips</div>
            </div>
            <div style="background: #e8f5e9; padding: 15px; border-radius: 5px; text-align: center;">
                <div style="color: #388e3c; font-size: 24px; font-weight: bold;"><?php echo $quick_stats['completed_trips']; ?></div>
                <div style="color: #666; font-size: 12px; margin-top: 5px;">Completed</div>
            </div>
            <div style="background: #fff3e0; padding: 15px; border-radius: 5px; text-align: center;">
                <div style="color: #f57c00; font-size: 24px; font-weight: bold;"><?php echo $quick_stats['active_trips']; ?></div>
                <div style="color: #666; font-size: 12px; margin-top: 5px;">Active</div>
            </div>
            <div style="background: #f3e5f5; padding: 15px; border-radius: 5px; text-align: center;">
                <div style="color: #7b1fa2; font-size: 24px; font-weight: bold;"><?php echo $quick_stats['today_trips']; ?></div>
                <div style="color: #666; font-size: 12px; margin-top: 5px;">Today's Trips</div>
            </div>
        </div>

        <h2>Assigned Students</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Grade</th>
                    <th>Address</th>
                    <th>Parent</th>
                    <th>Parent Phone</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($student = $assigned_students->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $student['name']; ?></td>
                        <td><?php echo $student['grade']; ?></td>
                        <td><?php echo $student['address']; ?></td>
                        <td><?php echo $student['parent_name']; ?></td>
                        <td><?php echo $student['parent_phone']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h2>Today's Trips</h2>
        <?php if ($today_trips->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Pickup Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($trip = $today_trips->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $trip['student_name']; ?></td>
                            <td><?php echo $trip['pickup_time']; ?></td>
                            <td><span class="status-<?php echo $trip['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $trip['status'])); ?></span></td>
                            <td>
                                <?php if ($trip['status'] == 'scheduled'): ?>
                                    <button onclick="performAction('arrived', <?php echo $trip['id']; ?>)" style="background: #28a745; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; margin-right: 5px;">Arrived</button>
                                    <button onclick="performAction('start', <?php echo $trip['id']; ?>)" style="background: #007bff; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer;">Start Trip</button>
                                <?php elseif ($trip['status'] == 'picked_up'): ?>
                                    <button onclick="performAction('start', <?php echo $trip['id']; ?>)" style="background: #007bff; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer;">Start Trip</button>
                                <?php elseif ($trip['status'] == 'in_transit'): ?>
                                    <a href="dropoff_trip.php?trip_id=<?php echo $trip['id']; ?>" style="background: #dc3545; color: white; padding: 5px 10px; border: none; border-radius: 3px; text-decoration: none; display: inline-block;">Drop Off</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No trips scheduled for today.</p>
        <?php endif; ?>

        <h2 style="margin-top: 30px;">Trip Statistics (All Time)</h2>
        <div class="stats-summary" style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <p><strong>Total Trips:</strong> <?php echo $quick_stats['total_trips']; ?> | 
               <strong>Completed:</strong> <?php echo $quick_stats['completed_trips']; ?> | 
               <strong>Active:</strong> <?php echo $quick_stats['active_trips']; ?> |
               <strong>Today:</strong> <?php echo $quick_stats['today_trips']; ?></p>
            <p><a href="trip_history.php" style="color: #007bff; text-decoration: none;">View Detailed Trip History & Analytics →</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        function performAction(action, tripId) {
            const btn = event.currentTarget || event.target;
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = '⏳...';
            
            if (!navigator.geolocation) {
                // No GPS, submit without coordinates
                submitAction(action, tripId, null, null, btn, originalText);
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    submitAction(action, tripId, lat, lng, btn, originalText);
                },
                function(error) {
                    // GPS failed, submit without coordinates
                    submitAction(action, tripId, null, null, btn, originalText);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 30000,
                    maximumAge: 0
                }
            );
        }
        
        function submitAction(action, tripId, lat, lng, btn, originalText) {
            const formData = new FormData();
            formData.append('trip_id', tripId);
            formData.append('action', action);
            
            if (lat !== null && lng !== null && lat !== 0 && lng !== 0) {
                if (action === 'arrived') {
                    formData.append('pickup_lat', lat);
                    formData.append('pickup_lng', lng);
                } else if (action === 'dropped_off') {
                    formData.append('dropoff_lat', lat);
                    formData.append('dropoff_lng', lng);
                }
            }
            
            fetch('update_trip.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            })
            .catch(e => {
                btn.disabled = false;
                btn.textContent = originalText;
            });
        }
    </script>
</body>
</html>