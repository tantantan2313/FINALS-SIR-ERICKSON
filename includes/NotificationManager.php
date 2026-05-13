<?php

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class NotificationManager {
    private $conn;
    private $mailConfig;

    public function __construct($connection) {
        $this->conn = $connection;
        $this->mailConfig = require __DIR__ . '/../mail_config.php';
    }

    /**
     * Send both email and in-app notification
     */
    public function send($userId, $title, $message, $type, $data = []) {
        // Save to database (in-app notification)
        $this->saveToDatabase($userId, $title, $message, $type, $data);

        // Get user email
        $user = $this->getUserById($userId);
        if ($user && $user['email']) {
            // Send email notification
            $this->sendEmail($user['email'], $user['full_name'], $title, $message, $data);
        }

        return true;
    }

    /**
     * Send batch notifications to multiple users
     */
    public function sendBatch($userIds, $title, $message, $type, $data = []) {
        foreach ($userIds as $userId) {
            $this->send($userId, $title, $message, $type, $data);
        }
        return true;
    }

    /**
     * Save notification to database
     */
    private function saveToDatabase($userId, $title, $message, $type, $data) {
        $dataJson = json_encode($data);
        
        $sql = "INSERT INTO notifications (user_id, title, message, type, data, is_read, created_at) 
                VALUES (?, ?, ?, ?, ?, FALSE, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Database error: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("issss", $userId, $title, $message, $type, $dataJson);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    /**
     * Send email notification
     */
    private function sendEmail($recipientEmail, $recipientName, $title, $message, $data) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->mailConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->mailConfig['username'];
            $mail->Password = $this->mailConfig['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->mailConfig['port'];

            // Recipients
            $mail->setFrom($this->mailConfig['from_email'], $this->mailConfig['from_name']);
            $mail->addAddress($recipientEmail, $recipientName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $title;
            
            // Generate HTML email body
            $htmlBody = $this->generateEmailTemplate($title, $message, $data);
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);

            // Send email
            return $mail->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate HTML email template
     */
    private function generateEmailTemplate($title, $message, $data) {
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 5px; }
                .header { background: #007bff; color: white; padding: 20px; border-radius: 5px 5px 0 0; text-align: center; }
                .content { background: white; padding: 20px; border: 1px solid #ddd; }
                .footer { background: #f0f0f0; padding: 10px; text-align: center; font-size: 12px; }
                .btn { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>{$title}</h2>
                </div>
                <div class='content'>
                    <p>{$message}</p>";
        
        // Add any additional data
        if (!empty($data)) {
            $html .= "<div style='margin-top: 20px;'>";
            foreach ($data as $key => $value) {
                $html .= "<p><strong>" . ucfirst($key) . ":</strong> {$value}</p>";
            }
            $html .= "</div>";
        }

        $html .= "
                    <p style='margin-top: 20px; color: #666;'>
                        Please log in to the system to view more details.
                    </p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " School Transport System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Get user by ID
     */
    private function getUserById($userId) {
        $sql = "SELECT id, username, email, full_name FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId) {
        $sql = "UPDATE notifications SET is_read = TRUE WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $notificationId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Get unread notifications for user
     */
    public function getUnreadNotifications($userId) {
        $sql = "SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $notifications;
    }

    /**
     * Get all notifications for user
     */
    public function getNotifications($userId, $limit = 50) {
        $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $notifications;
    }

    /**
     * Delete notification
     */
    public function deleteNotification($notificationId) {
        $sql = "DELETE FROM notifications WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $notificationId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
?>
