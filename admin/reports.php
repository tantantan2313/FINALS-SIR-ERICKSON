<?php
// admin/reports.php
// Admin reports page showing user counts, trip statistics, payment totals, and monthly summaries.
require_once '../config.php';
requireRole('admin');

// Get various statistics
$stats = [];

// User statistics
$user_stats = $conn->query("
    SELECT
        COUNT(CASE WHEN role = 'parent' THEN 1 END) as parents,
        COUNT(CASE WHEN role = 'driver' THEN 1 END) as drivers,
        COUNT(*) as total_users
    FROM users WHERE role != 'admin'
");
$stats['users'] = $user_stats->fetch_assoc();

// Student statistics
$student_stats = $conn->query("
    SELECT
        COUNT(*) as total_students,
        COUNT(CASE WHEN driver_id IS NOT NULL THEN 1 END) as assigned_students
    FROM students
");
$stats['students'] = $student_stats->fetch_assoc();

// Trip statistics
$trip_stats = $conn->query("
    SELECT
        COUNT(*) as total_trips,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_trips,
        COUNT(CASE WHEN DATE(trip_date) = CURDATE() THEN 1 END) as today_trips
    FROM trips
");
$stats['trips'] = $trip_stats->fetch_assoc();

// Payment statistics
$payment_stats = $conn->query("
    SELECT
        COUNT(*) as total_payments,
        SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_collected,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount
    FROM payments
");
$stats['payments'] = $payment_stats->fetch_assoc();

// Recent trips
$recent_trips = $conn->query("
    SELECT t.*, s.name as student_name, d.full_name as driver_name, p.full_name as parent_name
    FROM trips t
    JOIN students s ON t.student_id = s.id
    JOIN users d ON t.driver_id = d.id
    JOIN users p ON s.parent_id = p.id
    ORDER BY t.created_at DESC LIMIT 10
");

// Monthly trip summary
$monthly_trips = $conn->query("
    SELECT
        DATE_FORMAT(trip_date, '%Y-%m') as month,
        COUNT(*) as trip_count,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count
    FROM trips
    WHERE trip_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(trip_date, '%Y-%m')
    ORDER BY month DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="nav">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="manage_drivers.php">Manage Drivers</a></li>
                <li><a href="manage_students.php">Manage Students</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>

        <h1>System Reports</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>User Statistics</h3>
                <p>Parents: <?php echo $stats['users']['parents']; ?></p>
                <p>Drivers: <?php echo $stats['users']['drivers']; ?></p>
                <p>Total Users: <?php echo $stats['users']['total_users']; ?></p>
            </div>

            <div class="stat-card">
                <h3>Student Statistics</h3>
                <p>Total Students: <?php echo $stats['students']['total_students']; ?></p>
                <p>Assigned to Drivers: <?php echo $stats['students']['assigned_students']; ?></p>
                <p>Unassigned: <?php echo $stats['students']['total_students'] - $stats['students']['assigned_students']; ?></p>
            </div>

            <div class="stat-card">
                <h3>Trip Statistics</h3>
                <p>Total Trips: <?php echo $stats['trips']['total_trips']; ?></p>
                <p>Completed: <?php echo $stats['trips']['completed_trips']; ?></p>
                <p>Today's Trips: <?php echo $stats['trips']['today_trips']; ?></p>
            </div>

            <div class="stat-card">
                <h3>Payment Statistics</h3>
                <p>Total Payments: <?php echo $stats['payments']['total_payments']; ?></p>
                <p>Collected: $<?php echo number_format($stats['payments']['total_collected'], 2); ?></p>
                <p>Pending: $<?php echo number_format($stats['payments']['pending_amount'], 2); ?></p>
            </div>
        </div>

        <h2>Monthly Trip Summary</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Total Trips</th>
                    <th>Completed</th>
                    <th>Completion Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($month = $monthly_trips->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                        <td><?php echo $month['trip_count']; ?></td>
                        <td><?php echo $month['completed_count']; ?></td>
                        <td><?php echo $month['trip_count'] > 0 ? round(($month['completed_count'] / $month['trip_count']) * 100, 1) : 0; ?>%</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h2>Recent Trips</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student</th>
                    <th>Parent</th>
                    <th>Driver</th>
                    <th>Status</th>
                    <th>Pickup Time</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($trip = $recent_trips->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($trip['trip_date'])); ?></td>
                        <td><?php echo $trip['student_name']; ?></td>
                        <td><?php echo $trip['parent_name']; ?></td>
                        <td><?php echo $trip['driver_name']; ?></td>
                        <td><span class="status-<?php echo $trip['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $trip['status'])); ?></span></td>
                        <td><?php echo $trip['pickup_time']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #ddd;
        }
        .stat-card h3 {
            margin: 0 0 15px 0;
            color: #333;
        }
        .stat-card p {
            margin: 5px 0;
            color: #666;
        }
    </style>
</body>
</html>