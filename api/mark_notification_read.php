<?php
/**
 * API endpoint for marking notification as read
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/NotificationManager.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $notificationId = $data['notification_id'] ?? null;
    
    if (!$notificationId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing notification_id']);
        exit;
    }
    
    $notificationManager = new NotificationManager($conn);
    $result = $notificationManager->markAsRead($notificationId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to mark as read']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
