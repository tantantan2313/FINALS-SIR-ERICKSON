<?php
/**
 * INTEGRATION GUIDE FOR NOTIFICATION SYSTEM
 * 
 * This file shows exactly how to integrate the notification system
 * into your existing application files.
 */

// ============================================
// FILE: driver/update_trip.php
// ============================================
// Add these lines after you update trip status

// At the top of the file, add these includes:
/*
require_once __DIR__ . '/../includes/NotificationManager.php';
require_once __DIR__ . '/../includes/TripNotifications.php';

$notificationManager = new NotificationManager($conn);
$tripNotifications = new TripNotifications($conn, $notificationManager);
*/

// When updating trip to "picked_up" status:
/*
$tripNotifications->notifyPickup($tripId, $driverId, $studentId);
*/

// When updating trip to "in_transit" status:
/*
$tripNotifications->notifyInTransit($tripId, $studentId);
*/

// When updating trip to "dropped_off" status:
/*
$tripNotifications->notifyDropoff($tripId, $studentId);
*/

// ============================================
// FILE: generate_payments.php
// ============================================
// Add these lines after creating payments

// At the top of the file, add these includes:
/*
require_once __DIR__ . '/includes/NotificationManager.php';
require_once __DIR__ . '/includes/PaymentNotifications.php';

$notificationManager = new NotificationManager($conn);
$paymentNotifications = new PaymentNotifications($conn, $notificationManager);
*/

// After inserting each payment:
/*
$paymentNotifications->notifyNewBill($paymentId);
*/

// ============================================
// FILE: admin/manage_drivers.php
// ============================================
// Add these lines when approving/rejecting drivers

// At the top of the file, add these includes:
/*
require_once __DIR__ . '/../includes/NotificationManager.php';
require_once __DIR__ . '/../includes/AdminNotifications.php';

$notificationManager = new NotificationManager($conn);
$adminNotifications = new AdminNotifications($conn, $notificationManager);
*/

// When approving a driver:
/*
// Update driver status
$sql = "UPDATE drivers SET status = 'approved' WHERE id = ?";
// ...
// Then send notification
$adminNotifications->notifyDriverApproved($driverId);
*/

// When rejecting a driver:
/*
// Update driver status
$sql = "UPDATE drivers SET status = 'rejected' WHERE id = ?";
// ...
// Then send notification
$adminNotifications->notifyDriverRejected($driverId, 'License number invalid');
*/

// ============================================
// FILE: register.php
// ============================================
// Add notification when new parent registers

// At the top of the file, add these includes:
/*
require_once __DIR__ . '/includes/NotificationManager.php';
require_once __DIR__ . '/includes/AdminNotifications.php';

$notificationManager = new NotificationManager($conn);
$adminNotifications = new AdminNotifications($conn, $notificationManager);
*/

// After inserting new user with role = 'parent':
/*
if ($role === 'parent') {
    $adminNotifications->notifyNewParentRegistration($userId);
}

// If it's a driver application
if ($role === 'driver') {
    // After creating the drivers record
    $adminNotifications->notifyNewDriverApplication($driverId, $userId);
}
*/

// ============================================
// FILE: parent/add_student.php
// ============================================
// Add notification when new student is added

// At the top of the file, add these includes:
/*
require_once __DIR__ . '/../includes/NotificationManager.php';
require_once __DIR__ . '/../includes/AdminNotifications.php';

$notificationManager = new NotificationManager($conn);
$adminNotifications = new AdminNotifications($conn, $notificationManager);
*/

// After inserting new student:
/*
$adminNotifications->notifyNewStudentRegistration($studentId);
*/

// ============================================
// FILE: parent/dashboard.php (and similar dashboards)
// ============================================
// Add notification bell to dashboard header

// In your HTML head or header section, add:
/*
<?php include __DIR__ . '/../includes/notification_display.php'; ?>
*/

// ============================================
// FILE: driver/dashboard.php (and similar dashboards)
// ============================================
// Add notification bell to dashboard header

// In your HTML head or header section, add:
/*
<?php include __DIR__ . '/../includes/notification_display.php'; ?>
*/

// ============================================
// FILE: admin/dashboard.php
// ============================================
// Add notification bell to dashboard header

// In your HTML head or header section, add:
/*
<?php include __DIR__ . '/../includes/notification_display.php'; ?>
*/

// ============================================
// EXAMPLE: Complete update_trip.php integration
// ============================================
?>

<?php
// Example of complete integration in driver/update_trip.php

/*
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/NotificationManager.php';
require_once __DIR__ . '/../includes/TripNotifications.php';

if (!isLoggedIn() || getUserRole() !== 'driver') {
    redirect('../../index.php');
}

// Initialize notification system
$notificationManager = new NotificationManager($conn);
$tripNotifications = new TripNotifications($conn, $notificationManager);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tripId = $_POST['trip_id'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if ($tripId && $status) {
        // Validate and update trip
        $sql = "UPDATE trips SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $tripId);
        
        if ($stmt->execute()) {
            // Get trip details for notifications
            $tripSql = "SELECT student_id FROM trips WHERE id = ?";
            $tripStmt = $conn->prepare($tripSql);
            $tripStmt->bind_param("i", $tripId);
            $tripStmt->execute();
            $trip = $tripStmt->get_result()->fetch_assoc();
            $tripStmt->close();
            
            $studentId = $trip['student_id'];
            $driverId = $_SESSION['user_id'];
            
            // Send appropriate notification based on status
            switch ($status) {
                case 'picked_up':
                    $tripNotifications->notifyPickup($tripId, $driverId, $studentId);
                    break;
                case 'in_transit':
                    $tripNotifications->notifyInTransit($tripId, $studentId);
                    break;
                case 'dropped_off':
                    $tripNotifications->notifyDropoff($tripId, $studentId);
                    break;
            }
            
            echo "Trip updated successfully!";
        }
        
        $stmt->close();
    }
}
?>
*/
?>
