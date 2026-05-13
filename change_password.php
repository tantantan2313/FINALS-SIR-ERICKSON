<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $conn->prepare("SELECT password, email, full_name FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            $error = 'Unable to find your account.';
        } else {
            $user = $result->fetch_assoc();
            if (!verifyPassword($current_password, $user['password'])) {
                $error = 'Current password is incorrect.';
            } else {
                $hashed_password = hashPassword($new_password);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param('si', $hashed_password, $user_id);
                $stmt->execute();

                require_once 'send_email.php';
                $message = '<p>Hello ' . htmlspecialchars($user['full_name']) . ',</p>';
                $message .= '<p>Your password has been changed successfully.</p>';
                $message .= '<p>If you did not make this change, please contact support immediately.</p>';
                sendEmail($user['email'], 'Password Changed', $message);

                $success = 'Your password has been updated successfully.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - School Transport System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Change Password</h1>
        <div class="login-form">
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="current_password">Current Password:</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit">Update Password</button>
            </form>
            <p><a href="index.php">Back to login</a></p>
            <p><a href="dashboard.php">Back to dashboard</a></p>
        </div>
    </div>
</body>
</html>
