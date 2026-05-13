<?php
// api/get_notifications.php
// API endpoint returning the authenticated user's notifications in JSON format.
/**
 * API endpoint for getting user notifications
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
    $notificationManager = new NotificationManager($conn);
    $notifications = $notificationManager->getNotifications($_SESSION['user_id'], 20);
    
    echo json_encode($notifications);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
