<?php
// Database migration script to update notifications table
// Run this once to update your database schema

$migrations = [
    // Add missing columns to notifications table if they don't exist
    "ALTER TABLE notifications ADD COLUMN title VARCHAR(255) AFTER type",
    "ALTER TABLE notifications ADD COLUMN data JSON AFTER message"
];

// This script helps verify the schema
function checkAndUpdateSchema($conn) {
    // Check if columns exist
    $sql = "DESCRIBE notifications";
    $result = $conn->query($sql);
    $columns = [];
    
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $output = [];
    
    if (!in_array('title', $columns)) {
        if ($conn->query("ALTER TABLE notifications ADD COLUMN title VARCHAR(255) AFTER type")) {
            $output[] = "✓ Added 'title' column";
        } else {
            $output[] = "✗ Failed to add 'title' column: " . $conn->error;
        }
    } else {
        $output[] = "✓ 'title' column already exists";
    }
    
    if (!in_array('data', $columns)) {
        if ($conn->query("ALTER TABLE notifications ADD COLUMN data JSON AFTER message")) {
            $output[] = "✓ Added 'data' column";
        } else {
            $output[] = "✗ Failed to add 'data' column: " . $conn->error;
        }
    } else {
        $output[] = "✓ 'data' column already exists";
    }
    
    // Check drivers table for GCash QR column
    $sql = "DESCRIBE drivers";
    $result = $conn->query($sql);
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    if (!in_array('qrcode_image', $columns)) {
        if ($conn->query("ALTER TABLE drivers ADD COLUMN qrcode_image VARCHAR(255) NULL")) {
            $output[] = "✓ Added 'qrcode_image' column to drivers";
        } else {
            $output[] = "✗ Failed to add 'qrcode_image' column to drivers: " . $conn->error;
        }
    } else {
        $output[] = "✓ 'qrcode_image' column already exists in drivers";
    }
    
    return $output;
}

// If this file is accessed directly
if (basename($_SERVER['PHP_SELF']) === 'database_migration.php') {
    require_once __DIR__ . '/config.php';
    
    echo "<pre>";
    echo "Running Database Migrations...\n";
    echo str_repeat("=", 50) . "\n\n";
    
    $results = checkAndUpdateSchema($conn);
    foreach ($results as $result) {
        echo $result . "\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Migration complete!\n";
    echo "</pre>";
}
?>
