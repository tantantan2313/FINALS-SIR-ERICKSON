# Auto-Send Notification System Documentation

## Overview
The auto-send notification system automatically sends notifications to parents, drivers, and administrators based on system events and schedules. Notifications are sent via both email and in-app notifications.

## Features

### 1. **Trip Notifications**
- Pickup alerts when driver is near
- In-transit notifications
- Safe dropoff confirmations
- Trip delay alerts
- Rating request reminders
- Driver trip assignments
- Weekly trip summaries for parents

### 2. **Payment Notifications**
- New monthly bill notifications
- Payment due reminders (7 days before)
- Overdue payment alerts
- Payment received confirmations
- Monthly billing summaries

### 3. **Admin Notifications**
- New driver applications
- Driver approval/rejection updates
- New student registrations
- New parent registrations
- System alerts
- Daily system reports
- Weekly system reports
- Monthly system reports

### 4. **In-App Notifications**
- Notification bell with unread count
- Notification panel in dashboard
- Full notification history page
- Mark as read functionality
- Delete notifications

## Installation & Setup

### Step 1: Database Setup
Run the migration script to update your database:
```
Open browser and visit: http://localhost/Traysikel/setup_notifications.php
```

This will:
- Add `title` column to notifications table
- Add `data` column (JSON) to notifications table
- Create `notification_log` table for tracking sent notifications

### Step 2: Include Notification Components

#### In Dashboard Headers:
Add this line to include the notification bell in your dashboard:

```php
<?php include __DIR__ . '/../includes/notification_display.php'; ?>
```

#### Add to Your Page (usually in the header/navbar):
```html
<!-- Notification bell will appear here -->
```

### Step 3: Set Up Cron Job

#### For Linux/Mac:
Add to crontab: `crontab -e`
```bash
*/5 * * * * curl http://localhost/Traysikel/scheduler/notification_scheduler.php
```

#### For Windows Task Scheduler:
1. Open Task Scheduler
2. Create Basic Task
3. Set trigger: Every 5 minutes
4. Action: Start a Program
5. Program: `C:\xampp\php\php.exe`
6. Arguments: `C:\xampp\htdocs\Traysikel\scheduler\notification_scheduler.php`

Or use curl:
```
Program: C:\Windows\System32\curl.exe
Arguments: http://localhost/Traysikel/scheduler/notification_scheduler.php
```

## Usage Examples

### Sending Trip Notifications

```php
require_once 'config.php';
require_once 'includes/NotificationManager.php';
require_once 'includes/TripNotifications.php';

$notificationManager = new NotificationManager($conn);
$tripNotifications = new TripNotifications($conn, $notificationManager);

// When a student is picked up
$tripNotifications->notifyPickup($tripId, $driverId, $studentId);

// When trip status changes to in-transit
$tripNotifications->notifyInTransit($tripId, $studentId);

// When student is dropped off
$tripNotifications->notifyDropoff($tripId, $studentId);

// Notify parent of delay
$tripNotifications->notifyDelay($tripId, $studentId, 15); // 15 minutes delay

// Request rating after trip
$tripNotifications->notifyRatingRequest($tripId, $studentId);

// Notify driver of new trip
$tripNotifications->notifyDriverNewTrip($tripId, $driverId);

// Send weekly summary to parent
$tripNotifications->sendWeeklyTripSummary($parentId);
```

### Sending Payment Notifications

```php
require_once 'includes/PaymentNotifications.php';

$paymentNotifications = new PaymentNotifications($conn, $notificationManager);

// Notify new bill
$paymentNotifications->notifyNewBill($paymentId);

// Send payment reminder
$paymentNotifications->notifyPaymentDueReminder($paymentId);

// Notify overdue payment
$paymentNotifications->notifyOverduePayment($paymentId);

// Confirm payment received
$paymentNotifications->notifyPaymentReceived($paymentId);

// Send monthly billing summary
$paymentNotifications->sendMonthlyBillingSummary($parentId);

// Check and update overdue payments
$paymentNotifications->checkAndUpdateOverduePayments();
```

### Sending Admin Notifications

```php
require_once 'includes/AdminNotifications.php';

$adminNotifications = new AdminNotifications($conn, $notificationManager);

// New driver application
$adminNotifications->notifyNewDriverApplication($driverId, $userId);

// Driver approved
$adminNotifications->notifyDriverApproved($userId);

// Driver rejected
$adminNotifications->notifyDriverRejected($userId, 'License number invalid');

// New student registration
$adminNotifications->notifyNewStudentRegistration($studentId);

// New parent registration
$adminNotifications->notifyNewParentRegistration($userId);

// System alert
$adminNotifications->notifySystemAlert('high_overdue_payments', 
    'Multiple payments are overdue', 
    ['overdue_count' => 5, 'total_amount' => '₱10,000']
);

// Send reports
$adminNotifications->sendDailySystemReport();
$adminNotifications->sendWeeklySystemReport();
$adminNotifications->sendMonthlySystemReport();
```

