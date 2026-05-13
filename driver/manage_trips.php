<?php
require_once '../config.php';
require_once '../includes/NotificationManager.php';
require_once '../includes/TripNotifications.php';

requireRole('driver');

$user_id = $_SESSION['user_id'];
$message = '';

// Initialize notification system
$notificationManager = new NotificationManager($conn);
$tripNotifications = new TripNotifications($conn, $notificationManager);

// Handle trip creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_trip'])) {
    $student_id = (int)$_POST['student_id'];
    $trip_date = sanitize($_POST['trip_date']);
    $pickup_time = sanitize($_POST['pickup_time']);
    
    // Coordinates are optional - will be captured when trip starts/ends
    $pickup_lat = !empty($_POST['pickup_lat']) ? (float)$_POST['pickup_lat'] : NULL;
    $pickup_lng = !empty($_POST['pickup_lng']) ? (float)$_POST['pickup_lng'] : NULL;
    $dropoff_lat = !empty($_POST['dropoff_lat']) ? (float)$_POST['dropoff_lat'] : NULL;
    $dropoff_lng = !empty($_POST['dropoff_lng']) ? (float)$_POST['dropoff_lng'] : NULL;

    // Verify student belongs to this driver
    $student_check = $conn->query("SELECT id FROM students WHERE id = $student_id AND driver_id = $user_id");
    if ($student_check->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO trips (driver_id, student_id, trip_date, pickup_time, pickup_lat, pickup_lng, dropoff_lat, dropoff_lng) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissdddd", $user_id, $student_id, $trip_date, $pickup_time, $pickup_lat, $pickup_lng, $dropoff_lat, $dropoff_lng);
        if ($stmt->execute()) {
            $trip_id = $stmt->insert_id;
            // Send notification to driver about new trip
            $tripNotifications->notifyDriverNewTrip($trip_id, $user_id);
            $message = 'Trip created successfully!';
        }
    } else {
        $message = 'Invalid student selection.';
    }
}

// Get assigned students
$students = $conn->query("SELECT * FROM students WHERE driver_id = $user_id");

// Get upcoming trips
$upcoming_trips = $conn->query("
    SELECT t.*, s.name as student_name, s.address
    FROM trips t
    JOIN students s ON t.student_id = s.id
    WHERE t.driver_id = $user_id AND t.trip_date >= CURDATE() AND t.status != 'completed'
    ORDER BY t.trip_date, t.pickup_time
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Trips - Driver</title>
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

        <h1>Manage Trips</h1>

        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>

        <h2>Create New Trip</h2>
        <form method="POST" class="trip-form">
            <div class="form-group">
                <label for="student_id">Select Student:</label>
                <select id="student_id" name="student_id" required>
                    <option value="">Choose a student</option>
                    <?php
                    $students->data_seek(0);
                    while ($student = $students->fetch_assoc()): ?>
                        <option value="<?php echo $student['id']; ?>"><?php echo $student['name']; ?> (<?php echo $student['address']; ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="trip_date">Trip Date:</label>
                <input type="date" id="trip_date" name="trip_date" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label for="pickup_time">Pickup Time:</label>
                <input type="time" id="pickup_time" name="pickup_time" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="pickup_lat">Pickup Latitude (Optional):</label>
                    <input type="number" step="0.000001" id="pickup_lat" name="pickup_lat" placeholder="Auto-captured when trip starts" readonly style="background: #f0f0f0;">
                </div>
                <div class="form-group">
                    <label for="pickup_lng">Pickup Longitude (Optional):</label>
                    <input type="number" step="0.000001" id="pickup_lng" name="pickup_lng" placeholder="Auto-captured when trip starts" readonly style="background: #f0f0f0;">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="dropoff_lat">Drop-off Latitude (Optional):</label>
                    <input type="number" step="0.000001" id="dropoff_lat" name="dropoff_lat" placeholder="Auto-captured when trip ends" readonly style="background: #f0f0f0;">
                </div>
                <div class="form-group">
                    <label for="dropoff_lng">Drop-off Longitude (Optional):</label>
                    <input type="number" step="0.000001" id="dropoff_lng" name="dropoff_lng" placeholder="Auto-captured when trip ends" readonly style="background: #f0f0f0;">
                </div>
            </div>

           
            <button type="submit" name="create_trip">Create Trip</button>
        </form>

        <h2>Upcoming Trips</h2>
        <?php if ($upcoming_trips->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Pickup Time</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($trip = $upcoming_trips->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($trip['trip_date'])); ?></td>
                            <td><?php echo $trip['student_name']; ?></td>
                            <td><?php echo $trip['pickup_time']; ?></td>
                            <td><?php echo substr($trip['address'], 0, 50) . (strlen($trip['address']) > 50 ? '...' : ''); ?></td>
                            <td><span class="status-<?php echo $trip['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $trip['status'])); ?></span></td>
                            <td>
                                <?php if ($trip['status'] == 'in_transit'): ?>
                                    <a href="dropoff_trip.php?trip_id=<?php echo $trip['id']; ?>" style="background: #dc3545; color: white; padding: 5px 10px; border: none; border-radius: 3px; text-decoration: none; display: inline-block;">Drop Off</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No upcoming trips scheduled.</p>
        <?php endif; ?>
    </div>

</body>
</html>