<?php
require_once '../config.php';
requireRole('parent');

$user_id = $_SESSION['user_id'];
$trip_id = (int)($_POST['trip_id'] ?? 0);
$action = $_POST['action'] ?? '';

// Verify trip belongs to parent's student
$trip_check = $conn->query("
    SELECT t.*, s.parent_id 
    FROM trips t 
    JOIN students s ON t.student_id = s.id 
    WHERE t.id = $trip_id AND s.parent_id = $user_id
");

if ($trip_check->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid trip']);
    exit;
}

$trip = $trip_check->fetch_assoc();

if ($action == 'confirm_arrival') {
    // Confirm that the trip is completed
    $conn->query("UPDATE trips SET status = 'completed' WHERE id = $trip_id");
    echo json_encode(['success' => true, 'message' => 'Trip confirmed as completed']);
} elseif ($action == 'mark_issue') {
    // Mark trip with an issue
    $issue_notes = sanitize($_POST['issue_notes'] ?? '');
    $conn->query("UPDATE trips SET status = 'issue_reported', notes = '$issue_notes' WHERE id = $trip_id");
    
    // Notify driver and admin
    require_once '../includes/NotificationManager.php';
    $notificationManager = new NotificationManager($conn);
    $notificationManager->send(
        $trip['driver_id'],
        'Trip Issue Reported',
        'Parent reported an issue with trip #' . $trip_id . ': ' . $issue_notes,
        'trip_issue',
        ['trip_id' => $trip_id, 'issue' => $issue_notes]
    );
    
    echo json_encode(['success' => true, 'message' => 'Issue reported to driver']);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
exit;
?>
