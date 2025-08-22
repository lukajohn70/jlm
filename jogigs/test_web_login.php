<?php
// Test web login process
session_start();

echo "<h2>Testing Web Login Process</h2>";

try {
    require_once 'includes/config.php';
    require_once 'includes/functions.php';
    require_once 'includes/auth.php';
    
    // Simulate POST data
    $_POST['email'] = 'admin@jogigs.com';
    $_POST['password'] = 'admin123';
    
    echo "<h3>1. Simulating Login Form Submission</h3>";
    echo "Email: " . $_POST['email'] . "<br>";
    echo "Password length: " . strlen($_POST['password']) . "<br>";
    
    // Process login
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo "❌ Empty email or password<br>";
    } else {
        $result = login_user($email, $password);
        
        if ($result['success']) {
            echo "✅ Login successful!<br>";
            echo "User ID: " . $_SESSION['user_id'] . "<br>";
            echo "User Email: " . $_SESSION['user_email'] . "<br>";
            
            echo "<h3>2. Testing Dashboard Access</h3>";
            if (is_logged_in()) {
                echo "✅ User is logged in<br>";
                
                // Test dashboard functions
                $user = get_current_jogigs_user();
                if ($user) {
                    echo "✅ Current user: " . $user['email'] . "<br>";
                    
                    // Test require_auth
                    ob_start();
                    require_auth();
                    $auth_output = ob_get_clean();
                    
                    if (empty($auth_output)) {
                        echo "✅ require_auth() passed<br>";
                        echo "<a href='dashboard.php' target='_blank'>Open Dashboard</a><br>";
                    } else {
                        echo "❌ require_auth() failed<br>";
                    }
                } else {
                    echo "❌ No current user found<br>";
                }
            } else {
                echo "❌ User is not logged in<br>";
            }
        } else {
            echo "❌ Login failed: " . $result['message'] . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h3>3. Session Data</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
?>
