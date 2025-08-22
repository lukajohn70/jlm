<?php
// Debug dashboard access
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Dashboard Access</h2>";

try {
    require_once 'includes/config.php';
    require_once 'includes/functions.php';
    require_once 'includes/auth.php';
    
    echo "<h3>1. Session Information</h3>";
    echo "Session ID: " . session_id() . "<br>";
    echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";
    
    echo "<h3>2. Login Status</h3>";
    if (is_logged_in()) {
        echo "✅ User is logged in<br>";
        echo "User ID: " . $_SESSION['user_id'] . "<br>";
        echo "User Email: " . $_SESSION['user_email'] . "<br>";
    } else {
        echo "❌ User is not logged in<br>";
    }
    
    echo "<h3>3. Session Validation</h3>";
    $validation_result = validate_session();
    echo "Session validation: " . ($validation_result ? '✅ PASSED' : '❌ FAILED') . "<br>";
    
    echo "<h3>4. Current User</h3>";
    $current_user = get_current_jogigs_user();
    if ($current_user) {
        echo "✅ Current user: " . $current_user['email'] . "<br>";
        echo "User details: <pre>" . print_r($current_user, true) . "</pre>";
    } else {
        echo "❌ No current user<br>";
    }
    
    echo "<h3>5. Database Session Check</h3>";
    if (is_logged_in()) {
        $session_id = session_id();
        $stmt = $pdo->prepare("
            SELECT * FROM jogigs_sessions 
            WHERE user_id = ? AND session_id = ? AND expires_at > NOW()
        ");
        $stmt->execute([$_SESSION['user_id'], $session_id]);
        $session_record = $stmt->fetch();
        
        if ($session_record) {
            echo "✅ Database session record exists<br>";
            echo "Session expires: " . $session_record['expires_at'] . "<br>";
        } else {
            echo "❌ No database session record found<br>";
        }
    }
    
    echo "<h3>6. Test Dashboard Access</h3>";
    if (is_logged_in() && $validation_result) {
        echo "✅ Dashboard access should work<br>";
        echo "<a href='dashboard.php'>Go to Dashboard</a><br>";
    } else {
        echo "❌ Dashboard access will fail<br>";
        echo "<a href='index.php'>Go to Login</a><br>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>
