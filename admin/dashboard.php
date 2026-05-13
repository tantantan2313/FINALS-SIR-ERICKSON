<?php
require_once '../config.php';

// Require admin access for this page.
// The requireRole helper checks the current session role and redirects
// unauthorized users to the login page or admin login page.
requireRole('admin');

// Store the current admin user's ID for future use or logging.
$user_id = $_SESSION['user_id'];

// Initialize dashboard statistics array.
$stats = [];

// Total users count, excluding admin accounts.
$result = $conn->query("SELECT COUNT(*) as total_users FROM users WHERE role != 'admin'");
$stats['total_users'] = $result->fetch_assoc()['total_users'];

// Total students count.
$result = $conn->query("SELECT COUNT(*) as total_students FROM students");
$stats['total_students'] = $result->fetch_assoc()['total_students'];

// Count of trips that are currently active (picked up or in transit).
$result = $conn->query("SELECT COUNT(*) as active_trips FROM trips WHERE status IN ('picked_up', 'in_transit')");
$stats['active_trips'] = $result->fetch_assoc()['active_trips'];

// Count of drivers waiting for approval.
$result = $conn->query("SELECT COUNT(*) as pending_drivers FROM drivers WHERE status = 'pending'");
$stats['pending_drivers'] = $result->fetch_assoc()['pending_drivers'];

