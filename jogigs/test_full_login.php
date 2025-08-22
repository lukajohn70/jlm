<?php
// Test full login process and dashboard access
session_start();

echo "<h2>Testing Full Login Process</h2>";

try {
    require_once 'includes/config.php';
    require_once 'includes/functions.php';
    require_once 'includes/auth.php';
    
    echo "<h3>1. Initial Session State</h3>";
    echo "Session ID: " . session_id() . "<br>";
    echo "Logged in: " . (is_logged_in() ? 'Yes' : 'No') . "<br>";
    
    if (!is_logged_in()) {
        echo "<h3>2. Attempting Login</h3>";
        $result = login_user('admin@jogigs.com', 'admin123');
        echo "Login result: " . ($result['success'] ? '✅ SUCCESS' : '❌ FAILED') . "<br>";
        
        if ($result['success']) {
            echo "User ID: " . $_SESSION['user_id'] . "<br>";
            echo "User Email: " . $_SESSION['user_email'] . "<br>";
            
            echo "<h3>3. Testing Session Validation</h3>";
            $validation_result = validate_session();
            echo "Session validation: " . ($validation_result ? '✅ PASSED' : '❌ FAILED') . "<br>";
            
            echo "<h3>4. Testing Dashboard Access</h3>";
            if (is_logged_in() && $validation_result) {
                echo "✅ Dashboard access should work<br>";
                echo "<a href='dashboard.php' target='_blank'>Open Dashboard in New Tab</a><br>";
                
                // Test the require_auth function
                echo "<h3>5. Testing require_auth Function</h3>";
                ob_start();
                require_auth();
                $auth_output = ob_get_clean();
                echo "require_auth result: " . (empty($auth_output) ? '✅ PASSED (no redirect)' : '❌ FAILED (redirected)') . "<br>";
                
            } else {
                echo "❌ Dashboard access will fail<br>";
            }
        } else {
            echo "Login failed: " . $result['message'] . "<br>";
        }
    } else {
        echo "<h3>2. Already Logged In</h3>";
        echo "User ID: " . $_SESSION['user_id'] . "<br>";
        echo "User Email: " . $_SESSION['user_email'] . "<br>";
        
        echo "<h3>3. Testing Dashboard Access</h3>";
        echo "<a href='dashboard.php' target='_blank'>Open Dashboard in New Tab</a><br>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}

echo "<h3>6. Session Data</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
?>





