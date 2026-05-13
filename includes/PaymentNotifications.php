<?php
// Payment notification handlers

class PaymentNotifications {
    private $notificationManager;
    private $conn;

    public function __construct($connection, $notificationManager) {
        $this->conn = $connection;
        $this->notificationManager = $notificationManager;
    }

    /**
     * Notify parent of new monthly bill
     */
    public function notifyNewBill($paymentId) {
        $payment = $this->getPaymentDetails($paymentId);
        $parent = $this->getUser($payment['parent_id']);
        $student = $this->getStudentDetails($payment['student_id']);

        if ($parent) {
            $title = "New Transportation Bill";
            $message = "A new transportation bill of ₱" . number_format($payment['amount'], 2) . " is due for {$student['name']}";
            $data = [
                'amount' => '₱' . number_format($payment['amount'], 2),
                'student_name' => $student['name'],
                'due_date' => $payment['due_date'],
                'payment_id' => $paymentId
            ];

            $this->notificationManager->send(
                $parent['id'],
                $title,
                $message,
                'new_bill',
                $data
            );
        }
    }

    /**
     * Notify parent of payment due reminder (7 days before)
     */
    public function notifyPaymentDueReminder($paymentId) {
        $payment = $this->getPaymentDetails($paymentId);
        
        if ($payment['status'] !== 'pending') {
            return; // Only remind for pending payments
        }

        $parent = $this->getUser($payment['parent_id']);
        $student = $this->getStudentDetails($payment['student_id']);
        $daysUntilDue = ceil((strtotime($payment['due_date']) - time()) / 86400);

        if ($parent && $daysUntilDue > 0) {
            $title = "Payment Due Reminder";
            $message = "Payment of ₱" . number_format($payment['amount'], 2) . " for {$student['name']} is due in {$daysUntilDue} days";
            $data = [
                'amount' => '₱' . number_format($payment['amount'], 2),
                'student_name' => $student['name'],
                'days_until_due' => $daysUntilDue,
                'due_date' => $payment['due_date'],
                'payment_id' => $paymentId
            ];

            $this->notificationManager->send(
                $parent['id'],
                $title,
                $message,
                'payment_reminder',
                $data
            );
        }
    }

    /**
     * Notify parent of overdue payment
     */
    public function notifyOverduePayment($paymentId) {
        $payment = $this->getPaymentDetails($paymentId);
        
        if ($payment['status'] !== 'overdue') {
            return;
        }

        $parent = $this->getUser($payment['parent_id']);
        $student = $this->getStudentDetails($payment['student_id']);
        $overdueByDays = ceil((time() - strtotime($payment['due_date'])) / 86400);

        if ($parent) {
            $title = "Overdue Payment Alert";
            $message = "Payment of ₱" . number_format($payment['amount'], 2) . " for {$student['name']} is now {$overdueByDays} days overdue";
            $data = [
                'amount' => '₱' . number_format($payment['amount'], 2),
                'student_name' => $student['name'],
                'overdue_days' => $overdueByDays,
                'original_due_date' => $payment['due_date'],
                'payment_id' => $paymentId
            ];

            $this->notificationManager->send(
                $parent['id'],
                $title,
                $message,
                'overdue_payment',
                $data
            );
        }
    }

    /**
     * Notify parent of payment received
     */
    public function notifyPaymentReceived($paymentId) {
        $payment = $this->getPaymentDetails($paymentId);
        $parent = $this->getUser($payment['parent_id']);
        $student = $this->getStudentDetails($payment['student_id']);

        if ($parent) {
            $title = "Payment Received";
            $message = "We have received your payment of ₱" . number_format($payment['amount'], 2) . " for {$student['name']}";
            $data = [
                'amount' => '₱' . number_format($payment['amount'], 2),
                'student_name' => $student['name'],
                'paid_date' => date('M d, Y'),
                'payment_id' => $paymentId
            ];

            $this->notificationManager->send(
                $parent['id'],
                $title,
                $message,
                'payment_received',
                $data
            );
        }
    }

    /**
     * Send monthly billing summary
     */
    public function sendMonthlyBillingSummary($parentId) {
        $user = $this->getUser($parentId);
        $students = $this->getStudents($parentId);

        // Get current month's payments
        $sql = "SELECT SUM(amount) as total_amount, COUNT(*) as total_bills,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_bills,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bills,
                COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_bills
                FROM payments 
                WHERE parent_id = ? AND MONTH(created_at) = MONTH(CURDATE()) 
                AND YEAR(created_at) = YEAR(CURDATE())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $parentId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $title = "Monthly Billing Summary";
        $message = "Here is your transportation billing summary for " . date('F Y');
        $data = [
            'total_amount' => '₱' . number_format($result['total_amount'] ?? 0, 2),
            'total_bills' => $result['total_bills'] ?? 0,
            'paid_bills' => $result['paid_bills'] ?? 0,
            'pending_bills' => $result['pending_bills'] ?? 0,
            'overdue_bills' => $result['overdue_bills'] ?? 0,
            'month' => date('F Y')
        ];

        $this->notificationManager->send(
            $parentId,
            $title,
            $message,
            'billing_summary',
            $data
        );
    }

    /**
     * Check and process automatic overdue status updates
     */
    public function checkAndUpdateOverduePayments() {
        // Find pending payments that are now overdue
        $sql = "UPDATE payments 
                SET status = 'overdue' 
                WHERE status = 'pending' 
                AND due_date < CURDATE()
                AND id NOT IN (
                    SELECT payment_id FROM notification_log 
                    WHERE notification_type = 'overdue_payment'
                )";
        
        $this->conn->query($sql);

        // Get all newly overdue payments
        $sql = "SELECT id FROM payments 
                WHERE status = 'overdue' 
                AND due_date < CURDATE()
                AND id NOT IN (
                    SELECT payment_id FROM notification_log 
                    WHERE notification_type = 'overdue_payment'
                )";
        
        $result = $this->conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $this->notifyOverduePayment($row['id']);
            $this->logNotification($row['id'], 'overdue_payment');
        }
    }

    // Helper functions
    private function getPaymentDetails($paymentId) {
        $sql = "SELECT * FROM payments WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $paymentId);
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

    private function getStudents($parentId) {
        $sql = "SELECT * FROM students WHERE parent_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $parentId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    private function logNotification($paymentId, $notificationType) {
        $sql = "INSERT INTO notification_log (payment_id, notification_type, sent_at) VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE sent_at = NOW()";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $paymentId, $notificationType);
        $stmt->execute();
        $stmt->close();
    }
}
?>
