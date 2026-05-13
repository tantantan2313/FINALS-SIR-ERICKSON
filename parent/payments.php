<?php
require_once '../config.php';
requireRole('parent');

$user_id = $_SESSION['user_id'];
$message = '';

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay'])) {
    $payment_id = (int)$_POST['payment_id'];

    // In a real system, integrate with payment gateway
    // For demo, just mark as paid
    $conn->query("UPDATE payments SET status = 'paid', paid_date = CURDATE() WHERE id = $payment_id AND parent_id = $user_id");
    $message = 'Payment processed successfully!';
}

// Get payment history
$payments = $conn->query("
    SELECT p.*, s.name as student_name, d.qrcode_image, u.full_name as driver_name, d.vehicle_type, d.vehicle_plate
    FROM payments p
    JOIN students s ON p.student_id = s.id
    LEFT JOIN drivers d ON s.driver_id = d.user_id
    LEFT JOIN users u ON d.user_id = u.id
    WHERE p.parent_id = $user_id
    ORDER BY p.due_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Parent</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="nav">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="add_student.php">Add Student</a></li>
                <li><a href="select_driver.php">Select Driver</a></li>
                <li><a href="trip_history.php">Trip History</a></li>
                <li><a href="payments.php">Payments</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>

        <h1>Payment History</h1>

        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($payments->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Payment Method</th>
                        <th>Paid Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($payment = $payments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $payment['student_name']; ?></td>
                            <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                            <td><?php echo date('M d, Y', strtotime($payment['due_date'])); ?></td>
                            <td>
                                <span style="color: <?php echo $payment['status'] == 'paid' ? '#4caf50' : ($payment['status'] == 'overdue' ? '#f44336' : '#ff9800'); ?>">
                                    <?php echo ucfirst($payment['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($payment['qrcode_image']): ?>
                                    <strong>GCash QR</strong>
                                    <div style="margin-top: 6px; max-width: 120px;">
                                        <img src="../uploads/<?php echo htmlspecialchars($payment['qrcode_image']); ?>" alt="GCash QR" style="width: 100%; height: auto; border: 1px solid #ccc; border-radius: 4px; object-fit: contain; background: white;">
                                    </div>
                                <?php else: ?>
                                    Manual / Contact Driver
                                <?php endif; ?>
                            </td>
                            <td><?php echo $payment['paid_date'] ? date('M d, Y', strtotime($payment['paid_date'])) : 'N/A'; ?></td>
                            <td>
                                <?php if ($payment['status'] == 'pending' || $payment['status'] == 'overdue'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                        <button type="submit" name="pay" style="background: #4CAF50; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">Pay Now</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($payment['qrcode_image']): ?>
                            <tr>
                                <td colspan="7" style="background: #f9f9f9;">
                                    <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: center; padding: 15px; border: 1px solid #ddd; border-radius: 8px; background: #fff;">
                                        <div style="min-width: 220px;">
                                            <strong>Pay via driver GCash QR</strong>
                                            <p style="margin: 10px 0 0;">Driver: <?php echo htmlspecialchars($payment['driver_name'] ?: 'Not Assigned'); ?></p>
                                            <?php if ($payment['vehicle_type'] || $payment['vehicle_plate']): ?>
                                                <p style="margin: 5px 0 0;">Vehicle: <?php echo htmlspecialchars($payment['vehicle_type'] . ' (' . $payment['vehicle_plate'] . ')'); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div style="flex: 0 0 180px;">
                                            <img src="../uploads/<?php echo htmlspecialchars($payment['qrcode_image']); ?>" alt="GCash QR Code" style="width: 180px; height: 180px; object-fit: contain; border: 1px solid #ccc; background: white;">
                                        </div>
                                        <div style="flex: 1; min-width: 200px;">
                                            <p style="margin: 0;"><strong>Instructions:</strong></p>
                                            <ol style="margin: 8px 0 0; padding-left: 18px; color: #555;">
                                                <li>Open your GCash app.</li>
                                                <li>Tap <strong>Scan QR</strong>.</li>
                                                <li>Scan the QR code above.</li>
                                                <li>Enter the amount: <strong>$<?php echo number_format($payment['amount'], 2); ?></strong>.</li>
                                                <li>Confirm payment.</li>
                                            </ol>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php elseif (($payment['status'] == 'pending' || $payment['status'] == 'overdue') && !$payment['qrcode_image']): ?>
                            <tr>
                                <td colspan="7" style="background: #fff3cd; padding: 15px; border: 1px solid #ffeeba; border-radius: 8px; color: #856404;">
                                    <strong>GCash QR not available yet.</strong> The assigned driver has not uploaded a GCash QR code. Please contact the driver for payment details.
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No payment records found.</p>
        <?php endif; ?>

        <div class="payment-info">
            <h2>Payment Information</h2>
            <p><strong>Monthly Fee:</strong> $50 per student</p>
            <p><strong>Payment Methods:</strong> GCash QR, Credit Card, Bank Transfer, Cash</p>
            <p><strong>Due Date:</strong> 1st of each month</p>
            <p><em>Note: This is a demo system. In production, payments would be processed through a secure payment gateway.</em></p>
        </div>
    </div>

    <style>
        .payment-info {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            border: 1px solid #ddd;
        }
        .payment-info h2 {
            margin-top: 0;
            color: #333;
        }
        .payment-info p {
            margin: 10px 0;
            color: #666;
        }
    </style>
</body>
</html>