<?php
require_once '../config.php';
requireRole('driver');

$user_id = $_SESSION['user_id'];

// Get trip history
$trip_history = $conn->query("
    SELECT t.*, s.name as student_name, s.address, p.full_name as parent_name
    FROM trips t
    JOIN students s ON t.student_id = s.id
    JOIN users p ON s.parent_id = p.id
    WHERE t.driver_id = $user_id
    ORDER BY t.trip_date DESC, t.pickup_time DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip History - Driver</title>
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

        <h1>Trip History</h1>

        <?php if ($trip_history->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Parent</th>
                        <th>Pickup Time</th>
                        <th>Drop-off Time</th>
                        <th>Status</th>
                        <th>Photo Proof</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($trip = $trip_history->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($trip['trip_date'])); ?></td>
                            <td><?php echo $trip['student_name']; ?></td>
                            <td><?php echo $trip['parent_name']; ?></td>
                            <td><?php echo $trip['pickup_time']; ?></td>
                            <td><?php echo $trip['dropoff_time'] ?: 'N/A'; ?></td>
                            <td><span class="status-<?php echo $trip['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $trip['status'])); ?></span></td>
                            <td>
                                <?php if ($trip['photo_proof']): ?>
                                    <a href="../uploads/<?php echo $trip['photo_proof']; ?>" target="_blank">View Photo</a>
                                <?php else: ?>
                                    No photo
                                <?php endif; ?>
                            </td>
                            <td><?php echo $trip['notes'] ?: 'N/A'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No trip history available.</p>
        <?php endif; ?>

        <div class="trip-stats">
            <h2>Trip Statistics</h2>
            <?php
            $stats = $conn->query("
                SELECT
                    COUNT(*) as total_trips,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_trips,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_trips,
                    COUNT(CASE WHEN status IN ('picked_up', 'in_transit') THEN 1 END) as active_trips,
                    AVG(CASE WHEN status = 'completed' AND dropoff_time IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, pickup_time, dropoff_time) END) as avg_duration
                FROM trips
                WHERE driver_id = $user_id
            ")->fetch_assoc();
            ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Trips</h3>
                    <p><?php echo $stats['total_trips']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Completed Trips</h3>
                    <p><?php echo $stats['completed_trips']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Active Trips</h3>
                    <p><?php echo $stats['active_trips']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Cancelled Trips</h3>
                    <p><?php echo $stats['cancelled_trips']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Average Duration</h3>
                    <p><?php echo $stats['avg_duration'] ? round($stats['avg_duration']) . ' min' : 'N/A'; ?></p>
                </div>
            </div>
        </div>
    </div>

    <style>
        .trip-stats {
            margin-top: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        .stat-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #ddd;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 14px;
        }
        .stat-card p {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
        }
    </style>
</body>
</html>