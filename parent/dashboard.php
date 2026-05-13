<?php
require_once '../config.php';
requireRole('parent');

$user_id = $_SESSION['user_id'];

// Get parent's students
$students = $conn->query("SELECT * FROM students WHERE parent_id = $user_id");

// Get recent notifications
$notifications = $conn->query("SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5");

// Get active trips
$active_trips = $conn->query("
    SELECT t.*, s.name as student_name, u.full_name as driver_name
    FROM trips t
    JOIN students s ON t.student_id = s.id
    JOIN users u ON t.driver_id = u.id
    WHERE s.parent_id = $user_id AND t.status IN ('picked_up', 'in_transit')
    ORDER BY t.trip_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - School Transport System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="nav">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="add_student.php">Add Student</a></li>
                <li><a href="select_driver.php">Select Driver</a></li>
                <li><a href="trip_history.php">Trip History</a></li>
                <li><a href="payments.php">Payments</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>

        <h1>Parent Dashboard</h1>

        <?php
        // Get quick statistics
        $quick_stats = $conn->query("
            SELECT
                COUNT(*) as total_trips,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_trips,
                COUNT(CASE WHEN status IN ('picked_up', 'in_transit') THEN 1 END) as active_trips,
                COUNT(CASE WHEN trip_date = CURDATE() THEN 1 END) as today_trips
            FROM trips t
            JOIN students s ON t.student_id = s.id
            WHERE s.parent_id = $user_id
        ")->fetch_assoc();
        ?>

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

        <h2>My Students</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Grade</th>
                    <th>Driver</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($student = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $student['name']; ?></td>
                        <td><?php echo $student['grade']; ?></td>
                        <td>
                            <?php
                            if ($student['driver_id']) {
                                $driver = $conn->query("SELECT full_name FROM users WHERE id = {$student['driver_id']}")->fetch_assoc();
                                echo $driver['full_name'];
                            } else {
                                echo 'Not assigned';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $trip = $conn->query("SELECT status FROM trips WHERE student_id = {$student['id']} AND trip_date = CURDATE() ORDER BY id DESC LIMIT 1")->fetch_assoc();
                            if ($trip) {
                                echo '<span class="status-' . $trip['status'] . '">' . ucfirst(str_replace('_', ' ', $trip['status'])) . '</span>';
                            } else {
                                echo 'No trip today';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h2>Active Trips</h2>
        <?php if ($active_trips->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Driver</th>
                        <th>Status</th>
                        <th>Pickup Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($trip = $active_trips->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $trip['student_name']; ?></td>
                            <td><?php echo $trip['driver_name']; ?></td>
                            <td><span class="status-<?php echo $trip['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $trip['status'])); ?></span></td>
                            <td><?php echo $trip['pickup_time']; ?></td>
                            <td><a href="track_trip.php?trip_id=<?php echo $trip['id']; ?>">Track</a></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No active trips at the moment.</p>
        <?php endif; ?>

        <h2>Recent Notifications</h2>
        <ul>
            <?php while ($notif = $notifications->fetch_assoc()): ?>
                <li><?php echo $notif['message']; ?> <small>(<?php echo $notif['created_at']; ?>)</small></li>
            <?php endwhile; ?>
        </ul>
    </div>
</body>
</html>