## Integration Points

### In driver/update_trip.php:
When updating trip status, send notifications:

```php
// When marking trip as picked_up
$tripNotifications->notifyPickup($tripId, $driverId, $studentId);

// When marking trip as in_transit
$tripNotifications->notifyInTransit($tripId, $studentId);

// When marking trip as dropped_off
$tripNotifications->notifyDropoff($tripId, $studentId);
```

### In generate_payments.php:
When creating monthly payments:

```php
// After creating payment
$paymentNotifications->notifyNewBill($paymentId);
```

### In admin/manage_drivers.php:
When approving/rejecting drivers:

```php
// When approving
$adminNotifications->notifyDriverApproved($driverId);

// When rejecting
$adminNotifications->notifyDriverRejected($driverId, $rejectionReason);
```

### In register.php:
When new user registers:

```php
// For parent registration
$adminNotifications->notifyNewParentRegistration($userId);
```

### In parent/add_student.php:
When adding new student:

```php
// After creating student
$adminNotifications->notifyNewStudentRegistration($studentId);
```

## Notification Types

| Type | Recipients | Description |
|------|-----------|-------------|
| pickup | Parent, Driver | Student has been picked up |
| in_transit | Parent | Student is on the way |
| dropoff | Parent | Student has been dropped off |
| delay | Parent | Trip is delayed |
| new_bill | Parent | New monthly bill created |
| payment_reminder | Parent | Payment due in 7 days |
| overdue_payment | Parent | Payment is overdue |
| payment_received | Parent | Payment confirmed |
| new_driver_app | Admin | New driver application submitted |
| driver_approved | Driver | Driver approved |
| driver_rejected | Driver | Driver rejected |
| new_student | Admin | New student registered |
| new_parent | Admin | New parent registered |
| system_alert | Admin | System alert |
| daily_report | Admin | Daily statistics |
| weekly_report | Admin | Weekly statistics |
| monthly_report | Admin | Monthly statistics |

## Automatic Schedules

The scheduler automatically sends notifications at:

- **Every 5-15 minutes**: 
  - Trip pickups/dropoffs
  - Payment reminders
  - Overdue updates

- **Daily at 10:00 PM**: 
  - Daily system reports to admin

- **Every Monday at 8:00 AM**: 
  - Weekly system reports to admin
  - Weekly trip summaries to parents (Sunday 6 PM)

- **1st of month at 9:00 AM**: 
  - Monthly system reports to admin
  - Monthly billing summaries to all parents

## API Endpoints

### Get Notifications
```
GET /api/get_notifications.php
Returns: JSON array of user's notifications
```

### Mark as Read
```
POST /api/mark_notification_read.php
Body: { "notification_id": 123 }
Returns: { "success": true }
```

## Files Created

- `includes/NotificationManager.php` - Core notification class
- `includes/TripNotifications.php` - Trip-related notifications
- `includes/PaymentNotifications.php` - Payment-related notifications
- `includes/AdminNotifications.php` - Admin-related notifications
- `scheduler/notification_scheduler.php` - Automated scheduler
- `includes/notification_display.php` - UI component for notifications
- `api/get_notifications.php` - Get notifications endpoint
- `api/mark_notification_read.php` - Mark as read endpoint
- `parent/notifications.php` - Parent notifications page
- `driver/notifications.php` - Driver notifications page
- `admin/notifications.php` - Admin notifications page
- `setup_notifications.php` - Database migration script

## Email Configuration

Emails are sent using the credentials in `mail_config.php`. Make sure to:

1. Use an app-specific password for Gmail (not your main password)
2. Enable Less Secure Apps or use App Passwords
3. Test email configuration with `test_send.php`

## Testing

1. **Test Database Migration**: 
   Visit `http://localhost/Traysikel/setup_notifications.php`

2. **Test Scheduler**: 
   Visit `http://localhost/Traysikel/scheduler/notification_scheduler.php`

3. **Check Notification Log**: 
   Query the database: `SELECT * FROM notification_log;`

4. **View Notifications**: 
   User notifications page: `/parent/notifications.php`

## Troubleshooting

### Notifications not sending?
1. Check `scheduler/notification_scheduler.log`
2. Verify cron job is running
3. Check email configuration in `mail_config.php`
4. Verify database has notification tables

### Missing database columns?
Run: `http://localhost/Traysikel/setup_notifications.php`

### Emails not sending?
1. Test with `test_send.php`
2. Check credentials in `mail_config.php`
3. Check server's email configuration
4. Enable debugging in NotificationManager

## Support

For issues or feature requests, check the log file at:
`/scheduler/notification_scheduler.log`
