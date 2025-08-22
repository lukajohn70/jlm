<?php
// Simple database connection test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Simple Database Test</h2>";

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gwylvxeo_jlm');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    // Try to connect to the database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "<p>✅ Connected to database: " . DB_NAME . "</p>";
    
    // Check if jogigs_users table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'jogigs_users'");
    $stmt->execute();
    
    if ($stmt->fetch()) {
        echo "<p>✅ jogigs_users table exists</p>";
        
        // Check if admin user exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jogigs_users WHERE email = ?");
        $stmt->execute(['admin@jogigs.com']);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo "<p>✅ Admin user exists</p>";
            
            // Get admin user details
            $stmt = $pdo->prepare("SELECT * FROM jogigs_users WHERE email = ?");
            $stmt->execute(['admin@jogigs.com']);
            $admin = $stmt->fetch();
            
            echo "<p>Admin user details:</p>";
            echo "<ul>";
            echo "<li>ID: " . $admin['id'] . "</li>";
            echo "<li>Name: " . $admin['first_name'] . " " . $admin['last_name'] . "</li>";
            echo "<li>Status: " . $admin['status'] . "</li>";
            echo "<li>Email Verified: " . ($admin['email_verified'] ? 'Yes' : 'No') . "</li>";
            echo "</ul>";
            
            // Test password verification
            if (password_verify('admin123', $admin['password'])) {
                echo "<p>✅ Password verification works</p>";
            } else {
                echo "<p>❌ Password verification failed</p>";
            }
        } else {
            echo "<p>❌ Admin user does not exist</p>";
        }
    } else {
        echo "<p>❌ jogigs_users table does not exist</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
}
?> 