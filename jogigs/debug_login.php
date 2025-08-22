<?php
// Debug login process
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Login Process</h2>";

try {
    require_once 'includes/config.php';
    require_once 'includes/functions.php';
    require_once 'includes/auth.php';
    
    echo "<h3>1. Database Connection Test</h3>";
    echo "✅ Database connected successfully<br>";
    
    echo "<h3>2. Check if jogigs_users table exists</h3>";
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'jogigs_users'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "✅ jogigs_users table exists<br>";
    } else {
        echo "❌ jogigs_users table does not exist<br>";
        exit;
    }
    
    echo "<h3>3. Check if admin user exists</h3>";
    $stmt = $pdo->prepare("SELECT * FROM jogigs_users WHERE email = ?");
    $stmt->execute(['admin@jogigs.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ Admin user exists - ID: " . $user['id'] . ", Status: " . $user['status'] . "<br>";
        
        echo "<h3>4. Test password verification</h3>";
        $password_valid = password_verify('admin123', $user['password']);
        echo "Password verification: " . ($password_valid ? '✅ PASSED' : '❌ FAILED') . "<br>";
        
        echo "<h3>5. Test get_user_by_email function</h3>";
        $test_user = get_user_by_email('admin@jogigs.com');
        if ($test_user) {
            echo "✅ get_user_by_email works - Found user: " . $test_user['email'] . "<br>";
        } else {
            echo "❌ get_user_by_email failed<br>";
        }
        
        echo "<h3>6. Test login_user function</h3>";
        $result = login_user('admin@jogigs.com', 'admin123');
        echo "Login result: <pre>" . print_r($result, true) . "</pre>";
        
        if ($result['success']) {
            echo "✅ Login successful!<br>";
            echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
            echo "Session user_email: " . ($_SESSION['user_email'] ?? 'NOT SET') . "<br>";
        } else {
            echo "❌ Login failed: " . $result['message'] . "<br>";
        }
        
    } else {
        echo "❌ Admin user does not exist<br>";
        echo "<p><a href='create_admin.php'>Create Admin User</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>

