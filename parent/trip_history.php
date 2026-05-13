<?php
require_once '../config.php';
requireRole('parent');

$user_id = $_SESSION['user_id'];

// Get trip history for parent's students
$trip_history = $conn->query("
    SELECT t.*, s.name as student_name, d.full_name as driver_name
    FROM trips t
    JOIN students s ON t.student_id = s.id
    JOIN users d ON t.driver_id = d.id
    WHERE s.parent_id = $user_id
    ORDER BY t.trip_date DESC, t.pickup_time DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip History - Parent</title>
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

        <h1>Trip History</h1>

        <?php if ($trip_history->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Driver</th>
                        <th>Pickup Time</th>
                        <th>Drop-off Time</th>
                        <th>Status</th>
                        <th>Photo Proof</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($trip = $trip_history->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($trip['trip_date'])); ?></td>
                            <td><?php echo $trip['student_name']; ?></td>
                            <td><?php echo $trip['driver_name']; ?></td>
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
                            <td>
                                <?php if ($trip['status'] == 'dropped_off'): ?>
                                    <button onclick="confirmTrip(<?php echo $trip['id']; ?>)" style="background: #28a745; color: white; padding: 5px 8px; border: none; border-radius: 3px; cursor: pointer; font-size: 12px; margin-right: 5px;">Confirm ✓</button>
                                    <button onclick="reportIssue(<?php echo $trip['id']; ?>)" style="background: #dc3545; color: white; padding: 5px 8px; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">Report Issue</button>
                                <?php elseif ($trip['status'] == 'issue_reported'): ?>
                                    <span style="color: #f57c00; font-weight: bold;">⚠ Pending Resolution</span>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
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
                    AVG(CASE WHEN status = 'completed' AND t.dropoff_time IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, t.pickup_time, t.dropoff_time) END) as avg_duration
                FROM trips t
                JOIN students s ON t.student_id = s.id
                WHERE s.parent_id = $user_id
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

        <div class="trip-stats">
            <h2>Completed Trips Analytics</h2>
            <?php
            // Get completed trips analytics
            $completed_analytics = $conn->query("
                SELECT
                    COUNT(*) as completed_count,
                    MIN(TIMESTAMPDIFF(MINUTE, t.pickup_time, t.dropoff_time)) as min_duration,
                    MAX(TIMESTAMPDIFF(MINUTE, t.pickup_time, t.dropoff_time)) as max_duration,
                    AVG(TIMESTAMPDIFF(MINUTE, t.pickup_time, t.dropoff_time)) as avg_duration,
                    COUNT(CASE WHEN t.photo_proof IS NOT NULL THEN 1 END) as photos_count
                FROM trips t
                JOIN students s ON t.student_id = s.id
                WHERE s.parent_id = $user_id AND t.status = 'completed'
            ")->fetch_assoc();

            // Get driver performance for completed trips
            $driver_performance = $conn->query("
                SELECT
                    d.full_name,
                    COUNT(*) as trip_count,
                    AVG(TIMESTAMPDIFF(MINUTE, t.pickup_time, t.dropoff_time)) as avg_duration
                FROM trips t
                JOIN students s ON t.student_id = s.id
                JOIN users d ON t.driver_id = d.id
                WHERE s.parent_id = $user_id AND t.status = 'completed'
                GROUP BY t.driver_id, d.full_name
                ORDER BY trip_count DESC
            ");

            // Get student-wise completed trips
            $student_completed = $conn->query("
                SELECT
                    s.name as student_name,
                    COUNT(*) as completed_trips,
                    COUNT(CASE WHEN t.photo_proof IS NOT NULL THEN 1 END) as with_photos
                FROM trips t
                JOIN students s ON t.student_id = s.id
                WHERE s.parent_id = $user_id AND t.status = 'completed'
                GROUP BY s.id, s.name
                ORDER BY completed_trips DESC
            ");
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Completed Trips</h3>
                    <p><?php echo $completed_analytics['completed_count']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Avg Duration</h3>
                    <p><?php echo $completed_analytics['avg_duration'] ? round($completed_analytics['avg_duration']) . ' min' : 'N/A'; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Shortest Trip</h3>
                    <p><?php echo $completed_analytics['min_duration'] ? $completed_analytics['min_duration'] . ' min' : 'N/A'; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Longest Trip</h3>
                    <p><?php echo $completed_analytics['max_duration'] ? $completed_analytics['max_duration'] . ' min' : 'N/A'; ?></p>
                </div>
                <div class="stat-card">
                    <h3>With Photos</h3>
                    <p><?php echo $completed_analytics['photos_count']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Photo Coverage</h3>
                    <p><?php echo $completed_analytics['completed_count'] > 0 ? round(($completed_analytics['photos_count'] / $completed_analytics['completed_count']) * 100) . '%' : 'N/A'; ?></p>
                </div>
            </div>

            <h3 style="margin-top: 25px; margin-bottom: 15px;">Driver Performance (Completed Trips)</h3>
            <?php if ($driver_performance->num_rows > 0): ?>
                <table class="table" style="margin-bottom: 30px;">
                    <thead>
                        <tr>
                            <th>Driver Name</th>
                            <th>Completed Trips</th>
                            <th>Average Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($driver = $driver_performance->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $driver['full_name']; ?></td>
                                <td><?php echo $driver['trip_count']; ?></td>
                                <td><?php echo round($driver['avg_duration']) . ' min'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No completed trips data available.</p>
            <?php endif; ?>

            <h3 style="margin-bottom: 15px;">Completed Trips by Student</h3>
            <?php if ($student_completed->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Completed Trips</th>
                            <th>With Photo Proof</th>
                            <th>Coverage Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($student = $student_completed->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $student['student_name']; ?></td>
                                <td><?php echo $student['completed_trips']; ?></td>
                                <td><?php echo $student['with_photos']; ?></td>
                                <td><?php echo round(($student['with_photos'] / $student['completed_trips']) * 100) . '%'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No student data available.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmTrip(tripId) {
            if (confirm('Confirm that your child has arrived safely?')) {
                fetch('confirm_trip.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'trip_id=' + tripId + '&action=confirm_arrival'
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Trip marked as completed');
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(e => alert('Error: ' + e));
            }
        }

        function reportIssue(tripId) {
            const issue = prompt('Please describe the issue with this trip:');
            if (issue !== null && issue.trim() !== '') {
                fetch('confirm_trip.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'trip_id=' + tripId + '&action=mark_issue&issue_notes=' + encodeURIComponent(issue)
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(e => alert('Error: ' + e));
            }
        }
    </script>

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