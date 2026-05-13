<?php
/**
 * Notification Scheduler
 * 
 * This script handles automatic sending of notifications based on conditions.
 * Set up a cron job to run this file periodically (every 5-15 minutes recommended).
 * 
 * Cron job example:
 * */5 * * * * curl http://localhost/Traysikel/scheduler/notification_scheduler.php
 * 
 * Or using PHP CLI:
 * */5 * * * * php /path/to/notification_scheduler.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/NotificationManager.php';
require_once __DIR__ . '/../includes/TripNotifications.php';
require_once __DIR__ . '/../includes/PaymentNotifications.php';
require_once __DIR__ . '/../includes/AdminNotifications.php';

// Initialize managers
$notificationManager = new NotificationManager($conn);
$tripNotifications = new TripNotifications($conn, $notificationManager);
$paymentNotifications = new PaymentNotifications($conn, $notificationManager);
$adminNotifications = new AdminNotifications($conn, $notificationManager);

// Log file for debugging
$logFile = __DIR__ . '/notification_scheduler.log';

function log_message($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    error_log($logMessage, 3, $logFile);
    echo $logMessage;
}

log_message("=== Notification Scheduler Started ===");

try {
    // ===== TRIP NOTIFICATIONS =====
    log_message("Processing trip notifications...");
    
    // Check for trips that need pickup notifications
    $sql = "SELECT id, driver_id, student_id FROM trips 
            WHERE status = 'scheduled' 
            AND trip_date = CURDATE() 
            AND pickup_time <= DATE_ADD(NOW(), INTERVAL 30 MINUTE)
            AND id NOT IN (SELECT trip_id FROM notification_log WHERE notification_type = 'pickup_alert')";
    $result = $conn->query($sql);
    
    while ($trip = $result->fetch_assoc()) {
        $tripNotifications->notifyPickup($trip['id'], $trip['driver_id'], $trip['student_id']);
        log_notification($trip['id'], 'pickup_alert', $conn);
        log_message("✓ Sent pickup notification for trip {$trip['id']}");
    }

    // Check for in-transit notifications
    $sql = "SELECT id, student_id FROM trips 
            WHERE status = 'picked_up' 
            AND id NOT IN (SELECT trip_id FROM notification_log WHERE notification_type = 'in_transit_alert')";
    $result = $conn->query($sql);
    
    while ($trip = $result->fetch_assoc()) {
        $tripNotifications->notifyInTransit($trip['id'], $trip['student_id']);
        log_notification($trip['id'], 'in_transit_alert', $conn);
        log_message("✓ Sent in-transit notification for trip {$trip['id']}");
    }

    // Check for dropoff notifications
    $sql = "SELECT id, student_id FROM trips 
            WHERE status = 'dropped_off' 
            AND id NOT IN (SELECT trip_id FROM notification_log WHERE notification_type = 'dropoff_alert')";
    $result = $conn->query($sql);
    
    while ($trip = $result->fetch_assoc()) {
        $tripNotifications->notifyDropoff($trip['id'], $trip['student_id']);
        log_notification($trip['id'], 'dropoff_alert', $conn);
        log_message("✓ Sent dropoff notification for trip {$trip['id']}");
    }

    // ===== PAYMENT NOTIFICATIONS =====
    log_message("Processing payment notifications...");
    
    // Notify about payments due in 7 days
    $dueDate = date('Y-m-d', strtotime('+7 days'));
    $sql = "SELECT id FROM payments 
            WHERE status = 'pending' 
            AND due_date = ? 
            AND id NOT IN (SELECT payment_id FROM notification_log WHERE notification_type = 'payment_due_reminder')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $dueDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($payment = $result->fetch_assoc()) {
        $paymentNotifications->notifyPaymentDueReminder($payment['id']);
        log_payment_notification($payment['id'], 'payment_due_reminder', $conn);
        log_message("✓ Sent payment reminder for payment {$payment['id']}");
    }
    $stmt->close();

    // Check and notify overdue payments
    $paymentNotifications->checkAndUpdateOverduePayments();
    log_message("✓ Checked and updated overdue payments");

    // ===== DAILY REPORTS =====
    if (date('H:i') >= '22:00' && date('H:i') <= '22:10') { // Send at 10 PM
        log_message("Sending daily system reports...");
        $adminNotifications->sendDailySystemReport();
        log_message("✓ Daily system reports sent");
    }

    // ===== WEEKLY REPORTS =====
    if (date('w') == 1 && date('H:i') >= '08:00' && date('H:i') <= '08:10') { // Monday 8 AM
        log_message("Sending weekly system reports...");
        $adminNotifications->sendWeeklySystemReport();
        log_message("✓ Weekly system reports sent");
    }

    // ===== MONTHLY REPORTS =====
    if (date('d') == 1 && date('H:i') >= '09:00' && date('H:i') <= '09:10') { // 1st of month at 9 AM
        log_message("Sending monthly system reports...");
        $adminNotifications->sendMonthlySystemReport();
        log_message("✓ Monthly system reports sent");
        
        // Send monthly billing summaries to all parents
        $sql = "SELECT DISTINCT parent_id FROM students";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $paymentNotifications->sendMonthlyBillingSummary($row['parent_id']);
        }
        log_message("✓ Monthly billing summaries sent to all parents");
    }

    // ===== WEEKLY TRIP SUMMARIES =====
    if (date('w') == 0 && date('H:i') >= '18:00' && date('H:i') <= '18:10') { // Sunday 6 PM
        log_message("Sending weekly trip summaries...");
        $sql = "SELECT DISTINCT parent_id FROM students WHERE parent_id IS NOT NULL";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $tripNotifications->sendWeeklyTripSummary($row['parent_id']);
        }
        log_message("✓ Weekly trip summaries sent");
    }

    log_message("=== Notification Scheduler Completed Successfully ===\n");

} catch (Exception $e) {
    log_message("ERROR: " . $e->getMessage());
    log_message("=== Notification Scheduler Failed ===\n");
}

// Helper functions

function log_notification($tripId, $notificationType, $conn) {
    $sql = "INSERT INTO notification_log (trip_id, notification_type, sent_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE sent_at = NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $tripId, $notificationType);
    $stmt->execute();
    $stmt->close();
}

function log_payment_notification($paymentId, $notificationType, $conn) {
    $sql = "INSERT INTO notification_log (payment_id, notification_type, sent_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE sent_at = NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $paymentId, $notificationType);
    $stmt->execute();
    $stmt->close();
}

// Return 200 OK for cron job verification
http_response_code(200);
?>
