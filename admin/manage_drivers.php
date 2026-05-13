<?php
// admin/manage_drivers.php
// Admin interface for reviewing and approving or rejecting driver applications.
// Shows all drivers and supports status updates from pending to approved or rejected.
require_once '../config.php';
requireRole('admin');

$message = '';

if (isset($_POST['approve'])) {
    $driver_id = (int)$_POST['driver_id'];
    $conn->query("UPDATE drivers SET status = 'approved' WHERE id = $driver_id");
    $message = 'Driver approved successfully';
}

if (isset($_POST['reject'])) {
    $driver_id = (int)$_POST['driver_id'];
    $conn->query("UPDATE drivers SET status = 'rejected' WHERE id = $driver_id");
    $message = 'Driver rejected';
}

$drivers = $conn->query("
    SELECT d.*, u.full_name, u.email, u.phone
    FROM drivers d
    JOIN users u ON d.user_id = u.id
    ORDER BY d.status, u.full_name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Drivers - Admin</title>
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

        <h1>Manage Drivers</h1>

        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>

        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>License</th>
                    <th>Vehicle</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($driver = $drivers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $driver['full_name']; ?></td>
                        <td><?php echo $driver['email']; ?></td>
                        <td><?php echo $driver['phone']; ?></td>
                        <td><?php echo $driver['license_number']; ?></td>
                        <td><?php echo $driver['vehicle_type'] . ' (' . $driver['vehicle_plate'] . ')'; ?></td>
                        <td><?php echo ucfirst($driver['status']); ?></td>
                        <td>
                            <?php if ($driver['status'] == 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="driver_id" value="<?php echo $driver['id']; ?>">
                                    <button type="submit" name="approve">Approve</button>
                                    <button type="submit" name="reject">Reject</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>