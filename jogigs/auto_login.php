<?php
// Temporary auto-login script for testing
session_start();

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Auto-login the admin user
$result = login_user('admin@jogigs.com', 'admin123');

if ($result['success']) {
    echo "<h2>✅ Auto-Login Successful!</h2>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "User Email: " . $_SESSION['user_email'] . "<br>";
    echo "<p>You are now logged in. You can access the dashboard:</p>";
    echo "<a href='dashboard.php' style='display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a>";
} else {
    echo "<h2>❌ Auto-Login Failed</h2>";
    echo "Error: " . $result['message'] . "<br>";
}

echo "<h3>Session Data</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
?>
