<?php
// JLM Freelancer Web App - Configuration File

// Database Configuration - Using standardized JLM environment variables
require_once __DIR__ . '/../../config/config.php';
// The above requires config.php which loads .env and defines constants like DB_HOST, DB_NAME, etc.

// Application Configuration
define('APP_NAME', 'JoGigs');
define('APP_URL', 'http://localhost/jlm/jogigs');
define('APP_VERSION', '1.0.0');

// Security Configuration
define('SECRET_KEY', 'jlm_freelancer_secret_key_2024');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_COST', 12); // bcrypt cost

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Email Configuration (if needed)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');

// Error Reporting - Only set if not already set
if (!ini_get('display_errors')) {
    ini_set('display_errors', 1);
}
if (!ini_get('log_errors')) {
    ini_set('log_errors', 1);
}
if (!ini_get('error_log')) {
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// Timezone
date_default_timezone_set('Africa/Lagos');

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie path to include the entire JLM directory
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path' => '/jlm/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Database Connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Check if JoGigs tables exist, if not create them
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'jogigs_users'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        create_jogigs_tables($pdo);
    }
    
} catch (PDOException $e) {
    // If database doesn't exist, try to connect without database name
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        
        // Try to use the database
        $pdo->exec("USE " . DB_NAME);
        
        // Create JoGigs tables
        create_jogigs_tables($pdo);
        
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Create necessary directories
$directories = [
    __DIR__ . '/../uploads',
    __DIR__ . '/../logs',
    __DIR__ . '/../cache'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Function to create JoGigs tables in existing JLM database
function create_jogigs_tables($pdo) {
    try {
        // JoGigs Users table (separate from main JLM users) - Create first
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS jogigs_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                phone VARCHAR(20),
                skills TEXT,
                hourly_rate DECIMAL(10,2) DEFAULT 0.00,
                currency ENUM('NGN', 'USD', 'GBP', 'GHS') DEFAULT 'NGN',
                bio TEXT,
                profile_image VARCHAR(255),
                status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
                email_verified BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // JoGigs Jobs table - Create second
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS jogigs_jobs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                client_id INT,
                budget_min DECIMAL(10,2),
                budget_max DECIMAL(10,2),
                skills_required TEXT,
                status ENUM('open', 'in_progress', 'completed', 'cancelled') DEFAULT 'open',
                deadline DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // JoGigs Sessions table - Create third (no foreign keys)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS jogigs_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_id VARCHAR(255) UNIQUE NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES jogigs_users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // JoGigs Applications table - Create fourth (references users and jobs)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS jogigs_applications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                job_id INT NOT NULL,
                freelancer_id INT NOT NULL,
                proposal TEXT NOT NULL,
                bid_amount DECIMAL(10,2),
                status ENUM('pending', 'accepted', 'rejected', 'withdrawn') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (job_id) REFERENCES jogigs_jobs(id) ON DELETE CASCADE,
                FOREIGN KEY (freelancer_id) REFERENCES jogigs_users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // JoGigs Earnings table - Create fifth (references users and jobs)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS jogigs_earnings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                job_id INT,
                amount DECIMAL(10,2) NOT NULL,
                description VARCHAR(255),
                type ENUM('payment', 'bonus', 'refund') DEFAULT 'payment',
                status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES jogigs_users(id) ON DELETE CASCADE,
                FOREIGN KEY (job_id) REFERENCES jogigs_jobs(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // JoGigs Activity Logs table - Create last (references users)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS jogigs_activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                action VARCHAR(100) NOT NULL,
                description TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES jogigs_users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // JoGigs Payout Accounts table - Create after activity logs
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS jogigs_payout_accounts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                account_type ENUM('bank', 'paypal', 'stripe', 'mobile_money') NOT NULL,
                account_name VARCHAR(255) NOT NULL,
                account_number VARCHAR(255),
                bank_name VARCHAR(255),
                bank_code VARCHAR(50),
                paypal_email VARCHAR(255),
                stripe_account_id VARCHAR(255),
                mobile_number VARCHAR(50),
                mobile_provider VARCHAR(100),
                is_default BOOLEAN DEFAULT FALSE,
                is_verified BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES jogigs_users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_default (user_id, is_default)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // JoGigs Portfolio Uploads table - Create after payout accounts
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS jogigs_portfolio_uploads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                skill_key VARCHAR(100) NOT NULL,
                file_name VARCHAR(255) NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                file_size INT,
                file_type VARCHAR(100),
                upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('active', 'inactive') DEFAULT 'active',
                FOREIGN KEY (user_id) REFERENCES jogigs_users(id) ON DELETE CASCADE,
                INDEX idx_user_skill (user_id, skill_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // JoGigs Saved Jobs table - Create after portfolio uploads
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS jogigs_saved_jobs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                job_id INT NOT NULL,
                saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                notes TEXT,
                FOREIGN KEY (user_id) REFERENCES jogigs_users(id) ON DELETE CASCADE,
                FOREIGN KEY (job_id) REFERENCES jogigs_jobs(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_job (user_id, job_id),
                INDEX idx_user_id (user_id),
                INDEX idx_job_id (job_id),
                INDEX idx_saved_at (saved_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // JoGigs Quotes table - Create after portfolio uploads
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS jogigs_quotes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                booking_id INT NOT NULL,
                quote_number VARCHAR(50) UNIQUE NOT NULL,
                client_name VARCHAR(255) NOT NULL,
                client_email VARCHAR(255) NOT NULL,
                client_phone VARCHAR(50),
                service_type VARCHAR(255) NOT NULL,
                project_description TEXT NOT NULL,
                quote_amount DECIMAL(10,2) NOT NULL,
                currency ENUM('NGN', 'USD', 'GBP', 'GHS') DEFAULT 'NGN',
                estimated_duration VARCHAR(100),
                terms_conditions TEXT,
                status ENUM('pending', 'accepted', 'rejected', 'expired') DEFAULT 'pending',
                accepted_at TIMESTAMP NULL,
                expires_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_booking_id (booking_id),
                INDEX idx_status (status),
                INDEX idx_expires_at (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // JoGigs Job Assignments table - Create after quotes
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS jogigs_job_assignments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                quote_id INT NULL,
                job_id INT NOT NULL,
                freelancer_id INT NOT NULL,
                assignment_type ENUM('open_market', 'direct_assignment', 'proposal_based') NOT NULL,
                assignment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deadline DATE NOT NULL,
                status ENUM('offered', 'accepted', 'declined', 'in_progress', 'completed', 'cancelled') DEFAULT 'offered',
                accepted_at TIMESTAMP NULL,
                declined_at TIMESTAMP NULL,
                started_at TIMESTAMP NULL,
                completed_at TIMESTAMP NULL,
                admin_notes TEXT,
                freelancer_notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (quote_id) REFERENCES jogigs_quotes(id) ON DELETE SET NULL,
                FOREIGN KEY (job_id) REFERENCES jogigs_jobs(id) ON DELETE CASCADE,
                FOREIGN KEY (freelancer_id) REFERENCES jogigs_users(id) ON DELETE CASCADE,
                INDEX idx_quote_id (quote_id),
                INDEX idx_job_id (job_id),
                INDEX idx_freelancer_id (freelancer_id),
                INDEX idx_status (status),
                INDEX idx_deadline (deadline)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // JoGigs Penalties table - Create after job assignments
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS jogigs_penalties (
                id INT AUTO_INCREMENT PRIMARY KEY,
                freelancer_id INT NOT NULL,
                job_assignment_id INT NOT NULL,
                penalty_type ENUM('late_delivery', 'backed_out', 'poor_quality', 'no_communication') NOT NULL,
                penalty_amount DECIMAL(10,2) NOT NULL,
                currency ENUM('NGN', 'USD', 'GBP', 'GHS') DEFAULT 'NGN',
                reason TEXT NOT NULL,
                admin_notes TEXT,
                status ENUM('pending', 'paid', 'waived') DEFAULT 'pending',
                paid_at TIMESTAMP NULL,
                waived_at TIMESTAMP NULL,
                waived_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (freelancer_id) REFERENCES jogigs_users(id) ON DELETE CASCADE,
                FOREIGN KEY (job_assignment_id) REFERENCES jogigs_job_assignments(id) ON DELETE CASCADE,
                FOREIGN KEY (waived_by) REFERENCES jogigs_users(id) ON DELETE SET NULL,
                INDEX idx_freelancer_id (freelancer_id),
                INDEX idx_job_assignment_id (job_assignment_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // JoGigs Job Progress table - Create after penalties
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS jogigs_job_progress (
                id INT AUTO_INCREMENT PRIMARY KEY,
                job_assignment_id INT NOT NULL,
                progress_percentage INT DEFAULT 0,
                milestone_description TEXT,
                milestone_date DATE,
                status ENUM('not_started', 'in_progress', 'completed', 'delayed') DEFAULT 'not_started',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (job_assignment_id) REFERENCES jogigs_job_assignments(id) ON DELETE CASCADE,
                INDEX idx_job_assignment_id (job_assignment_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // JoGigs Job Communications table - Create after job progress
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS jogigs_job_communications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                job_assignment_id INT NOT NULL,
                sender_id INT NOT NULL,
                sender_type ENUM('admin', 'freelancer', 'client') NOT NULL,
                message TEXT NOT NULL,
                message_type ENUM('text', 'file', 'milestone_update') DEFAULT 'text',
                file_path VARCHAR(500),
                file_name VARCHAR(255),
                is_private BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (job_assignment_id) REFERENCES jogigs_job_assignments(id) ON DELETE CASCADE,
                FOREIGN KEY (sender_id) REFERENCES jogigs_users(id) ON DELETE CASCADE,
                INDEX idx_job_assignment_id (job_assignment_id),
                INDEX idx_sender_id (sender_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Insert default admin user if not exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jogigs_users WHERE email = ?");
        $stmt->execute(['admin@jogigs.com']);
        
        if ($stmt->fetchColumn() == 0) {
            $hashed_password = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
            $stmt = $pdo->prepare("
                INSERT INTO jogigs_users (email, password, first_name, last_name, status, email_verified) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute(['admin@jogigs.com', $hashed_password, 'Admin', 'User', 'active', 1]);
        }

    } catch (Exception $e) {
        error_log("Failed to create jogigs tables: " . $e->getMessage());
        throw $e;
    }
}
?> 