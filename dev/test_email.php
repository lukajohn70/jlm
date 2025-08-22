<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/email_utils.php';

echo "<h2>Email Test Results</h2>";

// Test 1: Check if PHPMailer is loaded
echo "<h3>1. PHPMailer Check:</h3>";
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "✅ PHPMailer is loaded successfully<br>";
} else {
    echo "❌ PHPMailer is NOT loaded<br>";
}

// Test 2: Check email configuration
echo "<h3>2. Email Configuration:</h3>";
echo "SMTP Host: " . (defined('SMTP_HOST') ? SMTP_HOST : 'NOT DEFINED') . "<br>";
echo "SMTP Port: " . (defined('SMTP_PORT') ? SMTP_PORT : 'NOT DEFINED') . "<br>";
echo "SMTP Username: " . (defined('SMTP_USERNAME') ? SMTP_USERNAME : 'NOT DEFINED') . "<br>";
echo "SMTP Password: " . (defined('SMTP_PASSWORD') ? 'SET' : 'NOT SET') . "<br>";

// Test 3: Try to send a test email
echo "<h3>3. Test Email Send:</h3>";
$testEmail = 'test@example.com'; // Replace with your email for testing
$result = sendEmail($testEmail, 'Test Email from JLM', 'This is a test email to verify email functionality is working.');

if ($result) {
    echo "✅ Test email sent successfully to $testEmail<br>";
} else {
    echo "❌ Test email failed to send<br>";
}

// Test 4: Check error logs
echo "<h3>4. Recent Error Logs:</h3>";
$logFile = ini_get('error_log');
if ($logFile && file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $recentLogs = array_slice($lines, -10); // Last 10 lines
    echo "<pre>" . implode("\n", $recentLogs) . "</pre>";
} else {
    echo "No error log found or accessible<br>";
}

echo "<br><strong>Note:</strong> If emails are not working, check your SMTP settings in config/email_utils.php";
?> 