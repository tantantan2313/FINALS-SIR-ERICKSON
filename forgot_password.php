<?php
// Include configuration file for database connection and helper functions
require_once 'config.php';
// Include email sending function
require_once 'send_email.php';

// Initialize error and success message variables
$error = '';
$success = '';

/**
 * Ensure password reset columns exist in the users table
 * Creates reset_token and reset_expires columns if they don't exist
 */
function ensurePasswordResetColumns($conn) {
    // Check if reset_token column exists
    $tokenColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
    // Check if reset_expires column exists
    $expiresColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_expires'");

    // Add reset_token column if it doesn't exist
    if ($tokenColumn->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL");
    }
    // Add reset_expires column if it doesn't exist
    if ($expiresColumn->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL");
    }
}

// Run the column creation function
ensurePasswordResetColumns($conn);

// Check if form was submitted via POST method
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize the email input from the form
    $email = sanitize($_POST['email']);

    // Prepare SQL query to find user by email
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
    // Bind the email parameter to the query
    $stmt->bind_param('s', $email);
    // Execute the prepared statement
    $stmt->execute();
    // Get the result set
    $result = $stmt->get_result();

    // Check if exactly one user was found with this email
    if ($result->num_rows === 1) {
        // Fetch the user data from the result
        $user = $result->fetch_assoc();
        
        // Generate a secure random token for password reset (32 hex characters = 16 bytes)
        $token = bin2hex(random_bytes(16));
        // Set token expiration to 1 hour from now
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Update the user record with the reset token and expiration time
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        // Bind the token, expiration time, and user ID to the update query
        $stmt->bind_param('ssi', $token, $expires, $user['id']);
        // Execute the update
        $stmt->execute();

        // Construct the password reset URL
        // Determine if the connection is HTTPS or HTTP
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        // Get the base path of the application (remove the current file from the path)
        $baseUrl = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
        // Build the complete reset URL with the token
        $resetUrl = sprintf('%s://%s%s/reset_password.php?token=%s', $protocol, $_SERVER['HTTP_HOST'], $baseUrl, urlencode($token));

        // Build the HTML email message body
        $message = '<p>Hello ' . htmlspecialchars($user['full_name']) . ',</p>';
        $message .= '<p>We received a request to reset your password. Click the link below to choose a new password:</p>';
        $message .= '<p><a href="' . $resetUrl . '" target="_blank">Reset your password</a></p>';
        $message .= '<p>This link will expire in 1 hour.</p>';
        $message .= '<p>If you did not request this, please ignore this message.</p>';

        // Send the password reset email
        if (sendEmail($email, 'Password Reset Request', $message)) {
            // Set success message if email was sent successfully
            $success = 'If that email exists in our system, a reset link has been sent.';
        } else {
            // Set error message if email sending failed
            $error = 'Unable to send the reset email right now. Please try again later.';
        }
    } else {
        // Set success message even if email not found (security best practice - don't reveal if email exists)
        $success = 'If that email exists in our system, a reset link has been sent.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - School Transport System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Forgot Password</h1>
        <div class="login-form">
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Address:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit">Send Reset Link</button>
            </form>
            <p><a href="index.php">Back to login</a></p>
        </div>
    </div>
</body>
</html>
