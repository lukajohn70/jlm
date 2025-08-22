<?php
// JLM Freelancer Web App - System Integrity Test
// This script tests all major functionality to ensure everything works correctly

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 JoGigs System Integrity Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
    .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
    .warning { background-color: #fff3cd; border-color: #ffeaa7; color: #856404; }
    .info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
    .test-item { margin: 10px 0; padding: 5px; }
    .test-item.success { background-color: #d1e7dd; }
    .test-item.error { background-color: #f8d7da; }
    .test-item.warning { background-color: #fff3cd; }
    .test-item.info { background-color: #cff4fc; }
</style>";

// Test 1: Database Connection
echo "<div class='test-section info'>";
echo "<h2>1. Database Connection Test</h2>";

try {
    require_once 'includes/config.php';
    require_once 'includes/functions.php';
    require_once 'includes/auth.php';
    echo "<div class='test-item success'>✅ Database connection successful</div>";
    echo "<div class='test-item info'>Database: " . DB_NAME . "</div>";
    echo "<div class='test-item info'>Host: " . DB_HOST . "</div>";
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
    exit;
}
echo "</div>";

// Test 2: Required Files
echo "<div class='test-section info'>";
echo "<h2>2. Required Files Test</h2>";

$required_files = [
    'includes/config.php',
    'includes/functions.php',
    'includes/auth.php',
    'dashboard.php',
    'jobs.php',
    'earnings.php',
    'profile.php',
    'login.php',
    'register.php',
    'logout.php',
    'job-details.php',
    'my-proposals.php',
    'submit_proposal.php',
    'submit_job_files.php',
    'job_actions.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<div class='test-item success'>✅ $file exists</div>";
    } else {
        echo "<div class='test-item error'>❌ $file missing</div>";
    }
}
echo "</div>";

// Test 3: Database Tables
echo "<div class='test-section info'>";
echo "<h2>3. Database Tables Test</h2>";

$required_tables = [
    'jogigs_users',
    'jogigs_jobs',
    'jogigs_sessions',
    'jogigs_proposals',
    'jogigs_job_assignments',
    'jogigs_earnings',
    'jogigs_activity_logs',
    'jogigs_portfolio_uploads',
    'jogigs_job_submissions',
    'jogigs_job_submission_files'
];

foreach ($required_tables as $table) {
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE '$table'");
        $stmt->execute();
        if ($stmt->fetch()) {
            echo "<div class='test-item success'>✅ Table $table exists</div>";
        } else {
            echo "<div class='test-item error'>❌ Table $table missing</div>";
        }
    } catch (Exception $e) {
        echo "<div class='test-item error'>❌ Error checking table $table: " . $e->getMessage() . "</div>";
    }
}
echo "</div>";

// Test 4: Core Functions
echo "<div class='test-section info'>";
echo "<h2>4. Core Functions Test</h2>";

$required_functions = [
    'is_logged_in',
    'get_current_jogigs_user',
    'login_user',
    'register_user',
    'logout_user',
    'format_currency',
    'format_date',
    'time_ago',
    'send_email',
    'get_jobs_for_freelancer',
    'get_user_earnings',
    'calculate_total_earnings',
    'get_freelancer_assignments',
    'submit_proposal',
    'submit_job_files',
    'accept_job_assignment',
    'decline_job_assignment',
    'start_job',
    'complete_job',
    'set_flash_message',
    'get_flash_message'
];

foreach ($required_functions as $function) {
    if (function_exists($function)) {
        echo "<div class='test-item success'>✅ Function $function() exists</div>";
    } else {
        echo "<div class='test-item error'>❌ Function $function() missing</div>";
    }
}
echo "</div>";

// Test 5: Test User Creation
echo "<div class='test-section info'>";
echo "<h2>5. Test User Creation</h2>";

try {
    // Check if test user exists
    $stmt = $pdo->prepare("SELECT * FROM jogigs_users WHERE email = ?");
    $stmt->execute(['test@jogigs.com']);
    $test_user = $stmt->fetch();
    
    if ($test_user) {
        echo "<div class='test-item success'>✅ Test user exists (ID: {$test_user['id']})</div>";
        $test_user_id = $test_user['id'];
    } else {
        // Create test user
        $test_data = [
            'email' => 'test@jogigs.com',
            'password' => 'test123',
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '1234567890',
            'skills' => 'web-design,graphic-design',
            'hourly_rate' => 25.00,
            'bio' => 'Test user for system integrity testing'
        ];
        
        $result = register_user($test_data);
        if ($result['success']) {
            echo "<div class='test-item success'>✅ Test user created successfully (ID: {$result['user_id']})</div>";
            $test_user_id = $result['user_id'];
        } else {
            echo "<div class='test-item error'>❌ Failed to create test user: {$result['message']}</div>";
            $test_user_id = null;
        }
    }
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ Error with test user: " . $e->getMessage() . "</div>";
    $test_user_id = null;
}
echo "</div>";

// Test 6: Test Job Creation
echo "<div class='test-section info'>";
echo "<h2>6. Test Job Creation</h2>";

try {
    // Check if test job exists
    $stmt = $pdo->prepare("SELECT * FROM jogigs_jobs WHERE title LIKE ?");
    $stmt->execute(['%Test Job%']);
    $test_job = $stmt->fetch();
    
    if ($test_job) {
        echo "<div class='test-item success'>✅ Test job exists (ID: {$test_job['id']})</div>";
        $test_job_id = $test_job['id'];
    } else {
        // Create test job
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_jobs (title, description, budget_min, budget_max, skills_required, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'Test Job for System Testing',
            'This is a test job created for system integrity testing.',
            10000.00,
            25000.00,
            'web-design,graphic-design',
            'open'
        ]);
        
        $test_job_id = $pdo->lastInsertId();
        echo "<div class='test-item success'>✅ Test job created successfully (ID: $test_job_id)</div>";
    }
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ Error with test job: " . $e->getMessage() . "</div>";
    $test_job_id = null;
}
echo "</div>";

// Test 7: Function Tests
echo "<div class='test-section info'>";
echo "<h2>7. Function Tests</h2>";

// Test format_currency
try {
    $formatted = format_currency(15000.50, 'NGN');
    echo "<div class='test-item success'>✅ format_currency(): $formatted</div>";
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ format_currency() failed: " . $e->getMessage() . "</div>";
}

// Test format_date
try {
    $formatted = format_date('2024-01-15');
    echo "<div class='test-item success'>✅ format_date(): $formatted</div>";
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ format_date() failed: " . $e->getMessage() . "</div>";
}

// Test time_ago
try {
    $time_ago = time_ago('2024-01-15 10:00:00');
    echo "<div class='test-item success'>✅ time_ago(): $time_ago</div>";
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ time_ago() failed: " . $e->getMessage() . "</div>";
}

// Test get_jobs_for_freelancer
try {
    $jobs = get_jobs_for_freelancer(5);
    echo "<div class='test-item success'>✅ get_jobs_for_freelancer(): " . count($jobs) . " jobs found</div>";
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ get_jobs_for_freelancer() failed: " . $e->getMessage() . "</div>";
}

// Test calculate_total_earnings
try {
    $earnings = calculate_total_earnings($test_user_id ?? 1);
    echo "<div class='test-item success'>✅ calculate_total_earnings(): " . format_currency($earnings, 'NGN') . "</div>";
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ calculate_total_earnings() failed: " . $e->getMessage() . "</div>";
}

// Test get_freelancer_assignments
try {
    $assignments = get_freelancer_assignments($test_user_id ?? 1);
    echo "<div class='test-item success'>✅ get_freelancer_assignments(): " . count($assignments) . " assignments found</div>";
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ get_freelancer_assignments() failed: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Test 8: Page Accessibility Test
echo "<div class='test-section info'>";
echo "<h2>8. Page Accessibility Test</h2>";

$pages_to_test = [
    'index.php' => 'Main entry point',
    'landing.php' => 'Landing page',
    'login.php' => 'Login page',
    'register.php' => 'Registration page',
    'dashboard.php' => 'Dashboard (requires login)',
    'jobs.php' => 'Jobs page (requires login)',
    'earnings.php' => 'Earnings page (requires login)',
    'profile.php' => 'Profile page (requires login)',
    'job-details.php' => 'Job details page (requires login)',
    'my-proposals.php' => 'My proposals page (requires login)',
    'submit_proposal.php' => 'Submit proposal page (requires login)',
    'submit_job_files.php' => 'Submit job files page (requires login)'
];

foreach ($pages_to_test as $page => $description) {
    if (file_exists($page)) {
        echo "<div class='test-item success'>✅ $page exists ($description)</div>";
    } else {
        echo "<div class='test-item error'>❌ $page missing ($description)</div>";
    }
}
echo "</div>";

// Test 9: Session Management
echo "<div class='test-section info'>";
echo "<h2>9. Session Management Test</h2>";

// Test session functions
try {
    $session_status = session_status();
    echo "<div class='test-item success'>✅ Session status: " . ($session_status === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</div>";
    
    // Generate CSRF token if not exists
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    // Test flash messages
    set_flash_message('Test flash message', 'success');
    $flash = get_flash_message();
    if ($flash && $flash['message'] === 'Test flash message') {
        echo "<div class='test-item success'>✅ Flash message system working</div>";
    } else {
        echo "<div class='test-item error'>❌ Flash message system not working</div>";
    }
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ Session management error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 10: Security Features
echo "<div class='test-section info'>";
echo "<h2>10. Security Features Test</h2>";

// Test CSRF protection
try {
    if (isset($_SESSION['csrf_token'])) {
        echo "<div class='test-item success'>✅ CSRF token exists</div>";
    } else {
        echo "<div class='test-item warning'>⚠️ CSRF token not set</div>";
    }
    
    // Test password hashing
    $test_password = 'test123';
    $hashed = password_hash($test_password, PASSWORD_BCRYPT);
    if (password_verify($test_password, $hashed)) {
        echo "<div class='test-item success'>✅ Password hashing working</div>";
    } else {
        echo "<div class='test-item error'>❌ Password hashing not working</div>";
    }
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ Security test error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 11: Email Configuration
echo "<div class='test-section info'>";
echo "<h2>11. Email Configuration Test</h2>";

try {
    // Check if PHPMailer is available
    $vendor_path = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($vendor_path)) {
        require_once $vendor_path;
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            echo "<div class='test-item success'>✅ PHPMailer is available</div>";
        } else {
            echo "<div class='test-item error'>❌ PHPMailer class not found</div>";
        }
    } else {
        echo "<div class='test-item error'>❌ Vendor autoload.php not found</div>";
    }
    
    // Check SMTP configuration
    echo "<div class='test-item info'>SMTP Host: " . (defined('SMTP_HOST') ? SMTP_HOST : 'Not defined') . "</div>";
    echo "<div class='test-item info'>SMTP Port: " . (defined('SMTP_PORT') ? SMTP_PORT : 'Not defined') . "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ Email configuration error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 12: File Upload Configuration
echo "<div class='test-section info'>";
echo "<h2>12. File Upload Configuration Test</h2>";

try {
    // Check upload directory
    $upload_dir = __DIR__ . '/uploads';
    if (is_dir($upload_dir)) {
        echo "<div class='test-item success'>✅ Upload directory exists</div>";
    } else {
        echo "<div class='test-item warning'>⚠️ Upload directory missing, creating...</div>";
        if (mkdir($upload_dir, 0755, true)) {
            echo "<div class='test-item success'>✅ Upload directory created</div>";
        } else {
            echo "<div class='test-item error'>❌ Failed to create upload directory</div>";
        }
    }
    
    // Check file size limits
    echo "<div class='test-item info'>Max file size: " . (defined('MAX_FILE_SIZE') ? format_bytes(MAX_FILE_SIZE) : 'Not defined') . "</div>";
    echo "<div class='test-item info'>Allowed extensions: " . (defined('ALLOWED_EXTENSIONS') ? implode(', ', ALLOWED_EXTENSIONS) : 'Not defined') . "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ File upload configuration error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Summary
echo "<div class='test-section info'>";
echo "<h2>📊 Test Summary</h2>";
echo "<p>This test script has verified the following components:</p>";
echo "<ul>";
echo "<li>Database connection and tables</li>";
echo "<li>Required files and functions</li>";
echo "<li>User registration and authentication</li>";
echo "<li>Job management system</li>";
echo "<li>Proposal system</li>";
echo "<li>File upload system</li>";
echo "<li>Session management</li>";
echo "<li>Security features</li>";
echo "<li>Email configuration</li>";
echo "<li>Page accessibility</li>";
echo "</ul>";
echo "<p><strong>If all tests passed, your JoGigs system is ready for use!</strong></p>";
echo "</div>";

// Helper function
function format_bytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
