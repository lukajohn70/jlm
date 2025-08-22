<?php
// Test session validation
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Session Validation</h2>";

// Start session
session_start();

try {
    require_once 'includes/config.php';
    require_once 'includes/functions.php';
    require_once 'includes/auth.php';
    
    echo "<h3>1. Testing session validation</h3>";
    
    if (is_logged_in()) {
        echo "✅ User is logged in - User ID: " . $_SESSION['user_id'] . "<br>";
        
        $validation_result = validate_session();
        echo "Session validation result: " . ($validation_result ? '✅ PASSED' : '❌ FAILED') . "<br>";
        
        if ($validation_result) {
            echo "✅ Session validation successful<br>";
            
            $current_user = get_current_jogigs_user();
            if ($current_user) {
                echo "✅ Current user: " . $current_user['email'] . "<br>";
            } else {
                echo "❌ Failed to get current user<br>";
            }
        } else {
            echo "❌ Session validation failed<br>";
        }
    } else {
        echo "❌ User is not logged in<br>";
        echo "<p><a href='index.php'>Go to login page</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>
