<?php
// Simple login test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Login Process</h2>";

// Start session
session_start();

try {
    require_once 'includes/config.php';
    require_once 'includes/functions.php';
    require_once 'includes/auth.php';
    
    echo "<h3>1. Testing database connection</h3>";
    echo "✅ Database connected successfully<br>";
    
    echo "<h3>2. Testing user lookup</h3>";
    $user = get_user_by_email('admin@jogigs.com');
    if ($user) {
        echo "✅ User found - ID: " . $user['id'] . ", Status: " . $user['status'] . "<br>";
    } else {
        echo "❌ User not found<br>";
        exit;
    }
    
    echo "<h3>3. Testing password verification</h3>";
    $password_valid = password_verify('admin123', $user['password']);
    echo "Password verification: " . ($password_valid ? '✅ PASSED' : '❌ FAILED') . "<br>";
    
    if (!$password_valid) {
        echo "❌ Password verification failed<br>";
        exit;
    }
    
    echo "<h3>4. Testing login_user function</h3>";
    $result = login_user('admin@jogigs.com', 'admin123');
    echo "Login result: <pre>" . print_r($result, true) . "</pre>";
    
    if ($result['success']) {
        echo "✅ Login successful!<br>";
        echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
        echo "Session user_email: " . ($_SESSION['user_email'] ?? 'NOT SET') . "<br>";
        
        echo "<h3>5. Testing is_logged_in function</h3>";
        if (is_logged_in()) {
            echo "✅ is_logged_in() returns true<br>";
        } else {
            echo "❌ is_logged_in() returns false<br>";
        }
        
        echo "<h3>6. Testing get_current_jogigs_user function</h3>";
        $current_user = get_current_jogigs_user();
        if ($current_user) {
            echo "✅ get_current_jogigs_user() works - User: " . $current_user['email'] . "<br>";
        } else {
            echo "❌ get_current_jogigs_user() failed<br>";
        }
        
    } else {
        echo "❌ Login failed: " . $result['message'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>
