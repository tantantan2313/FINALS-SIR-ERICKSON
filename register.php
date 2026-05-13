<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $role = sanitize($_POST['role']);

    // Check if username or email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $error = 'Username or email already exists';
    } else {
        // Insert user
        $hashed_password = hashPassword($password);
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, full_name, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $username, $hashed_password, $email, $role, $full_name, $phone);

        if ($stmt->execute()) {
            $user_id = $conn->insert_id;

            if ($role == 'driver') {
                // Insert into drivers table
                $license = sanitize($_POST['license']);
                $vehicle_type = sanitize($_POST['vehicle_type']);
                $vehicle_plate = sanitize($_POST['vehicle_plate']);

                $stmt = $conn->prepare("INSERT INTO drivers (user_id, license_number, vehicle_type, vehicle_plate) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $user_id, $license, $vehicle_type, $vehicle_plate);
                $stmt->execute();
            }

            $success = 'Registration successful! Please wait for admin approval.';
        } else {
            $error = 'Registration failed';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - School Transport System</title>
    <link rel="stylesheet" href="css/style.css">
    <script>
        function toggleDriverFields() {
            const role = document.getElementById('role').value;
            const driverFields = document.getElementById('driver-fields');
            driverFields.style.display = role === 'driver' ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>School Service Transportation System</h1>
        <div class="register-form">
            <h2>Register</h2>
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role" onchange="toggleDriverFields()" required>
                        <option value="parent">Parent</option>
                        <option value="driver">Driver</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="full_name">Full Name:</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="text" id="phone" name="phone">
                </div>
                <div id="driver-fields" style="display: none;">
                    <div class="form-group">
                        <label for="license">License Number:</label>
                        <input type="text" id="license" name="license">
                    </div>
                    <div class="form-group">
                        <label for="vehicle_type">Vehicle Type:</label>
                        <input type="text" id="vehicle_type" name="vehicle_type">
                    </div>
                    <div class="form-group">
                        <label for="vehicle_plate">Vehicle Plate:</label>
                        <input type="text" id="vehicle_plate" name="vehicle_plate">
                    </div>
                </div>
                <button type="submit">Register</button>
            </form>
            <p>Already have an account? <a href="index.php">Login</a></p>
        </div>
    </div>
</body>
</html>