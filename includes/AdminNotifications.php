<?php
// Admin notification handlers

class AdminNotifications {
    private $notificationManager;
    private $conn;

    public function __construct($connection, $notificationManager) {
        $this->conn = $connection;
        $this->notificationManager = $notificationManager;
    }

    /**
     * Notify admin of new driver application
     */
    public function notifyNewDriverApplication($driverId, $userId) {
        $driver = $this->getDriverDetails($driverId);
        $user = $this->getUser($userId);
        $admin = $this->getAdminUsers();

        foreach ($admin as $adminUser) {
            $title = "New Driver Application";
            $message = "{$user['full_name']} has submitted a new driver application";
            $data = [
                'driver_name' => $user['full_name'],
                'license_number' => $driver['license_number'],
                'vehicle_type' => $driver['vehicle_type'],
                'vehicle_plate' => $driver['vehicle_plate'],
                'status' => $driver['status']
            ];

            $this->notificationManager->send(
                $adminUser['id'],
                $title,
                $message,
                'new_driver_app',
                $data
            );
        }
    }

    /**
     * Notify driver of application approval
     */
    public function notifyDriverApproved($userId) {
        $user = $this->getUser($userId);

        $title = "Driver Application Approved";
        $message = "Congratulations! Your driver application has been approved";
        $data = [
            'status' => 'approved',
            'message' => 'You can now start accepting trip assignments'
        ];

        $this->notificationManager->send(
            $userId,
            $title,
            $message,
            'driver_approved',
            $data
        );
    }

    /**
     * Notify driver of application rejection
     */
    public function notifyDriverRejected($userId, $reason = '') {
        $user = $this->getUser($userId);

        $title = "Driver Application Status";
        $message = "Your driver application has been reviewed and needs further clarification";
        $data = [
            'status' => 'rejected',
            'reason' => $reason ?: 'Please contact admin for more information'
        ];

        $this->notificationManager->send(
            $userId,
            $title,
            $message,
            'driver_rejected',
            $data
        );
    }

    /**
     * Notify admin of new student registration
     */
    public function notifyNewStudentRegistration($studentId) {
        $student = $this->getStudentDetails($studentId);
        $parent = $this->getUser($student['parent_id']);
        $admin = $this->getAdminUsers();

        foreach ($admin as $adminUser) {
            $title = "New Student Registration";
            $message = "A new student {$student['name']} has been registered by {$parent['full_name']}";
            $data = [
                'student_name' => $student['name'],
                'student_grade' => $student['grade'],
                'parent_name' => $parent['full_name'],
                'student_id' => $studentId
            ];

            $this->notificationManager->send(
                $adminUser['id'],
                $title,
                $message,
                'new_student',
                $data
            );
        }
    }

