<?php
// Trip notification handlers

class TripNotifications {
    private $notificationManager;
    private $conn;

    public function __construct($connection, $notificationManager) {
        $this->conn = $connection;
        $this->notificationManager = $notificationManager;
    }

    /**
     * Notify parent when student is picked up
     */
    public function notifyPickup($tripId, $driverId, $studentId) {
        $trip = $this->getTripDetails($tripId);
        $student = $this->getStudentDetails($studentId);
        $driver = $this->getDriverDetails($driverId);
        $parent = $this->getParentDetails($student['parent_id']);

        if ($parent) {
            $title = "Student Pickup Notification";
            $message = "{$student['name']} has been picked up by {$driver['full_name']}";
            $data = [
                'student_name' => $student['name'],
                'driver_name' => $driver['full_name'],
                'vehicle' => $driver['vehicle_type'] . " - " . $driver['vehicle_plate'],
                'pickup_time' => $trip['pickup_time']
            ];

            $this->notificationManager->send(
                $parent['id'],
                $title,
                $message,
                'pickup',
                $data
            );
        }
    }

    /**
     * Notify parent when student is in transit
     */
    public function notifyInTransit($tripId, $studentId) {
        $trip = $this->getTripDetails($tripId);
        $student = $this->getStudentDetails($studentId);
        $parent = $this->getParentDetails($student['parent_id']);

        if ($parent) {
            $title = "Student In Transit";
            $message = "{$student['name']} is currently in transit and will arrive soon";
            $data = [
                'student_name' => $student['name'],
                'status' => 'in_transit'
            ];

            $this->notificationManager->send(
                $parent['id'],
                $title,
                $message,
                'in_transit',
                $data
            );
        }
    }

    /**
     * Notify parent when student is dropped off
     */
    public function notifyDropoff($tripId, $studentId) {
        $trip = $this->getTripDetails($tripId);
        $student = $this->getStudentDetails($studentId);
        $parent = $this->getParentDetails($student['parent_id']);

        if ($parent) {
            $title = "Student Drop-off Notification";
            $message = "{$student['name']} has been safely dropped off";
            $data = [
                'student_name' => $student['name'],
                'dropoff_time' => date('h:i A'),
                'status' => 'dropped_off'
            ];

            $this->notificationManager->send(
                $parent['id'],
                $title,
                $message,
                'dropoff',
                $data
            );
        }
    }

    /**
     * Notify parent of trip delay
     */
    public function notifyDelay($tripId, $studentId, $delayMinutes) {
        $trip = $this->getTripDetails($tripId);
        $student = $this->getStudentDetails($studentId);
        $parent = $this->getParentDetails($student['parent_id']);

        if ($parent) {
            $title = "Trip Delay Alert";
            $message = "{$student['name']}'s pickup is delayed by approximately {$delayMinutes} minutes";
            $data = [
                'student_name' => $student['name'],
                'delay_minutes' => $delayMinutes,
                'estimated_time' => date('h:i A', strtotime("+{$delayMinutes} minutes"))
            ];

            $this->notificationManager->send(
                $parent['id'],
                $title,
                $message,
                'delay',
                $data
            );
        }
    }

    /**
     * Notify parent to rate trip
     */
    public function notifyRatingRequest($tripId, $studentId) {
        $student = $this->getStudentDetails($studentId);
        $parent = $this->getParentDetails($student['parent_id']);

        if ($parent) {
            $title = "Rate Your Trip";
            $message = "Please rate your experience with {$student['name']}'s recent trip";
            $data = [
                'trip_id' => $tripId,
                'student_name' => $student['name']
            ];

            $this->notificationManager->send(
                $parent['id'],
                $title,
                $message,
                'rating_request',
                $data
            );
        }
    }

    /**
     * Notify driver of new trip assignment
     */
    public function notifyDriverNewTrip($tripId, $driverId) {
        $trip = $this->getTripDetails($tripId);
        $student = $this->getStudentDetails($trip['student_id']);

        $title = "New Trip Assignment";
        $message = "You have been assigned to transport {$student['name']} on " . $trip['trip_date'];
        $data = [
            'trip_id' => $tripId,
            'student_name' => $student['name'],
            'pickup_time' => $trip['pickup_time'],
            'trip_date' => $trip['trip_date']
        ];

        $this->notificationManager->send(
            $driverId,
            $title,
            $message,
            'trip_assignment',
            $data
        );
    }

    /**
     * Weekly trip summary for parent
     */
    public function sendWeeklyTripSummary($parentId) {
        $user = $this->getUser($parentId);
        
        // Get trips from past 7 days
        $sql = "SELECT COUNT(*) as total_trips, 
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_trips,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM trips 
                WHERE student_id IN (SELECT id FROM students WHERE parent_id = ?)
                AND trip_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $parentId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $title = "Weekly Trip Summary";
        $message = "Summary of transportation activities for the past week";
        $data = [
            'total_trips' => $result['total_trips'],
            'completed_trips' => $result['completed_trips'],
            'week_ending' => date('M d, Y')
        ];

        $this->notificationManager->send(
            $parentId,
            $title,
            $message,
            'weekly_summary',
            $data
        );
    }

    // Helper functions
    private function getTripDetails($tripId) {
        $sql = "SELECT * FROM trips WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $tripId);
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

    private function getDriverDetails($driverId) {
        $sql = "SELECT u.* FROM users u WHERE u.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $driverId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    private function getParentDetails($parentId) {
        $sql = "SELECT * FROM users WHERE id = ? AND role = 'parent'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $parentId);
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
}
?>
