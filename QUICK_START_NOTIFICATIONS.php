<?php
/**
 * QUICK START GUIDE
 * Auto-Send Notification System Setup
 */
?>

<!DOCTYPE html>
<html>
<head>
    <title>Auto-Send Notification System - Quick Start</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            max-width: 1000px; 
            margin: 0 auto; 
            padding: 20px;
            background: #f5f5f5;
        }
        h1 { color: #007bff; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #333; margin-top: 30px; }
        h3 { color: #555; }
        .step { 
            background: white; 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 5px;
            border-left: 4px solid #007bff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .code { 
            background: #f4f4f4; 
            border: 1px solid #ddd; 
            padding: 10px; 
            border-radius: 3px;
            font-family: monospace;
            overflow-x: auto;
            margin: 10px 0;
        }
        .success { color: #28a745; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .button { 
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            margin: 5px 0;
        }
        .button:hover { background: #0056b3; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 12px; 
            text-align: left;
        }
        th { background: #f4f4f4; font-weight: bold; }
    </style>
</head>
<body>

<h1>🔔 Auto-Send Notification System - Quick Start Guide</h1>

<div class="step">
    <h2>What You're Getting</h2>
    <p>A complete automated notification system that sends emails and in-app notifications for:</p>
    <ul>
        <li><strong>Trip Events</strong>: Pickups, in-transit, dropoffs, delays</li>
        <li><strong>Payment Events</strong>: New bills, reminders, overdue alerts, confirmations</li>
        <li><strong>Admin Events</strong>: New registrations, driver applications, system reports</li>
        <li><strong>Scheduled Reports</strong>: Daily, weekly, and monthly summaries</li>
    </ul>
</div>

<h2>📋 Installation Checklist</h2>

<div class="step">
    <h3>Step 1: Database Migration (5 minutes)</h3>
    <p class="warning">⚠️ Do this first!</p>
    
    <p>Visit this URL in your browser to update the database:</p>
    <div class="code">http://localhost/Traysikel/setup_notifications.php</div>
    
    <p>You should see a list of completed migrations. If all show ✓ (checkmarks), you're good to go.</p>
    
    <p><strong>What it does:</strong></p>
    <ul>
        <li>Adds 'title' column to notifications table</li>
        <li>Adds 'data' column (JSON) to notifications table</li>
        <li>Creates 'notification_log' table for tracking</li>
        <li>Updates notification types</li>
    </ul>
</div>

<div class="step">
    <h3>Step 2: Set Up Cron Job (5 minutes)</h3>
    <p class="warning">⚠️ This is important - without this, automated notifications won't be sent!</p>
    
    <p>Choose your operating system:</p>
    
    <h4>For Windows:</h4>
    <p>Use Windows Task Scheduler:</p>
    <ol>
        <li>Press <strong>Win + R</strong> and type <code>taskschd.msc</code></li>
        <li>Click "Create Basic Task"</li>
        <li>Name: "Traysikel Notification Scheduler"</li>
        <li>Trigger: Set to repeat every 5 minutes indefinitely</li>
        <li>Action: Start a program
            <ul>
                <li>Program: <code>C:\xampp\php\php.exe</code></li>
                <li>Arguments: <code>C:\xampp\htdocs\Traysikel\scheduler\notification_scheduler.php</code></li>
            </ul>
        </li>
        <li>Click Finish</li>
    </ol>
    
    <p><strong>Or using curl:</strong></p>
    <ol>
        <li>Open Task Scheduler</li>
        <li>New Basic Task</li>
        <li>Trigger: Every 5 minutes</li>
        <li>Action: Start program: <code>curl.exe</code></li>
        <li>Arguments: <code>http://localhost/Traysikel/scheduler/notification_scheduler.php</code></li>
    </ol>
    
    <h4>For Linux/Mac:</h4>
    <p>Edit crontab: <code>crontab -e</code></p>
    <p>Add this line:</p>
    <div class="code">*/5 * * * * curl http://localhost/Traysikel/scheduler/notification_scheduler.php</div>
    <p>This runs every 5 minutes.</p>
</div>

<div class="step">
    <h3>Step 3: Add Notifications to Dashboard (5 minutes)</h3>
    <p>Add the notification bell icon to your dashboards.</p>
    
    <p>Edit these files and add the line shown in the header/navbar section:</p>
    
    <h4>File: <code>parent/dashboard.php</code></h4>
    <p>Add in the HTML header or navbar:</p>
    <div class="code">&lt;?php include __DIR__ . '/../includes/notification_display.php'; ?&gt;</div>
    
    <h4>File: <code>driver/dashboard.php</code></h4>
    <p>Add in the HTML header or navbar:</p>
    <div class="code">&lt;?php include __DIR__ . '/../includes/notification_display.php'; ?&gt;</div>
    
    <h4>File: <code>admin/dashboard.php</code></h4>
    <p>Add in the HTML header or navbar:</p>
    <div class="code">&lt;?php include __DIR__ . '/../includes/notification_display.php'; ?&gt;</div>
</div>

<div class="step">
    <h3>Step 4: Integrate with Existing Files (10-15 minutes)</h3>
    <p>Add notification triggers when events happen in your application.</p>
    
    <p>See detailed integration guide: <code>INTEGRATION_GUIDE.php</code></p>
    
    <h4>Key Files to Update:</h4>
    <ul>
        <li><code>driver/update_trip.php</code> - Add trip notifications</li>
        <li><code>generate_payments.php</code> - Add payment notifications</li>
        <li><code>admin/manage_drivers.php</code> - Add driver approval notifications</li>
        <li><code>register.php</code> - Add registration notifications</li>
        <li><code>parent/add_student.php</code> - Add student registration notifications</li>
    </ul>
</div>

<h2>📧 Email Configuration</h2>

<div class="step">
    <p>Your email configuration is already set up in <code>mail_config.php</code>:</p>
    
    <table>
        <tr>
            <th>Setting</th>
            <th>Value</th>
        </tr>
        <tr>
            <td>Host</td>
            <td>smtp.gmail.com</td>
        </tr>
        <tr>
            <td>Port</td>
            <td>587</td>
        </tr>
        <tr>
            <td>From Email</td>
            <td>shinbartocillo435gmail@gmail.com</td>
        </tr>
    </table>
    
    <p class="success">✓ Configuration looks good!</p>
    
    <p><strong>To test email sending:</strong></p>
    <div class="code">http://localhost/Traysikel/test_send.php</div>
</div>

<h2>📁 Files Created/Modified</h2>

<div class="step">
    <table>
        <tr>
            <th>File Path</th>
            <th>Purpose</th>
        </tr>
        <tr>
            <td>includes/NotificationManager.php</td>
            <td>Core notification system</td>
        </tr>
        <tr>
            <td>includes/TripNotifications.php</td>
            <td>Trip-related notifications</td>
        </tr>
        <tr>
            <td>includes/PaymentNotifications.php</td>
            <td>Payment notifications</td>
        </tr>
        <tr>
            <td>includes/AdminNotifications.php</td>
            <td>Admin notifications</td>
        </tr>
        <tr>
            <td>scheduler/notification_scheduler.php</td>
            <td>Automated scheduler</td>
        </tr>
        <tr>
            <td>includes/notification_display.php</td>
            <td>Dashboard notification component</td>
        </tr>
        <tr>
            <td>api/get_notifications.php</td>
            <td>API endpoint</td>
        </tr>
        <tr>
            <td>api/mark_notification_read.php</td>
            <td>API endpoint</td>
        </tr>
        <tr>
            <td>parent/notifications.php</td>
            <td>Notifications page</td>
        </tr>
        <tr>
            <td>driver/notifications.php</td>
            <td>Notifications page</td>
        </tr>
        <tr>
            <td>admin/notifications.php</td>
            <td>Notifications page</td>
        </tr>
        <tr>
            <td>NOTIFICATIONS_README.md</td>
            <td>Full documentation</td>
        </tr>
    </table>
</div>

<h2>🧪 Testing</h2>

<div class="step">
    <h3>Test 1: Database</h3>
    <p>Visit: <a href="setup_notifications.php" class="button">Run Migration</a></p>
    
    <h3>Test 2: Scheduler</h3>
    <p>Visit: <a href="scheduler/notification_scheduler.php" class="button">Run Scheduler</a></p>
    
    <h3>Test 3: Email</h3>
    <p>Visit: <a href="test_send.php" class="button">Test Email</a></p>
    
    <h3>Test 4: View Notifications</h3>
    <p>After logging in, go to: <code>/parent/notifications.php</code></p>
    
    <h3>Test 5: Check Log File</h3>
    <p>View scheduler log: <code>scheduler/notification_scheduler.log</code></p>
</div>

<h2>⏰ Automatic Notification Schedule</h2>

<div class="step">
    <table>
        <tr>
            <th>Event</th>
            <th>Frequency</th>
            <th>Recipients</th>
        </tr>
        <tr>
            <td>Trip Pickups</td>
            <td>Every 5-15 minutes</td>
            <td>Parents</td>
        </tr>
        <tr>
            <td>Trip Status Updates</td>
            <td>Every 5-15 minutes</td>
            <td>Parents</td>
        </tr>
        <tr>
            <td>Payment Reminders</td>
            <td>Every 5-15 minutes</td>
            <td>Parents</td>
        </tr>
        <tr>
            <td>Overdue Alerts</td>
            <td>Every 5-15 minutes</td>
            <td>Parents</td>
        </tr>
        <tr>
            <td>Daily Report</td>
            <td>Daily at 10:00 PM</td>
            <td>Admin</td>
        </tr>
        <tr>
            <td>Weekly Report</td>
            <td>Monday 8:00 AM</td>
            <td>Admin</td>
        </tr>
        <tr>
            <td>Weekly Trip Summary</td>
            <td>Sunday 6:00 PM</td>
            <td>Parents</td>
        </tr>
        <tr>
            <td>Monthly Report</td>
            <td>1st of month 9:00 AM</td>
            <td>Admin</td>
        </tr>
        <tr>
            <td>Monthly Billing Summary</td>
            <td>1st of month 9:00 AM</td>
            <td>Parents</td>
        </tr>
    </table>
</div>

<h2>❓ FAQ</h2>

<div class="step">
    <h3>Q: Where do I see notifications?</h3>
    <p><strong>A:</strong> Click the 🔔 bell icon in the dashboard header. You can also go to your notifications page.</p>
    
    <h3>Q: Will I receive email notifications?</h3>
    <p><strong>A:</strong> Yes! Notifications are sent via both email and in-app. Make sure your email configuration is correct.</p>
    
    <h3>Q: How often do notifications send?</h3>
    <p><strong>A:</strong> The scheduler runs every 5 minutes by default. Adjust cron job frequency if needed.</p>
    
    <h3>Q: What if notifications aren't working?</h3>
    <p><strong>A:</strong> Check the log file at <code>scheduler/notification_scheduler.log</code></p>
    
    <h3>Q: Can I customize notification messages?</h3>
    <p><strong>A:</strong> Yes! Edit the notification classes in the <code>includes</code> folder.</p>
    
    <h3>Q: How do I disable notifications?</h3>
    <p><strong>A:</strong> Disable the cron job in Windows Task Scheduler or remove the crontab entry.</p>
</div>

<h2>📞 Support</h2>

<div class="step">
    <p>For detailed documentation, see:</p>
    <ul>
        <li><strong>Full Documentation:</strong> <code>NOTIFICATIONS_README.md</code></li>
        <li><strong>Integration Guide:</strong> <code>INTEGRATION_GUIDE.php</code></li>
        <li><strong>Error Log:</strong> <code>scheduler/notification_scheduler.log</code></li>
    </ul>
</div>

</body>
</html>
