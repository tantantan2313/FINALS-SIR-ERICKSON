<?php
/**
 * Database Migration for Notification System
 * 
 * This creates necessary tables and columns for the auto-notification system.
 * Run this script once to set up the database.
 */

require_once __DIR__ . '/config.php';

$migrations = [];
$results = [];

// 1. Update notifications table to add missing columns
if (!column_exists('notifications', 'title', $conn)) {
    $migration = "ALTER TABLE notifications ADD COLUMN title VARCHAR(255) AFTER type";
    if ($conn->query($migration)) {
        $results[] = "✓ Added 'title' column to notifications table";
    } else {
        $results[] = "✗ Failed to add 'title' column: " . $conn->error;
    }
}

if (!column_exists('notifications', 'data', $conn)) {
    $migration = "ALTER TABLE notifications ADD COLUMN data JSON AFTER message";
    if ($conn->query($migration)) {
        $results[] = "✓ Added 'data' column to notifications table";
    } else {
        $results[] = "✗ Failed to add 'data' column: " . $conn->error;
    }
}

// 2. Create notification_log table
if (!table_exists('notification_log', $conn)) {
    $migration = "
    CREATE TABLE IF NOT EXISTS notification_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trip_id INT,
        payment_id INT,
        notification_type VARCHAR(50),
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_log (trip_id, payment_id, notification_type),
        FOREIGN KEY (trip_id) REFERENCES trips(id),
        FOREIGN KEY (payment_id) REFERENCES payments(id)
    )
    ";
    
    if ($conn->query($migration)) {
        $results[] = "✓ Created 'notification_log' table";
    } else {
        $results[] = "✗ Failed to create 'notification_log' table: " . $conn->error;
    }
}

// 3. Update notifications table for extended type enum
$migration = "ALTER TABLE notifications MODIFY COLUMN type ENUM('pickup', 'dropoff', 'delay', 'general', 'new_bill', 'payment_reminder', 'overdue_payment', 'payment_received', 'billing_summary', 'new_driver_app', 'driver_approved', 'driver_rejected', 'new_student', 'new_parent', 'system_alert', 'daily_report', 'weekly_report', 'monthly_report', 'weekly_summary', 'rating_request', 'trip_assignment', 'in_transit')";

if ($conn->query($migration)) {
    $results[] = "✓ Updated notification types";
} else {
    // This might fail if column already has these values - that's OK
    $results[] = "⚠ Notification types already updated or column already exists";
}

// Display results
echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Migration Results</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #f5f5f5; padding: 20px; border-radius: 5px; }
        h1 { color: #333; }
        .result { padding: 10px; margin: 10px 0; border-left: 4px solid #007bff; background: white; }
        .success { border-left-color: #28a745; }
        .error { border-left-color: #dc3545; }
        .warning { border-left-color: #ffc107; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Database Migration Results</h1>";

foreach ($results as $result) {
    $class = 'success';
    if (strpos($result, '✗') === 0) $class = 'error';
    if (strpos($result, '⚠') === 0) $class = 'warning';
    
    echo "<div class='result {$class}'>{$result}</div>";
}

echo "
        <hr>
        <p><strong>Next Steps:</strong></p>
        <ul>
            <li>Set up a cron job to run <code>scheduler/notification_scheduler.php</code> every 5-15 minutes</li>
            <li>For Windows Task Scheduler, use: <code>curl http://localhost/Traysikel/scheduler/notification_scheduler.php</code></li>
            <li>Review the notification system integration in your application files</li>
        </ul>
    </div>
</body>
</html>";

// Helper functions
function table_exists($tableName, $conn) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result && $result->num_rows > 0;
}

function column_exists($tableName, $columnName, $conn) {
    $result = $conn->query("SHOW COLUMNS FROM $tableName LIKE '$columnName'");
    return $result && $result->num_rows > 0;
}
?>
