<?php
require_once '../config.php';
require_once '../includes/NotificationManager.php';
require_once '../includes/TripNotifications.php';
require_once '../includes/PaymentNotifications.php';

requireRole('driver');

$user_id = $_SESSION['user_id'];
$trip_id = (int)($_GET['trip_id'] ?? $_POST['trip_id'] ?? 0);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Initialize notification system
$notificationManager = new NotificationManager($conn);
$tripNotifications = new TripNotifications($conn, $notificationManager);
$paymentNotifications = new PaymentNotifications($conn, $notificationManager);

// Verify trip belongs to driver
$trip_check = $conn->query("SELECT t.*, s.parent_id, s.id as student_id FROM trips t JOIN students s ON t.student_id = s.id WHERE t.id = $trip_id AND t.driver_id = $user_id");
if ($trip_check->num_rows == 0) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        echo json_encode(['success' => false, 'error' => 'Invalid trip']);
    } else {
        die("Invalid trip");
    }
    exit;
}

$trip = $trip_check->fetch_assoc();
$parent_id = $trip['parent_id'];
$student_id = $trip['student_id'];

$message = '';
$pickup_lat = !empty($_POST['pickup_lat']) ? (float)$_POST['pickup_lat'] : null;
$pickup_lng = !empty($_POST['pickup_lng']) ? (float)$_POST['pickup_lng'] : null;
$dropoff_lat = !empty($_POST['dropoff_lat']) ? (float)$_POST['dropoff_lat'] : null;
$dropoff_lng = !empty($_POST['dropoff_lng']) ? (float)$_POST['dropoff_lng'] : null;

// Log for debugging
error_log("Action: $action, Trip: $trip_id, Pickup: ($pickup_lat, $pickup_lng), Dropoff: ($dropoff_lat, $dropoff_lng)");

if ($action == 'arrived') {
    // Update with pickup coordinates if provided - use prepared statement
    if ($pickup_lat !== null && $pickup_lng !== null) {
        $stmt = $conn->prepare("UPDATE trips SET status = 'picked_up', pickup_time = NOW(), pickup_lat = ?, pickup_lng = ? WHERE id = ?");
        $stmt->bind_param("ddi", $pickup_lat, $pickup_lng, $trip_id);
        $stmt->execute();
    } else {
        $conn->query("UPDATE trips SET status = 'picked_up', pickup_time = NOW() WHERE id = $trip_id");
    }
    // Send email notification to parent
    $tripNotifications->notifyPickup($trip_id, $user_id, $student_id);
    $message = 'Pickup marked as arrived';
} elseif ($action == 'start') {
    $conn->query("UPDATE trips SET status = 'in_transit' WHERE id = $trip_id");
    // Send email notification that trip is in transit
    $tripNotifications->notifyInTransit($trip_id, $student_id);
    $message = 'Trip started';
} elseif ($action == 'dropped_off') {
    // Handle photo upload
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $upload_dir = __DIR__ . '/../uploads/';
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
            $message = 'Unable to prepare the uploads directory.';
        }

        if (!$message) {
            $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $file_name = 'trip_' . $trip_id . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                $photo_path = $file_name;
            }
        }
    }

    // Update with dropoff coordinates if provided - use prepared statement
    $notes = sanitize($_POST['notes'] ?? '');
    
    if ($dropoff_lat !== null && $dropoff_lng !== null) {
        if ($photo_path) {
            $stmt = $conn->prepare("UPDATE trips SET status = 'dropped_off', dropoff_time = NOW(), dropoff_lat = ?, dropoff_lng = ?, photo_proof = ?, notes = ? WHERE id = ?");
            $stmt->bind_param("ddssi", $dropoff_lat, $dropoff_lng, $photo_path, $notes, $trip_id);
        } else {
            $stmt = $conn->prepare("UPDATE trips SET status = 'dropped_off', dropoff_time = NOW(), dropoff_lat = ?, dropoff_lng = ?, notes = ? WHERE id = ?");
            $stmt->bind_param("ddsi", $dropoff_lat, $dropoff_lng, $notes, $trip_id);
        }
        $stmt->execute();
    } else {
        if ($photo_path) {
            $stmt = $conn->prepare("UPDATE trips SET status = 'dropped_off', dropoff_time = NOW(), photo_proof = ?, notes = ? WHERE id = ?");
            $stmt->bind_param("ssi", $photo_path, $notes, $trip_id);
        } else {
            $stmt = $conn->prepare("UPDATE trips SET status = 'dropped_off', dropoff_time = NOW(), notes = ? WHERE id = ?");
            $stmt->bind_param("si", $notes, $trip_id);
        }
        $stmt->execute();
    }

    // Create payment record for this drop-off if one does not already exist for today
    $payment_id = null;
    $stmt = $conn->prepare("SELECT id FROM payments WHERE parent_id = ? AND student_id = ? AND due_date = CURDATE() AND status != 'paid' LIMIT 1");
    $stmt->bind_param("ii", $trip['parent_id'], $trip['student_id']);
    $stmt->execute();
    $existing_payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing_payment) {
        $amount = 50.00;
        $stmt = $conn->prepare("INSERT INTO payments (parent_id, student_id, amount, due_date) VALUES (?, ?, ?, CURDATE())");
        $stmt->bind_param("iid", $trip['parent_id'], $trip['student_id'], $amount);
        if ($stmt->execute()) {
            $payment_id = $stmt->insert_id;
        }
        $stmt->close();
    }

    // Send email notification that student was dropped off
    $tripNotifications->notifyDropoff($trip_id, $student_id);
    if ($payment_id) {
        $paymentNotifications->notifyNewBill($payment_id);
    }
    $message = 'Drop-off completed' . ($photo_path ? ' with photo proof' : '') . ($payment_id ? ' Payment request generated.' : '');
}

// Return JSON if POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo json_encode(['success' => true, 'message' => $message]);
} else {
    redirect('dashboard.php?message=' . urlencode($message));
}
?>