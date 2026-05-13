<?php
require_once '../config.php';
requireRole('driver');

$user_id = $_SESSION['user_id'];
$message = '';

function isImageUploadValid($file) {
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $mime = mime_content_type($file['tmp_name']);
    return $mime && strpos($mime, 'image/') === 0;
}

function getImageExtension($file) {
    $originalName = $file['name'] ?? '';
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if ($ext) {
        return $ext === 'jpeg' ? 'jpg' : $ext;
    }

    $mime = mime_content_type($file['tmp_name']);
    if (!$mime || strpos($mime, 'image/') !== 0) {
        return '';
    }

    $ext = substr($mime, strlen('image/'));
    if ($ext === 'jpeg' || $ext === 'pjpeg') {
        return 'jpg';
    }
    return $ext === 'svg+xml' ? 'svg' : $ext;
}

function uploadDriverImage($file, $currentFilename, $prefix) {
    global $user_id;

    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return $currentFilename;
    }

    if (!isImageUploadValid($file)) {
        return false;
    }

    $ext = getImageExtension($file);
    if (!$ext) {
        return false;
    }

    $new_filename = $prefix . '_' . $user_id . '_' . time() . '.' . $ext;
    $upload_dir = __DIR__ . '/../uploads/';
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
        return false;
    }

    $upload_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        if ($currentFilename && file_exists($upload_dir . $currentFilename)) {
            unlink($upload_dir . $currentFilename);
        }
        return $new_filename;
    }

    return false;
}

// Ensure QR code column exists for drivers
$columnCheck = $conn->query("SHOW COLUMNS FROM drivers LIKE 'qrcode_image'");
if ($columnCheck->num_rows == 0) {
    $conn->query("ALTER TABLE drivers ADD COLUMN qrcode_image VARCHAR(255) NULL");
}

// Get current driver and user info
$driver_info = $conn->query("SELECT * FROM drivers WHERE user_id = $user_id")->fetch_assoc();
$user_info = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle profile update
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $license_number = sanitize($_POST['license_number']);
    $vehicle_type = sanitize($_POST['vehicle_type']);
    $vehicle_plate = sanitize($_POST['vehicle_plate']);
    $bio = sanitize($_POST['bio']);

    // Handle profile picture upload
    $profile_picture = $driver_info['profile_picture'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $uploaded_profile_picture = uploadDriverImage($_FILES['profile_picture'], $profile_picture, 'driver');
        if ($uploaded_profile_picture === false) {
            $message = 'Error uploading profile picture. Please upload a valid image file.';
        } else {
            $profile_picture = $uploaded_profile_picture;
        }
    }

    // Handle GCash QR code upload
    $qrcode_image = $driver_info['qrcode_image'];
    if (isset($_FILES['qrcode_image']) && $_FILES['qrcode_image']['error'] == 0) {
        $uploaded_qrcode_image = uploadDriverImage($_FILES['qrcode_image'], $qrcode_image, 'driver_qr');
        if ($uploaded_qrcode_image === false) {
            $message = 'Error uploading QR code image. Please upload a valid image file.';
        } else {
            $qrcode_image = $uploaded_qrcode_image;
        }
    }

    if (!$message) {
        // Update user info
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, email = ? WHERE id = ?");
        $stmt->bind_param("sssi", $full_name, $phone, $email, $user_id);
        $stmt->execute();

        // Update driver info
        $stmt = $conn->prepare("UPDATE drivers SET license_number = ?, vehicle_type = ?, vehicle_plate = ?, profile_picture = ?, bio = ?, qrcode_image = ? WHERE user_id = ?");
        $stmt->bind_param("ssssssi", $license_number, $vehicle_type, $vehicle_plate, $profile_picture, $bio, $qrcode_image, $user_id);
        $stmt->execute();

        $message = 'Profile updated successfully!';

        // Refresh info after update so the page reflects the new files immediately
        $driver_info = $conn->query("SELECT * FROM drivers WHERE user_id = $user_id")->fetch_assoc();
        $user_info = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Driver</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="nav">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_trips.php">Manage Trips</a></li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="trip_history.php">Trip History</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>

        <h1>My Profile</h1>

        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="profile-container">
            <div class="profile-picture-section">
                <h3>Profile Picture</h3>
                <div class="current-picture">
                    <?php if ($driver_info['profile_picture'] && file_exists('../uploads/' . $driver_info['profile_picture'])): ?>
                        <img src="../uploads/<?php echo $driver_info['profile_picture']; ?>" alt="Profile Picture" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <div class="default-avatar" style="width: 150px; height: 150px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #666;">
                            👤
                        </div>
                    <?php endif; ?>
                </div>

                <div class="qrcode-preview">
                    <h3>GCash QR Code</h3>
                    <div class="current-qrcode">
                        <?php if ($driver_info['qrcode_image'] && file_exists('../uploads/' . $driver_info['qrcode_image'])): ?>
                            <img src="../uploads/<?php echo $driver_info['qrcode_image']; ?>" alt="GCash QR Code" style="width: 150px; height: 150px; object-fit: contain; border: 1px solid #ccc; background: white;">
                        <?php else: ?>
                            <div style="width: 150px; height: 150px; display: flex; align-items: center; justify-content: center; background: #f0f0f0; border: 1px solid #ddd; color: #555; font-size: 14px;">
                                No QR code uploaded
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" class="profile-form">
                <h3>Personal Information</h3>

                <div class="form-group">
                    <label for="full_name">Full Name:</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_info['full_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_info['phone']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                </div>

                <h3>Driver Information</h3>

                <div class="form-group">
                    <label for="license_number">License Number:</label>
                    <input type="text" id="license_number" name="license_number" value="<?php echo htmlspecialchars($driver_info['license_number']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="vehicle_type">Vehicle Type:</label>
                    <input type="text" id="vehicle_type" name="vehicle_type" value="<?php echo htmlspecialchars($driver_info['vehicle_type']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="vehicle_plate">Vehicle Plate:</label>
                    <input type="text" id="vehicle_plate" name="vehicle_plate" value="<?php echo htmlspecialchars($driver_info['vehicle_plate']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="bio">Bio/About Me:</label>
                    <textarea id="bio" name="bio" rows="4" placeholder="Tell parents about yourself, your experience, and what makes you a good driver..."><?php echo htmlspecialchars($driver_info['bio']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="profile_picture">Change Profile Picture:</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                    <small>Allowed formats: any image type. Max size: 5MB</small>
                </div>

                <div class="form-group">
                    <label for="qrcode_image">Upload GCash QR Code:</label>
                    <input type="file" id="qrcode_image" name="qrcode_image" accept="image/*">
                    <small>Upload the GCash QR code image that parents can scan to pay you. Any image format is accepted.</small>
                </div>

                <button type="submit">Update Profile</button>
            </form>
        </div>
    </div>

    <style>
        .profile-container {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        .profile-picture-section {
            flex: 0 0 200px;
            text-align: center;
        }

        .profile-form {
            flex: 1;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .profile-form h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
        }

        .current-picture {
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .profile-container {
                flex-direction: column;
            }

            .profile-picture-section {
                flex: none;
            }
        }
    </style>
</body>
</html>