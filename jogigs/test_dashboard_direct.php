<?php
// Direct test of dashboard.php functionality
session_start();

echo "<h2>Direct Dashboard Test</h2>";

try {
    // Include all required files
    require_once 'includes/config.php';
    require_once 'includes/functions.php';
    require_once 'includes/auth.php';
    
    echo "<h3>1. Session Status</h3>";
    echo "Session ID: " . session_id() . "<br>";
    echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";
    
    echo "<h3>2. Authentication Status</h3>";
    echo "is_logged_in(): " . (is_logged_in() ? 'TRUE' : 'FALSE') . "<br>";
    
    if (is_logged_in()) {
        echo "User ID: " . $_SESSION['user_id'] . "<br>";
        echo "User Email: " . $_SESSION['user_email'] . "<br>";
        
        echo "<h3>3. Session Validation</h3>";
        $validation_result = validate_session();
        echo "validate_session(): " . ($validation_result ? 'TRUE' : 'FALSE') . "<br>";
        
        echo "<h3>4. Current User</h3>";
        $current_user = get_current_jogigs_user();
        if ($current_user) {
            echo "✅ Current user found: " . $current_user['email'] . "<br>";
        } else {
            echo "❌ No current user found<br>";
        }
        
        echo "<h3>5. Testing require_auth()</h3>";
        // Capture any output from require_auth
        ob_start();
        require_auth();
        $auth_output = ob_get_clean();
        
        if (empty($auth_output)) {
            echo "✅ require_auth() passed (no redirect)<br>";
        } else {
            echo "❌ require_auth() failed - Output: " . htmlspecialchars($auth_output) . "<br>";
        }
        
        echo "<h3>6. Testing Dashboard Functions</h3>";
        
        // Test calculate_total_earnings
        try {
            $total_earnings = calculate_total_earnings($_SESSION['user_id']);
            echo "Total earnings: " . $total_earnings . "<br>";
        } catch (Exception $e) {
            echo "❌ calculate_total_earnings error: " . $e->getMessage() . "<br>";
        }
        
        // Test get_user_earnings
        try {
            $recent_earnings = get_user_earnings($_SESSION['user_id'], 5);
            echo "Recent earnings count: " . count($recent_earnings) . "<br>";
        } catch (Exception $e) {
            echo "❌ get_user_earnings error: " . $e->getMessage() . "<br>";
        }
        
        // Test get_jobs_for_freelancer
        try {
            $available_jobs = get_jobs_for_freelancer(5);
            echo "Available jobs count: " . count($available_jobs) . "<br>";
        } catch (Exception $e) {
            echo "❌ get_jobs_for_freelancer error: " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "❌ User is not logged in<br>";
        echo "<p><a href='index.php'>Go to login page</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Critical Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}

echo "<h3>7. Database Connection Test</h3>";
try {
    $stmt = $pdo->prepare("SELECT 1");
    $stmt->execute();
    echo "✅ Database connection working<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}
?>
