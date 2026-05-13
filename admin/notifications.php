<?php
// admin/notifications.php
// Admin page for viewing and managing system notifications for admin users.
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/NotificationManager.php';

// Check if logged in
if (!isLoggedIn()) {
    redirect('../../index.php');
}

$notificationManager = new NotificationManager($conn);
$userRole = getUserRole();
$userId = $_SESSION['user_id'];

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $notificationId = $_POST['notification_id'] ?? null;
    
    if ($action === 'mark_read' && $notificationId) {
        $notificationManager->markAsRead($notificationId);
    } elseif ($action === 'delete' && $notificationId) {
        $notificationManager->deleteNotification($notificationId);
    } elseif ($action === 'mark_all_read') {
        $notifications = $notificationManager->getNotifications($userId);
        foreach ($notifications as $notif) {
            if (!$notif['is_read']) {
                $notificationManager->markAsRead($notif['id']);
            }
        }
    }
    
    redirect($_SERVER['PHP_SELF']);
}

// Get notifications
$notifications = $notificationManager->getNotifications($userId);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Notifications - School Transport System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        
        .header {
            background: #007bff;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 { font-size: 24px; }
        
        .back-link {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            background: rgba(255,255,255,0.2);
            border-radius: 3px;
        }
        
        .back-link:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 900px;
            margin: 20px auto;
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .controls {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            background: #007bff;
            color: white;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .notification-item {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: start;
            transition: background 0.3s;
        }
        
        .notification-item:hover {
            background: #f9f9f9;
        }
        
        .notification-item.unread {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .notification-message {
            color: #666;
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        .notification-meta {
            font-size: 12px;
            color: #999;
        }
        
        .notification-data {
            background: #f9f9f9;
            padding: 10px;
            margin-top: 10px;
            border-radius: 3px;
            font-size: 13px;
        }
        
        .notification-data-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        
        .notification-data-item:last-child {
            border: none;
        }
        
        .notification-actions {
            display: flex;
            gap: 10px;
            margin-left: 20px;
        }
        
        .action-btn {
            padding: 8px 12px;
            border: none;
            background: #f0f0f0;
            color: #333;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .action-btn:hover {
            background: #e0e0e0;
        }
        
        .action-btn-delete {
            background: #fee;
            color: #c33;
        }
        
        .action-btn-delete:hover {
            background: #fdd;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            background: #007bff;
            color: white;
            border-radius: 3px;
            font-size: 11px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Notifications</h1>
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <?php if (!empty($notifications)): ?>
            <div class="controls">
                <div>
                    Total: <strong><?php echo count($notifications); ?></strong> notifications
                </div>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" class="btn">Mark All as Read</button>
                </form>
            </div>
            
            <div class="notifications-list">
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-content">
                            <div class="notification-title">
                                <?php echo htmlspecialchars($notif['title']); ?>
                                <span class="badge"><?php echo ucfirst($notif['type']); ?></span>
                            </div>
                            <div class="notification-message">
                                <?php echo htmlspecialchars($notif['message']); ?>
                            </div>
                            
                            <?php if ($notif['data']): ?>
                                <?php 
                                    $data = json_decode($notif['data'], true);
                                    if (is_array($data) && !empty($data)):
                                ?>
                                    <div class="notification-data">
                                        <?php foreach ($data as $key => $value): ?>
                                            <div class="notification-data-item">
                                                <strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong>
                                                <span><?php echo htmlspecialchars($value); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <div class="notification-meta">
                                <?php 
                                    $createdTime = new DateTime($notif['created_at']);
                                    $now = new DateTime();
                                    $interval = $now->diff($createdTime);
                                    
                                    if ($interval->days > 0) {
                                        echo $createdTime->format('M d, Y H:i A');
                                    } else if ($interval->h > 0) {
                                        echo $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
                                    } else {
                                        echo $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
                                    }
                                ?>
                            </div>
                        </div>
                        
                        <div class="notification-actions">
                            <?php if (!$notif['is_read']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                                    <button type="submit" class="action-btn">Mark as Read</button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                                <button type="submit" class="action-btn action-btn-delete">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h2>No Notifications</h2>
                <p>You don't have any notifications yet. Check back later!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
