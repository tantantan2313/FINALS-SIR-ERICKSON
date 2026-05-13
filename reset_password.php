<?php
require_once 'config.php';
require_once 'send_email.php';

$error = '';
$success = '';

if (empty($_GET['token'])) {
    $error = 'Invalid or missing reset token.';
} else {
    $token = sanitize($_GET['token']);

    $stmt = $conn->prepare("SELECT id, email, full_name, reset_expires FROM users WHERE reset_token = ?");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        $error = 'Invalid or expired reset token.';
    } else {
        $user = $result->fetch_assoc();
        if (!$user['reset_expires'] || strtotime($user['reset_expires']) < time()) {
            $error = 'Reset token has expired. Please request a new password reset.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($error)) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $hashed_password = hashPassword($new_password);
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->bind_param('si', $hashed_password, $user['id']);
        $stmt->execute();

        $message = '<p>Hello ' . htmlspecialchars($user['full_name']) . ',</p>';
        $message .= '<p>Your password has been changed successfully.</p>';
        $message .= '<p>If you did not make this change, please contact support immediately.</p>';
        sendEmail($user['email'], 'Password Changed', $message);

        $success = 'Your password has been reset successfully. <a href="index.php">Login now</a>.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - School Transport System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Reset Password</h1>
        <div class="login-form">
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (!$success && empty($error)): ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
