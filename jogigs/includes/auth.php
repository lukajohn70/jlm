<?php
// JLM Freelancer Web App - Authentication Functions

// Ensure database connection and functions are available
if (!isset($pdo)) {
    require_once __DIR__ . '/config.php';
}

// Ensure functions are available
if (!function_exists('log_activity')) {
    require_once __DIR__ . '/functions.php';
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is authenticated (alias for is_logged_in)
 */
function is_authenticated() {
    return is_logged_in();
}

/**
 * Get current user data
 */
function get_current_jogigs_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    return get_user_by_id($_SESSION['user_id']);
}

/**
 * Login user
 */
function login_user($email, $password) {
    global $pdo;
    
    // Ensure database connection is available
    if (!isset($pdo)) {
        require_once __DIR__ . '/config.php';
    }
    
    // Ensure functions are available
    if (!function_exists('log_activity')) {
        require_once __DIR__ . '/functions.php';
    }
    
    try {
        error_log("login_user called with email: " . $email);
        
        // Get user by email
        $user = get_user_by_email($email);
        
        if (!$user) {
            error_log("login_user: User not found for email: " . $email);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        error_log("login_user: User found - ID: " . $user['id'] . ", Status: " . $user['status']);
        
        // Check if user is active
        if ($user['status'] !== 'active') {
            error_log("login_user: User not active - Status: " . $user['status']);
            return ['success' => false, 'message' => 'Account is not active'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            error_log("login_user: Password verification failed for user: " . $user['id']);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        error_log("login_user: Password verified successfully");
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['login_time'] = time();
        
        error_log("login_user: Session variables set - user_id: " . $_SESSION['user_id']);
        
        // Log activity (wrap in try-catch to prevent it from breaking login)
        try {
            if (function_exists('log_activity')) {
                log_activity($user['id'], 'login', 'User logged in successfully');
                error_log("login_user: Activity logged successfully");
            }
        } catch (Exception $e) {
            error_log("login_user: Failed to log activity: " . $e->getMessage());
            // Don't fail login if activity logging fails
        }
        
        // Create session record in database (wrap in try-catch to prevent it from breaking login)
        try {
            create_user_session($user['id']);
            error_log("login_user: User session created successfully");
        } catch (Exception $e) {
            error_log("login_user: Failed to create user session: " . $e->getMessage());
            // Don't fail login if session creation fails
        }
        
        error_log("login_user: Login successful for user: " . $user['id']);
        return ['success' => true, 'message' => 'Login successful', 'user' => $user];
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred during login'];
    }
}

/**
 * Logout user
 */
function logout_user() {
    global $pdo;
    
    // Ensure database connection is available
    if (!isset($pdo)) {
        require_once __DIR__ . '/config.php';
    }
    
    // Ensure functions are available
    if (!function_exists('log_activity')) {
        require_once __DIR__ . '/functions.php';
    }
    
    if (is_logged_in()) {
        // Log activity
        try {
            if (function_exists('log_activity')) {
                log_activity($_SESSION['user_id'], 'logout', 'User logged out');
            }
        } catch (Exception $e) {
            error_log("Failed to log logout activity: " . $e->getMessage());
        }
        
        // Remove session from database
        try {
            if (function_exists('remove_user_session')) {
                remove_user_session($_SESSION['user_id']);
            }
        } catch (Exception $e) {
            error_log("Failed to remove user session: " . $e->getMessage());
        }
    }
    
    // Clear session
    session_destroy();
    session_start();
}

/**
 * Register new user
 */
function register_user($data) {
    global $pdo;
    
    try {
        // Validate required fields
        $required_fields = ['email', 'password', 'first_name', 'last_name'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => ucfirst($field) . ' is required'];
            }
        }
        
        // Validate email
        if (!validate_email($data['email'])) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }
        
        // Check if email already exists
        $existing_user = get_user_by_email($data['email']);
        if ($existing_user) {
            return ['success' => false, 'message' => 'Email address already registered'];
        }
        
        // Prevent admin email registration
        if ($data['email'] === 'admin@jogigs.com') {
            return ['success' => false, 'message' => 'This email address is reserved for administrative use'];
        }
        
        // Hash password
        $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
        
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_users (email, password, first_name, last_name, phone, skills, hourly_rate, bio) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['email'],
            $hashed_password,
            $data['first_name'],
            $data['last_name'],
            $data['phone'] ?? '',
            $data['skills'] ?? '',
            $data['hourly_rate'] ?? 0.00,
            $data['bio'] ?? ''
        ]);
        
        $user_id = $pdo->lastInsertId();
        
        // Log activity
        log_activity($user_id, 'register', 'New user registered');
        
        return ['success' => true, 'message' => 'Registration successful', 'user_id' => $user_id];
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred during registration'];
    }
}