    /**
     * Notify admin of new parent registration
     */
    public function notifyNewParentRegistration($userId) {
        $user = $this->getUser($userId);
        $admin = $this->getAdminUsers();

        foreach ($admin as $adminUser) {
            $title = "New Parent Registration";
            $message = "A new parent account has been created: {$user['full_name']}";
            $data = [
                'parent_name' => $user['full_name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'user_id' => $userId
            ];

            $this->notificationManager->send(
                $adminUser['id'],
                $title,
                $message,
                'new_parent',
                $data
            );
        }
    }

    /**
     * Notify admin of critical system alert
     */
    public function notifySystemAlert($alertType, $message, $data = []) {
        $admin = $this->getAdminUsers();

        foreach ($admin as $adminUser) {
            $title = "System Alert: " . ucfirst(str_replace('_', ' ', $alertType));
            
            $this->notificationManager->send(
                $adminUser['id'],
                $title,
                $message,
                'system_alert',
                array_merge(['alert_type' => $alertType], $data)
            );
        }
    }

    /**
     * Send daily system report to admin
     */
    public function sendDailySystemReport() {
        $admin = $this->getAdminUsers();

        // Collect statistics
        $stats = $this->getDailyStatistics();

        foreach ($admin as $adminUser) {
            $title = "Daily System Report";
            $message = "Here is today's system activity summary";
            $data = [
                'date' => date('M d, Y'),
                'total_trips' => $stats['total_trips'],
                'completed_trips' => $stats['completed_trips'],
                'pending_trips' => $stats['pending_trips'],
                'total_payments' => $stats['total_payments'],
                'paid_payments' => $stats['paid_payments'],
                'overdue_payments' => $stats['overdue_payments'],
                'active_drivers' => $stats['active_drivers'],
                'new_registrations' => $stats['new_registrations']
            ];

            $this->notificationManager->send(
                $adminUser['id'],
                $title,
                $message,
                'daily_report',
                $data
            );
        }
    }

    /**
     * Send weekly system report to admin
     */
    public function sendWeeklySystemReport() {
        $admin = $this->getAdminUsers();

        // Collect statistics
        $stats = $this->getWeeklyStatistics();

        foreach ($admin as $adminUser) {
            $title = "Weekly System Report";
            $message = "Here is this week's system activity summary";
            $data = [
                'week_ending' => date('M d, Y'),
                'total_trips' => $stats['total_trips'],
                'completed_trips' => $stats['completed_trips'],
                'total_revenue' => $stats['total_revenue'],
                'paid_revenue' => $stats['paid_revenue'],
                'overdue_revenue' => $stats['overdue_revenue'],
                'new_drivers' => $stats['new_drivers'],
                'new_students' => $stats['new_students'],
                'average_rating' => $stats['average_rating']
            ];

            $this->notificationManager->send(
                $adminUser['id'],
                $title,
                $message,
                'weekly_report',
                $data
            );
        }
    }

    /**
     * Send monthly system report to admin
     */
    public function sendMonthlySystemReport() {
        $admin = $this->getAdminUsers();

        // Collect statistics
        $stats = $this->getMonthlyStatistics();

        foreach ($admin as $adminUser) {
            $title = "Monthly System Report";
            $message = "Here is this month's complete system activity summary";
            $data = [
                'month' => date('F Y'),
                'total_trips' => $stats['total_trips'],
                'completed_trips' => $stats['completed_trips'],
                'total_revenue' => $stats['total_revenue'],
                'paid_revenue' => $stats['paid_revenue'],
                'outstanding_revenue' => $stats['outstanding_revenue'],
                'active_drivers' => $stats['active_drivers'],
                'active_students' => $stats['active_students'],
                'average_rating' => $stats['average_rating']
            ];

            $this->notificationManager->send(
                $adminUser['id'],
                $title,
                $message,
                'monthly_report',
                $data
            );
        }
    }

    // Helper functions
    private function getAdminUsers() {
        $sql = "SELECT * FROM users WHERE role = 'admin'";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    private function getDriverDetails($driverId) {
        $sql = "SELECT * FROM drivers WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $driverId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    private function getStudentDetails($studentId) {
        $sql = "SELECT * FROM students WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    private function getUser($userId) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    private function getDailyStatistics() {
        $today = date('Y-m-d');
        
        $stats = [
            'total_trips' => 0,
            'completed_trips' => 0,
            'pending_trips' => 0,
            'total_payments' => 0,
            'paid_payments' => 0,
            'overdue_payments' => 0,
            'active_drivers' => 0,
            'new_registrations' => 0
        ];

        // Get trips
        $sql = "SELECT COUNT(*) as total, 
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
                FROM trips WHERE trip_date = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stats['total_trips'] = $result['total'];
        $stats['completed_trips'] = $result['completed'];
        $stmt->close();

        // Get payments
        $sql = "SELECT COUNT(*) as total, 
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid,
                COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue
                FROM payments WHERE DATE(created_at) = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stats['total_payments'] = $result['total'];
        $stats['paid_payments'] = $result['paid'];
        $stats['overdue_payments'] = $result['overdue'];
        $stmt->close();

        return $stats;
    }

    private function getWeeklyStatistics() {
        $startDate = date('Y-m-d', strtotime('-7 days'));
        
        $stats = [
            'total_trips' => 0,
            'completed_trips' => 0,
            'total_revenue' => 0,
            'paid_revenue' => 0,
            'overdue_revenue' => 0,
            'new_drivers' => 0,
            'new_students' => 0,
            'average_rating' => 0
        ];

        // Get trips
        $sql = "SELECT COUNT(*) as total, 
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
                FROM trips WHERE trip_date >= ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $startDate);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stats['total_trips'] = $result['total'];
        $stats['completed_trips'] = $result['completed'];
        $stmt->close();

        return $stats;
    }

    private function getMonthlyStatistics() {
        $startDate = date('Y-m-01');
        
        $stats = [
            'total_trips' => 0,
            'completed_trips' => 0,
            'total_revenue' => 0,
            'paid_revenue' => 0,
            'outstanding_revenue' => 0,
            'active_drivers' => 0,
            'active_students' => 0,
            'average_rating' => 0
        ];

        // Get trips
        $sql = "SELECT COUNT(*) as total, 
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
                FROM trips WHERE trip_date >= ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $startDate);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stats['total_trips'] = $result['total'];
        $stats['completed_trips'] = $result['completed'];
        $stmt->close();

        return $stats;
    }
}
?>
