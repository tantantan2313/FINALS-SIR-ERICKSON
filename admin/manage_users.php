<?php
require_once '../config.php';
requireRole('admin');

// Handle user actions
$message = '';
$error_message = '';
if (isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    // Don't allow deleting admin or self
    if ($user_id != $_SESSION['user_id']) {
        try {
            // Get user role using prepared statement
            $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param('i', $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            $role = $user_data ? $user_data['role'] : null;
            $stmt->close();

            if ($role === 'parent') {
                // Get student IDs
                $stmt = $conn->prepare("SELECT id FROM students WHERE parent_id = ?");
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $student_ids = [];
                while ($student = $result->fetch_assoc()) {
                    $student_ids[] = $student['id'];
                }
                $stmt->close();

                if (!empty($student_ids)) {
                    // Delete related payments and trips
                    foreach ($student_ids as $sid) {
                        if (!$conn->query("DELETE FROM payments WHERE student_id = $sid")) {
                            throw new Exception("Delete payments failed: " . $conn->error);
                        }
                        if (!$conn->query("DELETE FROM trips WHERE student_id = $sid")) {
                            throw new Exception("Delete trips failed: " . $conn->error);
                        }
                    }
                }

                if (!$conn->query("DELETE FROM payments WHERE parent_id = $user_id")) {
                    throw new Exception("Delete parent payments failed: " . $conn->error);
                }
                if (!$conn->query("DELETE FROM students WHERE parent_id = $user_id")) {
                    throw new Exception("Delete students failed: " . $conn->error);
                }
            } elseif ($role === 'driver') {
                if (!$conn->query("UPDATE students SET driver_id = NULL WHERE driver_id = $user_id")) {
                    throw new Exception("Update students failed: " . $conn->error);
                }
                if (!$conn->query("UPDATE trips SET driver_id = NULL WHERE driver_id = $user_id")) {
                    throw new Exception("Update trips failed: " . $conn->error);
                }
                if (!$conn->query("DELETE FROM drivers WHERE user_id = $user_id")) {
                    throw new Exception("Delete driver failed: " . $conn->error);
                }
            }

            // Delete notifications and logins (optional tables)
            if (!$conn->query("DELETE FROM notifications WHERE user_id = $user_id")) {
                // Log but don't fail if notifications table has issues
                error_log("Delete notifications warning: " . $conn->error);
            }
            
            // Check if user_logins table exists before trying to delete
            $table_check = $conn->query("SHOW TABLES LIKE 'user_logins'");
            if ($table_check && $table_check->num_rows > 0) {
                if (!$conn->query("DELETE FROM user_logins WHERE user_id = $user_id")) {
                    error_log("Delete user_logins warning: " . $conn->error);
                }
            }
            
            // Delete the user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            if (!$stmt) {
                throw new Exception("Delete prepare failed: " . $conn->error);
            }
            $stmt->bind_param('i', $user_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $message = 'User deleted successfully';
                } else {
                    $error_message = 'User not found or is an admin account.';
                }
            } else {
                throw new Exception("Delete failed: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Delete user error: " . $e->getMessage());
            $error_message = 'Error deleting user: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        $error_message = 'Cannot delete your own account';
    }
}

// Get all users except admin
$users = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY role, full_name");

// Check if query was successful
if (!$users) {
    error_log("Database query failed: " . $conn->error);
    $error_message = "Error retrieving users. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
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

        <h1>Manage Users</h1>

        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($users && $users->num_rows > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo ucfirst($user['role']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_user" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>No users found.</p>
        <?php endif; ?>
    </div>
</body>
</html>