/**
 * Create user session record
 */
function create_user_session($user_id) {
    global $pdo;
    
    try {
        $session_id = session_id();
        $expires_at = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
        
        // Remove existing sessions for this user
        $stmt = $pdo->prepare("DELETE FROM jogigs_sessions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Create new session
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_sessions (user_id, session_id, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $session_id,
            get_client_ip(),
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $expires_at
        ]);
        
    } catch (Exception $e) {
        error_log("Failed to create user session: " . $e->getMessage());
    }
}

/**
 * Remove user session record
 */
function remove_user_session($user_id) {
    global $pdo;
    
    try {
        $session_id = session_id();
        $stmt = $pdo->prepare("DELETE FROM jogigs_sessions WHERE user_id = ? AND session_id = ?");
        $stmt->execute([$user_id, $session_id]);
    } catch (Exception $e) {
        error_log("Failed to remove user session: " . $e->getMessage());
    }
}

/**
 * Validate session
 */
function validate_session() {
    if (!is_logged_in()) {
        return false;
    }
    
    global $pdo;
    
    try {
        $session_id = session_id();
        $stmt = $pdo->prepare("
            SELECT * FROM jogigs_sessions 
            WHERE user_id = ? AND session_id = ? AND expires_at > NOW()
        ");
        $stmt->execute([$_SESSION['user_id'], $session_id]);
        
        if (!$stmt->fetch()) {
            // Session record doesn't exist or expired, but user is logged in
            // Create a new session record instead of logging out
            try {
                create_user_session($_SESSION['user_id']);
                return true;
            } catch (Exception $e) {
                error_log("Failed to create session record: " . $e->getMessage());
                // Don't logout, just return true if session variables exist
                return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
            }
        }
        
        // Update session expiry
        $expires_at = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
        $stmt = $pdo->prepare("
            UPDATE jogigs_sessions 
            SET expires_at = ? 
            WHERE user_id = ? AND session_id = ?
        ");
        $stmt->execute([$expires_at, $_SESSION['user_id'], $session_id]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Session validation error: " . $e->getMessage());
        // If database validation fails, still allow access if session variables exist
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

/**
 * Require authentication
 */
function require_auth() {
    if (!validate_session()) {
        // Check if we're in admin directory and adjust redirect path
        $current_path = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($current_path, '/admin/') !== false) {
            redirect_with_message('../index.php', 'Please log in to access this page', 'error');
        } else {
            redirect_with_message('index.php', 'Please log in to access this page', 'error');
        }
    }
}

/**
 * Change password
 */
function change_password($user_id, $current_password, $new_password) {
    global $pdo;
    
    try {
        // Get user
        $user = get_user_by_id($user_id);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
        
        // Update password
        $stmt = $pdo->prepare("UPDATE jogigs_users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id]);
        
        // Log activity
        log_activity($user_id, 'password_change', 'Password changed successfully');
        
        return ['success' => true, 'message' => 'Password changed successfully'];
        
    } catch (Exception $e) {
        error_log("Password change error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while changing password'];
    }
}

/**
 * Reset password (forgot password)
 */
function reset_password($email) {
    global $pdo;
    
    try {
        // Get user by email
        $user = get_user_by_email($email);
        if (!$user) {
            return ['success' => false, 'message' => 'Email address not found'];
        }
        
        // Generate reset token
        $reset_token = bin2hex(random_bytes(32));
        $reset_expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        // Store reset token
        $stmt = $pdo->prepare("
            UPDATE jogigs_users 
            SET reset_token = ?, reset_expires = ? 
            WHERE id = ?
        ");
        $stmt->execute([$reset_token, $reset_expires, $user['id']]);
        
        // Send reset email with professional template
        $reset_link = APP_URL . '/reset-password.php?token=' . $reset_token;
        
        $email_subject = 'JoGigs - Password Reset Request';
        $email_message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Password Reset Request</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>JoGigs</h1>
                    <p>Password Reset Request</p>
                </div>
                <div class="content">
                    <h2>Hello!</h2>
                    <p>We received a request to reset your password for your JoGigs account.</p>
                    
                    <p>Click the button below to reset your password:</p>
                    
                    <div style="text-align: center;">
                        <a href="' . $reset_link . '" class="button">Reset Password</a>
                    </div>
                    
                    <p>If the button doesn\'t work, copy and paste this link into your browser:</p>
                    <p style="word-break: break-all; color: #667eea;">' . $reset_link . '</p>
                    
                    <div class="warning">
                        <strong>Important:</strong>
                        <ul>
                            <li>This link will expire in 1 hour</li>
                            <li>If you didn\'t request this password reset, please ignore this email</li>
                            <li>For security, this link can only be used once</li>
                        </ul>
                    </div>
                    
                    <p>If you have any questions, please contact our support team.</p>
                    
                    <p>Best regards,<br>The JoGigs Team</p>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' JLM Creative Media - JoGigs. All rights reserved.</p>
                    <p>This email was sent to ' . $email . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        send_email($email, $email_subject, $email_message);
        
        // Log activity
        log_activity($user['id'], 'password_reset_request', 'Password reset requested');
        
        return ['success' => true, 'message' => 'Password reset instructions sent to your email'];
        
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while processing reset request'];
    }
}

/**
 * Verify reset token
 */
function verify_reset_token($token) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id FROM jogigs_users 
            WHERE reset_token = ? AND reset_expires > NOW()
        ");
        $stmt->execute([$token]);
        
        return $stmt->fetch();
        
    } catch (Exception $e) {
        error_log("Reset token verification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Complete password reset
 */
function complete_password_reset($token, $new_password) {
    global $pdo;
    
    try {
        // Verify token
        $user = verify_reset_token($token);
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired reset token'];
        }
        
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
        
        // Update password and clear reset token
        $stmt = $pdo->prepare("
            UPDATE jogigs_users 
            SET password = ?, reset_token = NULL, reset_expires = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$hashed_password, $user['id']]);
        
        // Log activity
        log_activity($user['id'], 'password_reset_complete', 'Password reset completed');
        
        return ['success' => true, 'message' => 'Password reset successfully'];
        
    } catch (Exception $e) {
        error_log("Password reset completion error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while resetting password'];
    }
}

// Add reset token columns to users table if they don't exist
function add_reset_token_columns() {
    global $pdo;
    
    // Ensure database connection is available
    if (!isset($pdo)) {
        require_once __DIR__ . '/config.php';
    }
    
    try {
        // Check if columns exist
        $stmt = $pdo->prepare("SHOW COLUMNS FROM jogigs_users LIKE 'reset_token'");
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE jogigs_users ADD COLUMN reset_token VARCHAR(64) NULL");
            $pdo->exec("ALTER TABLE jogigs_users ADD COLUMN reset_expires TIMESTAMP NULL");
        }
    } catch (Exception $e) {
        error_log("Failed to add reset token columns: " . $e->getMessage());
    }
}

// Only add reset token columns if we're in a context where database is needed and available
if (isset($pdo) && (function_exists('is_logged_in') || isset($_SESSION))) {
    add_reset_token_columns();
}
?> 