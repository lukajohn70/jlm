<?php
// Force-create admin user and test setup
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'includes/config.php';
    echo "<h2>Force Creating Admin User</h2>";
    
    // Ensure jogigs_users table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS jogigs_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        skills TEXT,
        hourly_rate DECIMAL(10,2) DEFAULT 0.00,
        bio TEXT,
        profile_image VARCHAR(255),
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        email_verified BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    echo "<p>✅ jogigs_users table created/verified</p>";
    
    // Remove any existing admin user
    $pdo->prepare("DELETE FROM jogigs_users WHERE email = ?")->execute(['admin@jogigs.com']);
    echo "<p>✅ Removed existing admin user</p>";
    
    // Insert admin user with known hash
    $hashed_password = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("INSERT INTO jogigs_users (email, password, first_name, last_name, status, email_verified) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['admin@jogigs.com', $hashed_password, 'Admin', 'User', 'active', 1]);
    echo "<p>✅ Admin user created. Email: admin@jogigs.com, Password: admin123</p>";
    
    // Verify the user was created
    $stmt = $pdo->prepare("SELECT * FROM jogigs_users WHERE email = ?");
    $stmt->execute(['admin@jogigs.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p>✅ User verified in database - ID: " . $user['id'] . ", Status: " . $user['status'] . "</p>";
        
        // Test password verification
        $password_valid = password_verify('admin123', $user['password']);
        echo "<p>✅ Password verification test: " . ($password_valid ? 'PASSED' : 'FAILED') . "</p>";
        
        // Test login function
        require_once 'includes/functions.php';
        require_once 'includes/auth.php';
        
        $result = login_user('admin@jogigs.com', 'admin123');
        echo "<h3>Login Test Result:</h3>";
        echo '<pre>' . print_r($result, true) . '</pre>';
        
        if ($result['success']) {
            echo "<p>✅ Login function works correctly</p>";
        } else {
            echo "<p>❌ Login function failed: " . $result['message'] . "</p>";
        }
    } else {
        echo "<p>❌ Failed to create admin user</p>";
    }
    
    echo "<p><a href='index.php'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?> 