<?php
// admin/manage_students.php
// Admin interface for managing student records, including deleting students safely
// by removing related payment and trip records first.
require_once '../config.php';
requireRole('admin');

// Handle student actions
$message = '';
if (isset($_POST['delete_student'])) {
    $student_id = (int)$_POST['student_id'];
    
    // Delete in correct order due to foreign key constraints
    // First delete payments
    $conn->query("DELETE FROM payments WHERE student_id = $student_id");
    // Then delete trips
    $conn->query("DELETE FROM trips WHERE student_id = $student_id");
    // Finally delete student
    $conn->query("DELETE FROM students WHERE id = $student_id");
    
    $message = 'Student deleted successfully';
}

// Get all students with parent and driver info
$students = $conn->query("
    SELECT s.*, p.full_name as parent_name, d.full_name as driver_name
    FROM students s
    LEFT JOIN users p ON s.parent_id = p.id
    LEFT JOIN users d ON s.driver_id = d.id
    ORDER BY s.name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Admin</title>
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

        <h1>Manage Students</h1>

        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>

        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Grade</th>
                    <th>Parent</th>
                    <th>Driver</th>
                    <th>Address</th>
                    <th>Schedule</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($student = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $student['name']; ?></td>
                        <td><?php echo $student['grade']; ?></td>
                        <td><?php echo $student['parent_name'] ?: 'N/A'; ?></td>
                        <td><?php echo $student['driver_name'] ?: 'Not assigned'; ?></td>
                        <td><?php echo substr($student['address'], 0, 50) . (strlen($student['address']) > 50 ? '...' : ''); ?></td>
                        <td><?php echo substr($student['schedule'], 0, 30) . (strlen($student['schedule']) > 30 ? '...' : ''); ?></td>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this student?')">
                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                <button type="submit" name="delete_student" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>