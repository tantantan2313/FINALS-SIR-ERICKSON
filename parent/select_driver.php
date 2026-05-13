<?php
require_once '../config.php';
requireRole('parent');

$user_id = $_SESSION['user_id'];
$message = '';

if (isset($_POST['select_driver'])) {
    $student_id = (int)$_POST['student_id'];
    $driver_id = (int)$_POST['driver_id'];

    // Check if student belongs to parent
    $student_check = $conn->query("SELECT id FROM students WHERE id = $student_id AND parent_id = $user_id");
    if ($student_check->num_rows > 0) {
        $conn->query("UPDATE students SET driver_id = $driver_id WHERE id = $student_id");
        $message = 'Driver selected successfully!';
    } else {
        $message = 'Invalid student.';
    }
}

// Get parent's students
$students = $conn->query("SELECT * FROM students WHERE parent_id = $user_id");

// Get approved drivers
$drivers = $conn->query("
    SELECT d.*, u.full_name, u.phone
    FROM drivers d
    JOIN users u ON d.user_id = u.id
    WHERE d.status = 'approved'
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Driver - Parent</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="nav">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="add_student.php">Add Student</a></li>
                <li><a href="select_driver.php">Select Driver</a></li>
                <li><a href="trip_history.php">Trip History</a></li>
                <li><a href="payments.php">Payments</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>

        <h1>Select Driver for Students</h1>

        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>

        <h2>Available Drivers</h2>
        <div class="drivers-grid">
            <?php
            $drivers->data_seek(0);
            while ($driver = $drivers->fetch_assoc()):
            ?>
                <div class="driver-card">
                    <div class="driver-header">
                        <div class="driver-picture">
                            <?php if ($driver['profile_picture'] && file_exists('../uploads/' . $driver['profile_picture'])): ?>
                                <img src="../uploads/<?php echo $driver['profile_picture']; ?>" alt="Profile Picture">
                            <?php else: ?>
                                <div class="default-avatar">👤</div>
                            <?php endif; ?>
                        </div>
                        <div class="driver-info">
                            <h3><?php echo htmlspecialchars($driver['full_name']); ?></h3>
                            <div class="rating">
                                <?php
                                $rating = $driver['rating'] ?? 0;
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '⭐' : '☆';
                                }
                                echo " ($rating/5)";
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="driver-details">
                        <div class="detail-row">
                            <span class="label">Phone:</span>
                            <span class="value"><?php echo htmlspecialchars($driver['phone']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Vehicle:</span>
                            <span class="value"><?php echo htmlspecialchars($driver['vehicle_type'] . ' (' . $driver['vehicle_plate'] . ')'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">License:</span>
                            <span class="value"><?php echo htmlspecialchars($driver['license_number']); ?></span>
                        </div>
                        <?php if ($driver['bio']): ?>
                            <div class="bio-section">
                                <span class="label">About:</span>
                                <p class="bio"><?php echo htmlspecialchars($driver['bio']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <h2>Assign Driver to Student</h2>
        <form method="POST">
            <div class="form-group">
                <label for="student_id">Select Student:</label>
                <select id="student_id" name="student_id" required>
                    <option value="">Choose a student</option>
                    <?php
                    $students->data_seek(0); // Reset pointer
                    while ($student = $students->fetch_assoc()): ?>
                        <option value="<?php echo $student['id']; ?>"><?php echo $student['name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="driver_id">Select Driver:</label>
                <select id="driver_id" name="driver_id" required>
                    <option value="">Choose a driver</option>
                    <?php
                    $drivers->data_seek(0); // Reset pointer
                    while ($driver = $drivers->fetch_assoc()): ?>
                        <option value="<?php echo $driver['user_id']; ?>"><?php echo $driver['full_name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" name="select_driver">Assign Driver</button>
        </form>
    </div>

    <style>
        .drivers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .driver-card {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .driver-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .driver-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .driver-picture {
            margin-right: 15px;
        }

        .driver-picture img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #007bff;
        }

        .default-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #666;
            border: 2px solid #007bff;
        }

        .driver-info h3 {
            margin: 0 0 5px 0;
            color: #333;
        }

        .rating {
            color: #ffa500;
            font-size: 14px;
        }

        .driver-details {
            space-y: 10px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 8px;
        }

        .detail-row .label {
            font-weight: bold;
            color: #555;
            min-width: 80px;
            flex-shrink: 0;
        }

        .detail-row .value {
            color: #333;
        }

        .bio-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .bio-section .label {
            font-weight: bold;
            color: #555;
            display: block;
            margin-bottom: 5px;
        }

        .bio {
            color: #666;
            font-style: italic;
            margin: 0;
            line-height: 1.4;
        }

        @media (max-width: 768px) {
            .drivers-grid {
                grid-template-columns: 1fr;
            }

            .driver-header {
                flex-direction: column;
                text-align: center;
            }

            .driver-picture {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
    </style>
</body>
</html>