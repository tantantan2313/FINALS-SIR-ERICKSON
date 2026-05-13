<?php
// Script to generate monthly payments for students
// This should be run at the beginning of each month

require_once 'config.php';

// Only allow admin to run this
if (!isset($_SESSION['user_id']) || getUserRole() !== 'admin') {
    die("Access denied");
}

$message = '';

// Get current month and year
$current_month = date('Y-m-01');
$next_month = date('Y-m-01', strtotime('+1 month'));

// Check if payments already exist for this month
$existing_payments = $conn->query("SELECT COUNT(*) as count FROM payments WHERE due_date = '$next_month'")->fetch_assoc()['count'];

if ($existing_payments > 0) {
    $message = "Payments for next month already exist ($existing_payments payments found)";
} else {
    // Generate payments for all students with assigned drivers
    $students = $conn->query("
        SELECT s.id, s.parent_id, s.name
        FROM students s
        WHERE s.driver_id IS NOT NULL
    ");

    $payment_count = 0;
    while ($student = $students->fetch_assoc()) {
        // Monthly fee of $50
        $amount = 50.00;

        $stmt = $conn->prepare("INSERT INTO payments (parent_id, student_id, amount, due_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iids", $student['parent_id'], $student['id'], $amount, $next_month);
        $stmt->execute();
        $payment_count++;
    }

    $message = "Generated $payment_count payments for " . date('F Y', strtotime($next_month));
}

redirect("admin/dashboard.php?message=" . urlencode($message));
?>
