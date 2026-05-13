<?php
/**
 * Notification Display Component
 * 
 * Include this file in your dashboard pages to display notifications
 * Requires: $_SESSION['user_id'] to be set
 */

if (!isset($_SESSION['user_id'])) {
    return;
}

require_once __DIR__ . '/NotificationManager.php';

$notificationManager = new NotificationManager($conn);
$unreadCount = count($notificationManager->getUnreadNotifications($_SESSION['user_id']));
?>

<style>
.notification-container {
    position: relative;
}

.notification-bell {
    position: relative;
    cursor: pointer;
    font-size: 20px;
    display: inline-block;
}

.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.notification-panel {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    width: 350px;
    max-height: 500px;
    overflow-y: auto;
    z-index: 1000;
    margin-top: 10px;
}

.notification-panel.active {
    display: block;
}

.notification-header {
    padding: 15px;
    border-bottom: 1px solid #ddd;
    background: #f8f9fa;
    font-weight: bold;
}

.notification-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.notification-item {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background 0.3s;
}

.notification-item:hover {
    background: #f5f5f5;
}

.notification-item.unread {
    background: #e7f3ff;
}

.notification-item-title {
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.notification-item-message {
    font-size: 13px;
    color: #666;
    margin-bottom: 5px;
}

.notification-item-time {
    font-size: 11px;
    color: #999;
}

.notification-footer {
    padding: 10px 15px;
    text-align: center;
    border-top: 1px solid #ddd;
    background: #f8f9fa;
}

.notification-footer a {
    color: #007bff;
    text-decoration: none;
    font-size: 13px;
}

.notification-footer a:hover {
    text-decoration: underline;
}

.empty-notification {
    padding: 30px 15px;
    text-align: center;
    color: #999;
}
</style>

<div class="notification-container">
    <div class="notification-bell" onclick="toggleNotificationPanel()">
        🔔
        <?php if ($unreadCount > 0): ?>
            <span class="notification-badge"><?php echo $unreadCount; ?></span>
        <?php endif; ?>
    </div>
    
    <div class="notification-panel" id="notificationPanel">
        <div class="notification-header">
            Notifications
        </div>
        
        <ul class="notification-list" id="notificationList">
            <!-- Loaded via AJAX -->
        </ul>
        
        <div class="notification-footer">
            <a href="<?php echo $_SESSION['role']; ?>/notifications.php">View All</a>
        </div>
    </div>
</div>

<script>
function toggleNotificationPanel() {
    const panel = document.getElementById('notificationPanel');
    panel.classList.toggle('active');
    
    if (panel.classList.contains('active')) {
        loadNotifications();
    }
}

function loadNotifications() {
    fetch('<?php echo dirname($_SERVER['PHP_SELF']); ?>/api/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            const list = document.getElementById('notificationList');
            list.innerHTML = '';
            
            if (data.length === 0) {
                list.innerHTML = '<div class="empty-notification">No notifications</div>';
                return;
            }
            
            data.forEach(notif => {
                const item = document.createElement('li');
                item.className = 'notification-item' + (notif.is_read ? '' : ' unread');
                
                const time = new Date(notif.created_at).toLocaleDateString();
                
                item.innerHTML = `
                    <div class="notification-item-title">${notif.title}</div>
                    <div class="notification-item-message">${notif.message}</div>
                    <div class="notification-item-time">${time}</div>
                `;
                
                item.onclick = () => markAsRead(notif.id);
                list.appendChild(item);
            });
        })
        .catch(error => console.error('Error loading notifications:', error));
}

function markAsRead(notificationId) {
    fetch('<?php echo dirname($_SERVER['PHP_SELF']); ?>/api/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        loadNotifications();
    })
    .catch(error => console.error('Error:', error));
}

// Close panel when clicking outside
document.addEventListener('click', function(event) {
    const container = document.querySelector('.notification-container');
    if (!container.contains(event.target)) {
        document.getElementById('notificationPanel').classList.remove('active');
    }
});

// Auto-refresh notifications every 30 seconds
setInterval(() => {
    const panel = document.getElementById('notificationPanel');
    if (panel.classList.contains('active')) {
        loadNotifications();
    }
}, 30000);
</script>
