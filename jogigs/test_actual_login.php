<?php
// Test actual web login process
session_start();

echo "<h2>Testing Actual Web Login Process</h2>";

// Simulate the exact same process as index.php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is already logged in
if (is_logged_in()) {
    echo "<h3>✅ Already logged in!</h3>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "User Email: " . $_SESSION['user_email'] . "<br>";
    echo "<a href='dashboard.php'>Go to Dashboard</a><br>";
    exit();
}

// Simulate POST data (like from the login form)
$_POST['email'] = 'admin@jogigs.com';
$_POST['password'] = 'admin123';
$_SERVER['REQUEST_METHOD'] = 'POST'; // Simulate POST request

echo "<h3>1. Processing Login Form</h3>";
echo "Email: " . $_POST['email'] . "<br>";
echo "Password length: " . strlen($_POST['password']) . "<br>";

// Handle login form submission (same logic as index.php)
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error_message = 'Please enter both email and password.';
        echo "❌ " . $error_message . "<br>";
    } else {
        $result = login_user($email, $password);
        
        if ($result['success']) {
            echo "✅ Login successful!<br>";
            echo "User ID: " . $_SESSION['user_id'] . "<br>";
            echo "User Email: " . $_SESSION['user_email'] . "<br>";
            
            echo "<h3>2. Testing Redirect Logic</h3>";
            echo "✅ Should redirect to dashboard.php<br>";
            echo "<a href='dashboard.php'>Go to Dashboard</a><br>";
            
            echo "<h3>3. Testing Dashboard Access</h3>";
            if (is_logged_in()) {
                echo "✅ User is logged in<br>";
                
                // Test require_auth
                ob_start();
                require_auth();
                $auth_output = ob_get_clean();
                
                if (empty($auth_output)) {
                    echo "✅ require_auth() passed<br>";
                } else {
                    echo "❌ require_auth() failed<br>";
                }
            } else {
                echo "❌ User is not logged in<br>";
            }
        } else {
            $error_message = $result['message'];
            echo "❌ Login failed: " . $error_message . "<br>";
        }
    }
} else {
    echo "❌ No POST data received<br>";
}

echo "<h3>4. Session Data</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
?>
