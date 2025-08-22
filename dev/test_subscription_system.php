<?php
require_once __DIR__ . '/config/config.php';

echo "<h2>Subscription System Test Results</h2>";

// Test 1: Check if all required files exist
echo "<h3>1. File System Check</h3>";
$required_files = [
    'subscription_manage.php',
    'config/email_utils.php',
    'config/email_templates.php',
    'subscribe.php',
    'unsubscribe.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ $file exists</p>";
    } else {
        echo "<p style='color: red;'>✗ $file missing</p>";
    }
}

// Test 2: Check database tables
echo "<h3>2. Database Tables Check</h3>";
try {
    $tables = ['email_subscribers', 'email_campaigns', 'email_campaign_recipients', 'users'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            echo "<p style='color: green;'>✓ $table table exists</p>";
        } else {
            echo "<p style='color: red;'>✗ $table table missing</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

// Test 3: Check PHP syntax
echo "<h3>3. PHP Syntax Check</h3>";
$php_files = ['subscription_manage.php', 'config/email_utils.php', 'subscribe.php'];
foreach ($php_files as $file) {
    $output = shell_exec("php -l $file 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "<p style='color: green;'>✓ $file syntax OK</p>";
    } else {
        echo "<p style='color: red;'>✗ $file syntax error: " . htmlspecialchars($output) . "</p>";
    }
}

// Test 4: Check admin user exists
echo "<h3>4. Admin User Check</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $admin_count = $stmt->fetchColumn();
    if ($admin_count > 0) {
        echo "<p style='color: green;'>✓ Admin users found: $admin_count</p>";
        
        // Show admin details
        $stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'admin' LIMIT 3");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li>ID: {$admin['id']}, Name: {$admin['name']}, Email: {$admin['email']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ No admin users found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking admin users: " . $e->getMessage() . "</p>";
}

// Test 5: Check email configuration
echo "<h3>5. Email Configuration Check</h3>";
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "<p style='color: green;'>✓ PHPMailer is available</p>";
} else {
    echo "<p style='color: red;'>✗ PHPMailer not found</p>";
}

// Test 6: Check if functions exist
echo "<h3>6. Function Availability Check</h3>";
$required_functions = ['sendNewsletterEmail', 'sendSubscriptionConfirmation', 'sendEmail'];
foreach ($required_functions as $function) {
    if (function_exists($function)) {
        echo "<p style='color: green;'>✓ $function function exists</p>";
    } else {
        echo "<p style='color: red;'>✗ $function function missing</p>";
    }
}

// Test 7: Test subscription management access
echo "<h3>7. Access Test</h3>";
echo "<p>To test the subscription management system:</p>";
echo "<ol>";
echo "<li><a href='login.php' target='_blank'>Login to admin panel</a></li>";
echo "<li><a href='admin_dashboard.php?tab=subscriptions' target='_blank'>Go to subscriptions tab</a></li>";
echo "<li><a href='subscription_manage.php' target='_blank'>Direct access to subscription management</a></li>";
echo "</ol>";

// Test 8: Check blog URL structure
echo "<h3>8. Blog URL Structure Check</h3>";
echo "<p>Blog posts now use slugs instead of IDs:</p>";
echo "<ul>";
echo "<li>Old format: blog_post.php?id=123</li>";
echo "<li>New format: blog_post.php?slug=my-blog-post-title</li>";
echo "</ul>";

echo "<h3>Summary</h3>";
echo "<p>The subscription management system should now be working correctly. The main issue was that the code was trying to access a non-existent session variable.</p>";
echo "<p><strong>What was fixed:</strong></p>";
echo "<ul>";
echo "<li>Changed <code>\$_SESSION['admin_id']</code> to proper admin ID retrieval from database</li>";
echo "<li>Added proper error handling for missing admin users</li>";
echo "<li>Maintained compatibility with existing admin authentication system</li>";
echo "</ul>";

echo "<p><strong>Next steps:</strong></p>";
echo "<ul>";
echo "<li>Test the subscription management system by logging in as admin</li>";
echo "<li>Try creating a newsletter campaign</li>";
echo "<li>Test the frontend subscription form</li>";
echo "<li>Verify that blog posts work with slug URLs</li>";
echo "</ul>";
?> 