// Retrieve the most recent trips for the activity table.
$recent_trips = $conn->query("
    SELECT t.id, t.status, t.student_id,
           s.name as student_name,
           d.full_name as driver_name,
           t.created_at
    FROM trips t
    JOIN students s ON t.student_id = s.id
    JOIN users d ON t.driver_id = d.id
    ORDER BY t.created_at DESC
    LIMIT 15
");

// Retrieve the most recent payments for the activity table.
$recent_payments = $conn->query("
    SELECT p.id, p.status, p.amount, p.created_at,
           u.full_name as parent_name
    FROM payments p
    JOIN users u ON p.parent_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 10
");

// Retrieve recent user registrations for dashboard review.
$recent_users = $conn->query("
    SELECT id, full_name, email, role, created_at
    FROM users
    WHERE role != 'admin'
    ORDER BY created_at DESC
    LIMIT 10
");

// Retrieve driver approval requests and rejected applications.
$pending_drivers = $conn->query("
    SELECT d.id, d.status, u.full_name, u.email, u.created_at
    FROM drivers d
    JOIN users u ON d.user_id = u.id
    WHERE d.status IN ('pending', 'rejected')
    ORDER BY u.created_at DESC
    LIMIT 10
");

// Retrieve reported trip issues for administrative action.
$reported_issues = $conn->query("
    SELECT t.id, t.notes, s.name as student_name, d.full_name as driver_name, t.created_at
    FROM trips t
    JOIN students s ON t.student_id = s.id
    JOIN users d ON t.driver_id = d.id
    WHERE t.status = 'issue_reported'
    ORDER BY t.created_at DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - School Transport System</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Base Styles */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f2f5; color: #333; margin: 0; }
        .dashboard { padding: 30px; max-width: 1300px; margin: auto; }
        
        /* Navigation */
        .nav { background: #fff; padding: 10px 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .nav ul { list-style: none; padding: 0; display: flex; gap: 20px; margin: 0; }
        .nav a { text-decoration: none; color: #1a73e8; font-weight: 600; font-size: 14px; }
        .nav a:hover { text-decoration: underline; }

        /* Typography */
        h1 { color: #1a1a1a; margin-bottom: 25px; }
        h2 { color: #444; border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-top: 40px; }
        h3 { color: #2c3e50; font-size: 18px; margin-top: 0; display: flex; align-items: center; justify-content: space-between; }
        .subtitle { color: #666; font-size: 12px; font-weight: normal; }

        /* Stats Cards */
        .stats { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-card { 
            background: #2a2f45; 
            padding: 20px; 
            border-radius: 10px; 
            text-align: center; 
            flex: 1; 
            min-width: 200px; 
            border-bottom: 4px solid #00bcd4; 
            color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stat-card h3 { color: #00bcd4; justify-content: center; margin-bottom: 10px; font-size: 14px; text-transform: uppercase; }
        .stat-card p { font-size: 32px; font-weight: bold; margin: 0; }

        /* Layout Grid */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        @media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } }

        /* Table Styling */
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; background: white; }
        .table th { text-align: left; padding: 12px; border-bottom: 2px solid #edf2f7; color: #718096; font-size: 13px; text-transform: uppercase; }
        .table td { padding: 12px; border-bottom: 1px solid #edf2f7; color: #2d3748; font-size: 14px; }
        .table tr:hover { background-color: #f8fafc; }

        /* Status Badges */
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .status-paid { background: #c6f6d5; color: #22543d; }
        .status-pending { background: #feebc8; color: #744210; }
        .status-issue { background: #fed7d7; color: #822727; }
        
        /* Success Message */
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 5px solid #28a745; }
    </style>
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
                <li><a href="../logout.php" style="color: #e53e3e;">Logout</a></li>
            </ul>
        </div>

        <h1>Admin Dashboard</h1>

        <?php if (isset($_GET['message'])): ?>
            <div class="success"><?php echo htmlspecialchars($_GET['message']); ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <h3>Total Users</h3>
                <p><?php echo $stats['total_users']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Students</h3>
                <p><?php echo $stats['total_students']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Active Trips</h3>
                <p><?php echo $stats['active_trips']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Drivers</h3>
                <p><?php echo $stats['pending_drivers']; ?></p>
            </div>
        </div>

        <h2>Activity Overview</h2>

        <div class="grid-2">
            <!-- Recent Trips -->
            <div class="card">
                <h3>Recent Trips <span class="subtitle">(Last 15)</span></h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Driver</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($trip = $recent_trips->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $trip['student_name']; ?></strong></td>
                                <td><?php echo $trip['driver_name']; ?></td>
                                <td><span class="badge status-pending"><?php echo ucfirst(str_replace('_', ' ', $trip['status'])); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Payments -->
            <div class="card">
                <h3>Recent Payments <span class="subtitle">(Last 10)</span></h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Parent</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($payment = $recent_payments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $payment['parent_name']; ?></td>
                                <td style="font-weight: bold; color: #2d3748;">$<?php echo number_format($payment['amount'], 2); ?></td>
                                <td>
                                    <span class="badge <?php echo $payment['status'] == 'paid' ? 'status-paid' : 'status-pending'; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid-2">
            <!-- New Users -->
            <div class="card">
                <h3>New Registrations <span class="subtitle">(Last 10)</span></h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $recent_users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['full_name']; ?></td>
                                <td><span class="badge" style="background:#edf2f7;"><?php echo ucfirst($user['role']); ?></span></td>
                                <td style="font-size: 11px; color: #718096;"><?php echo date('M d', strtotime($user['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pending Drivers -->
            <div class="card">
                <h3>Driver Approvals <span class="subtitle">(Action Required)</span></h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Applied</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pending_drivers->num_rows > 0): ?>
                            <?php while ($driver = $pending_drivers->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $driver['full_name']; ?></td>
                                    <td><span class="badge status-pending"><?php echo ucfirst($driver['status']); ?></span></td>
                                    <td style="font-size: 11px;"><?php echo date('M d', strtotime($driver['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align: center; color: #a0aec0; padding: 20px;">All caught up!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reported Issues Section -->
        <div class="card" style="margin-top: 30px; border-left: 5px solid #e53e3e;">
            <h3 style="color: #e53e3e;">Trip Issues Reported</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Trip</th>
                        <th>Student</th>
                        <th>Driver</th>
                        <th>Issue</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reported_issues->num_rows > 0): ?>
                        <?php while ($issue = $reported_issues->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $issue['id']; ?></td>
                                <td><?php echo $issue['student_name']; ?></td>
                                <td><?php echo $issue['driver_name']; ?></td>
                                <td style="color: #e53e3e; font-style: italic;"><?php echo htmlspecialchars($issue['notes'] ?? 'No detail'); ?></td>
                                <td><?php echo date('M d, H:i', strtotime($issue['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; color: #a0aec0; padding: 20px;">No issues reported</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 40px; text-align: center; color: #718096; font-size: 12px; border-top: 1px solid #ddd; padding-top: 20px;">
            Last System Update: <?php echo date('F d, Y - H:i:s'); ?> | <a href="dashboard.php" style="color: #1a73e8;">Manual Refresh</a>
        </div>
    </div>
</body>
</html>