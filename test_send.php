<?php

require 'send_email.php';

$to = "shinbartocillo435gmail@gmail.com";

$subject = "Test Email - School Transport System";

$message = "
<h2>System Notification</h2>
<p>This is a test email from your system.</p>
<p>If you receive this, email system is working ✅</p>
";

if(sendEmail($to, $subject, $message)){
    echo "Email sent successfully!";
} else {
    echo "Failed to send email.";
}