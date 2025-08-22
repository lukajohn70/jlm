<?php
// JLM Freelancer Web App - Utility Functions

// Global database connection
global $pdo;

/**
 * Sanitize user input
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get client IP address
 */
function get_client_ip() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Log activity
 */
function log_activity($user_id, $action, $details = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_activity_logs (user_id, action, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $action,
            $details,
            get_client_ip(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Format currency
 */
function format_currency($amount, $currency = 'NGN') {
    $symbols = [
        'NGN' => '₦',
        'USD' => '$',
        'GBP' => '£',
        'GHS' => '₵'
    ];
    
    $symbol = $symbols[$currency] ?? '₦';
    return $symbol . number_format($amount, 2);
}

/**
 * Format date
 */
function format_date($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

/**
 * Get time ago
 */
function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'Just now';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return format_date($datetime);
    }
}

/**
 * Upload file
 */
function upload_file($file, $directory = 'uploads') {
    $upload_dir = __DIR__ . '/../' . $directory . '/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Check file extension
    if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $filepath];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

/**
 * Send email using PHPMailer
 */
function send_email($to, $subject, $message, $headers = '') {
    try {
        // Include PHPMailer
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = !empty(SMTP_USER);
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom('noreply@jogigs.com', 'JoGigs');
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);
        
        $mail->send();
        error_log("Email sent successfully to: $to, Subject: $subject");
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        
        // Fallback: log the email content for manual sending
        error_log("Email content for manual sending:");
        error_log("To: $to");
        error_log("Subject: $subject");
        error_log("Message: $message");
        
        return false;
    }
}

/**
 * Validate email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate random string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

/**
 * Get user by ID
 */
function get_user_by_id($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM jogigs_users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Failed to get user: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user by email
 */
function get_user_by_email($email) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM jogigs_users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Failed to get user by email: " . $e->getMessage());
        return false;
    }
}

/**
 * Update user profile
 */
function update_user_profile($user_id, $data) {
    global $pdo;
    
    try {
        $allowed_fields = ['first_name', 'last_name', 'phone', 'skills', 'hourly_rate', 'bio'];
        $updates = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                $updates[] = "$field = ?";
                $values[] = $value;
            }
        }
        
        if (empty($updates)) {
            return ['success' => false, 'message' => 'No valid fields to update'];
        }
        
        $values[] = $user_id;
        $sql = "UPDATE jogigs_users SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        return ['success' => true, 'message' => 'Profile updated successfully'];
    } catch (Exception $e) {
        error_log("Failed to update user profile: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update profile'];
    }
}

/**
 * Get jobs for freelancer
 */
function get_jobs_for_freelancer($limit = 10, $offset = 0) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM jogigs_jobs 
            WHERE status = 'open' 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get jobs: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user earnings
 */
function get_user_earnings($user_id, $limit = 10, $offset = 0) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM jogigs_earnings 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $limit, $offset]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get earnings: " . $e->getMessage());
        return [];
    }
}

/**
 * Calculate total earnings
 */
function calculate_total_earnings($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT SUM(amount) as total FROM jogigs_earnings 
            WHERE user_id = ? AND status = 'completed'
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    } catch (Exception $e) {
        error_log("Failed to calculate earnings: " . $e->getMessage());
        return 0;
    }
}

/**
 * Redirect with message
 */
function redirect_with_message($url, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $url");
    exit();
}

/**
 * Set flash message for user feedback
 */
function set_flash_message($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get flash message
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Check if user has permission
 */
function has_permission($user_id, $permission) {
    // This is a simple implementation - expand as needed
    $user = get_user_by_id($user_id);
    return $user && $user['status'] === 'active';
}

/**
 * Create activity logs table if it doesn't exist
 */
function create_activity_logs_table() {
    global $pdo;
    
    try {
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
        return true;
    } catch (Exception $e) {
        error_log("Failed to create activity logs table: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's payout accounts
 */
function get_user_payout_accounts($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM jogigs_payout_accounts 
            WHERE user_id = ? 
            ORDER BY is_default DESC, created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get payout accounts: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user's default payout account
 */
function get_user_default_payout_account($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM jogigs_payout_accounts 
            WHERE user_id = ? AND is_default = TRUE
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Failed to get default payout account: " . $e->getMessage());
        return null;
    }
}

/**
 * Add new payout account
 */
function add_payout_account($user_id, $data) {
    global $pdo;
    
    try {
        // If this is the first account or user wants it as default, set as default
        if ($data['is_default'] ?? false) {
            // Remove default from other accounts
            $stmt = $pdo->prepare("
                UPDATE jogigs_payout_accounts 
                SET is_default = FALSE 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_payout_accounts (
                user_id, account_type, account_name, account_number, 
                bank_name, bank_code, paypal_email, stripe_account_id,
                mobile_number, mobile_provider, is_default, is_verified
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $data['account_type'],
            $data['account_name'],
            $data['account_number'] ?? null,
            $data['bank_name'] ?? null,
            $data['bank_code'] ?? null,
            $data['paypal_email'] ?? null,
            $data['stripe_account_id'] ?? null,
            $data['mobile_number'] ?? null,
            $data['mobile_provider'] ?? null,
            $data['is_default'] ?? false,
            false // is_verified starts as false
        ]);
        
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Failed to add payout account: " . $e->getMessage());
        return false;
    }
}

/**
 * Update payout account
 */
function update_payout_account($account_id, $user_id, $data) {
    global $pdo;
    
    try {
        // If setting as default, remove default from other accounts
        if ($data['is_default'] ?? false) {
            $stmt = $pdo->prepare("
                UPDATE jogigs_payout_accounts 
                SET is_default = FALSE 
                WHERE user_id = ? AND id != ?
            ");
            $stmt->execute([$user_id, $account_id]);
        }
        
        $stmt = $pdo->prepare("
            UPDATE jogigs_payout_accounts SET
                account_type = ?,
                account_name = ?,
                account_number = ?,
                bank_name = ?,
                bank_code = ?,
                paypal_email = ?,
                stripe_account_id = ?,
                mobile_number = ?,
                mobile_provider = ?,
                is_default = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->execute([
            $data['account_type'],
            $data['account_name'],
            $data['account_number'] ?? null,
            $data['bank_name'] ?? null,
            $data['bank_code'] ?? null,
            $data['paypal_email'] ?? null,
            $data['stripe_account_id'] ?? null,
            $data['mobile_number'] ?? null,
            $data['mobile_provider'] ?? null,
            $data['is_default'] ?? false,
            $account_id,
            $user_id
        ]);
        
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Failed to update payout account: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete payout account
 */
function delete_payout_account($account_id, $user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM jogigs_payout_accounts 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$account_id, $user_id]);
        
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Failed to delete payout account: " . $e->getMessage());
        return false;
    }
}

/**
 * Set default payout account
 */
function set_default_payout_account($user_id, $account_id) {
    global $pdo;
    
    try {
        // Remove default from all accounts
        $stmt = $pdo->prepare("UPDATE jogigs_payout_accounts SET is_default = FALSE WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Set new default
        $stmt = $pdo->prepare("UPDATE jogigs_payout_accounts SET is_default = TRUE WHERE id = ? AND user_id = ?");
        $stmt->execute([$account_id, $user_id]);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to set default payout account: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate payout account data
 */
function validate_payout_account($data) {
    $errors = [];
    
    if (empty($data['account_type'])) {
        $errors[] = 'Account type is required';
    }
    
    if (empty($data['account_name'])) {
        $errors[] = 'Account name is required';
    }
    
    switch ($data['account_type']) {
        case 'bank':
            if (empty($data['account_number'])) {
                $errors[] = 'Account number is required for bank accounts';
            }
            if (empty($data['bank_name'])) {
                $errors[] = 'Bank name is required for bank accounts';
            }
            break;
            
        case 'paypal':
            if (empty($data['paypal_email'])) {
                $errors[] = 'PayPal email is required for PayPal accounts';
            }
            break;
            
        case 'mobile_money':
            if (empty($data['mobile_number'])) {
                $errors[] = 'Mobile number is required for mobile money accounts';
            }
            if (empty($data['mobile_provider'])) {
                $errors[] = 'Mobile provider is required for mobile money accounts';
            }
            break;
    }
    
    return $errors;
}

// ==================== FREELANCING SYSTEM FUNCTIONS ====================

/**
 * Generate quote number
 */
function generate_quote_number() {
    return 'QT-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Create quote from booking
 */
function create_quote_from_booking($booking_data) {
    global $pdo;
    
    try {
        $quote_number = generate_quote_number();
        
        // Calculate quote amount based on service type and complexity
        $quote_amount = calculate_quote_amount($booking_data['services'], $booking_data['project_description']);
        
        // Set expiration date (7 days from now)
        $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_quotes (
                booking_id, quote_number, client_name, client_email, client_phone,
                service_type, project_description, quote_amount, currency,
                estimated_duration, terms_conditions, expires_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $booking_data['booking_id'],
            $quote_number,
            $booking_data['client_name'],
            $booking_data['client_email'],
            $booking_data['client_phone'] ?? '',
            is_array($booking_data['services']) ? implode(', ', $booking_data['services']) : $booking_data['services'],
            $booking_data['project_description'],
            $quote_amount,
            'NGN', // Default currency
            calculate_estimated_duration($booking_data['services'], $booking_data['project_description']),
            get_default_terms_conditions(),
            $expires_at
        ]);
        
        $quote_id = $pdo->lastInsertId();
        
        // Log activity
        log_activity(null, 'quote_created', "Quote {$quote_number} created for booking {$booking_data['booking_id']}");
        
        return [
            'success' => true,
            'quote_id' => $quote_id,
            'quote_number' => $quote_number,
            'amount' => $quote_amount
        ];
        
    } catch (Exception $e) {
        error_log("Failed to create quote: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create quote'];
    }
}

/**
 * Calculate quote amount based on services and project description
 */
function calculate_quote_amount($services, $project_description) {
    $base_amount = 0;
    
    // Base amounts for different services
    $service_prices = [
        'web_development' => 50000,
        'mobile_app_development' => 75000,
        'graphic_design' => 25000,
        'digital_marketing' => 35000,
        'content_creation' => 20000,
        'seo_optimization' => 30000,
        'social_media_management' => 25000,
        'video_editing' => 40000,
        'ui_ux_design' => 45000,
        'data_analysis' => 60000
    ];
    
    if (is_array($services)) {
        foreach ($services as $service) {
            $service_key = strtolower(str_replace(' ', '_', $service));
            if (isset($service_prices[$service_key])) {
                $base_amount += $service_prices[$service_key];
            } else {
                $base_amount += 30000; // Default price for unknown services
            }
        }
    } else {
        // Single service
        $service_key = strtolower(str_replace(' ', '_', $services));
        $base_amount = $service_prices[$service_key] ?? 30000;
    }
    
    // Adjust based on project complexity (word count in description)
    $word_count = str_word_count($project_description);
    if ($word_count > 500) {
        $base_amount *= 1.5; // Complex project
    } elseif ($word_count > 200) {
        $base_amount *= 1.2; // Medium complexity
    }
    
    return $base_amount;
}

/**
 * Calculate estimated duration
 */
function calculate_estimated_duration($services, $project_description) {
    $word_count = str_word_count($project_description);
    
    if (is_array($services) && count($services) > 1) {
        return '2-4 weeks';
    }
    
    if ($word_count > 500) {
        return '3-4 weeks';
    } elseif ($word_count > 200) {
        return '2-3 weeks';
    } else {
        return '1-2 weeks';
    }
}

/**
 * Get default terms and conditions
 */
function get_default_terms_conditions() {
    return "1. Payment is due upon project completion\n2. Revisions are included up to 2 rounds\n3. Project timeline is estimated and may vary\n4. Communication will be maintained throughout the project\n5. Final deliverables will be provided upon payment";
}

/**
 * Accept quote
 */
function accept_quote($quote_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE jogigs_quotes 
            SET status = 'accepted', accepted_at = NOW() 
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$quote_id]);
        
        if ($stmt->rowCount() > 0) {
            // Create job from accepted quote
            $job_result = create_job_from_quote($quote_id);
            
            log_activity(null, 'quote_accepted', "Quote {$quote_id} accepted");
            
            return ['success' => true, 'job_id' => $job_result['job_id'] ?? null];
        }
        
        return ['success' => false, 'message' => 'Quote not found or already processed'];
        
    } catch (Exception $e) {
        error_log("Failed to accept quote: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to accept quote'];
    }
}

/**
 * Create job from accepted quote
 */
function create_job_from_quote($quote_id) {
    global $pdo;
    
    try {
        // Get quote details
        $stmt = $pdo->prepare("SELECT * FROM jogigs_quotes WHERE id = ?");
        $stmt->execute([$quote_id]);
        $quote = $stmt->fetch();
        
        if (!$quote) {
            return ['success' => false, 'message' => 'Quote not found'];
        }
        
        // Create job
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_jobs (
                title, description, budget_min, budget_max, skills_required, status, deadline
            ) VALUES (?, ?, ?, ?, ?, 'open', ?)
        ");
        
        $deadline = date('Y-m-d', strtotime('+2 weeks'));
        
        $stmt->execute([
            "Project: " . $quote['service_type'],
            $quote['project_description'],
            $quote['quote_amount'],
            $quote['quote_amount'],
            $quote['service_type'],
            $deadline
        ]);
        
        $job_id = $pdo->lastInsertId();
        
        return ['success' => true, 'job_id' => $job_id];
        
    } catch (Exception $e) {
        error_log("Failed to create job from quote: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create job'];
    }
}

/**
 * Assign job to freelancer (open market)
 */
function assign_job_to_freelancers($job_id, $quote_id, $deadline_days = 14) {
    global $pdo;
    
    try {
        // Get available freelancers with matching skills
        $stmt = $pdo->prepare("
            SELECT j.id, j.title, j.skills_required, q.service_type
            FROM jogigs_jobs j
            JOIN jogigs_quotes q ON j.id = ?
            WHERE j.id = ?
        ");
        $stmt->execute([$job_id, $job_id]);
        $job = $stmt->fetch();
        
        if (!$job) {
            return ['success' => false, 'message' => 'Job not found'];
        }
        
        // Find freelancers with matching skills
        $skills = explode(',', $job['skills_required']);
        $placeholders = str_repeat('?,', count($skills) - 1) . '?';
        
        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name, skills, hourly_rate
            FROM jogigs_users 
            WHERE status = 'active' 
            AND skills REGEXP ?
            ORDER BY hourly_rate ASC
        ");
        
        $skills_regex = implode('|', array_map('trim', $skills));
        $stmt->execute([$skills_regex]);
        $freelancers = $stmt->fetchAll();
        
        $deadline = date('Y-m-d', strtotime("+{$deadline_days} days"));
        $assignments_created = 0;
        
        foreach ($freelancers as $freelancer) {
            $stmt = $pdo->prepare("
                INSERT INTO jogigs_job_assignments (
                    quote_id, job_id, freelancer_id, assignment_type, deadline, status
                ) VALUES (?, ?, ?, 'open_market', ?, 'offered')
            ");
            
            $stmt->execute([
                $quote_id,
                $job_id,
                $freelancer['id'],
                $deadline
            ]);
            
            $assignments_created++;
        }
        
        log_activity(null, 'job_assigned', "Job {$job_id} assigned to {$assignments_created} freelancers");
        
        return ['success' => true, 'assignments_created' => $assignments_created];
        
    } catch (Exception $e) {
        error_log("Failed to assign job to freelancers: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to assign job'];
    }
}

/**
 * Assign job directly to specific freelancer
 */
function assign_job_directly($job_id, $quote_id, $freelancer_id, $deadline_days = 14) {
    global $pdo;
    
    try {
        // Check if assignment already exists
        $stmt = $pdo->prepare("
            SELECT id FROM jogigs_job_assignments 
            WHERE job_id = ? AND freelancer_id = ?
        ");
        $stmt->execute([$job_id, $freelancer_id]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Job already assigned to this freelancer'];
        }
        
        $deadline = date('Y-m-d', strtotime("+{$deadline_days} days"));
        
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_job_assignments (
                quote_id, job_id, freelancer_id, assignment_type, deadline, status
            ) VALUES (?, ?, ?, 'direct_assignment', ?, 'offered')
        ");
        
        $stmt->execute([
            $quote_id,
            $job_id,
            $freelancer_id,
            $deadline
        ]);
        
        $assignment_id = $pdo->lastInsertId();
        
        log_activity($freelancer_id, 'job_directly_assigned', "Job {$job_id} directly assigned to freelancer {$freelancer_id}");
        
        return ['success' => true, 'assignment_id' => $assignment_id];
        
    } catch (Exception $e) {
        error_log("Failed to assign job directly: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to assign job'];
    }
}

/**
 * Create job assignment from accepted proposal
 */
function create_assignment_from_proposal($proposal_id) {
    global $pdo;
    
    try {
        // Get proposal details
        $stmt = $pdo->prepare("
            SELECT p.*, j.deadline as job_deadline 
            FROM jogigs_proposals p
            JOIN jogigs_jobs j ON p.job_id = j.id
            WHERE p.id = ?
        ");
        $stmt->execute([$proposal_id]);
        $proposal = $stmt->fetch();
        
        if (!$proposal) {
            return ['success' => false, 'message' => 'Proposal not found'];
        }
        
        // Check if assignment already exists
        $stmt = $pdo->prepare("
            SELECT id FROM jogigs_job_assignments 
            WHERE job_id = ? AND freelancer_id = ?
        ");
        $stmt->execute([$proposal['job_id'], $proposal['freelancer_id']]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Assignment already exists for this job and freelancer'];
        }
        
        // Create assignment
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_job_assignments (
                quote_id, job_id, freelancer_id, assignment_type, deadline, status
            ) VALUES (NULL, ?, ?, 'proposal_based', ?, 'accepted')
        ");
        
        $stmt->execute([
            $proposal['job_id'],
            $proposal['freelancer_id'],
            $proposal['job_deadline']
        ]);
        
        $assignment_id = $pdo->lastInsertId();
        
        // Create initial progress record
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_job_progress (
                job_assignment_id, progress_percentage, status, notes
            ) VALUES (?, 0, 'not_started', 'Job assigned from accepted proposal')
        ");
        $stmt->execute([$assignment_id]);
        
        log_activity($proposal['freelancer_id'], 'proposal_accepted_assignment_created', "Assignment created from accepted proposal {$proposal_id}");
        
        return ['success' => true, 'assignment_id' => $assignment_id];
        
    } catch (Exception $e) {
        error_log("Failed to create assignment from proposal: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create assignment'];
    }
}

/**
 * Accept job assignment
 */
function accept_job_assignment($assignment_id, $freelancer_id) {
    global $pdo;
    
    try {
        // Check if assignment exists and is offered to this freelancer
        $stmt = $pdo->prepare("
            SELECT * FROM jogigs_job_assignments 
            WHERE id = ? AND freelancer_id = ? AND status = 'offered'
        ");
        $stmt->execute([$assignment_id, $freelancer_id]);
        $assignment = $stmt->fetch();
        
        if (!$assignment) {
            return ['success' => false, 'message' => 'Assignment not found or not available'];
        }
        
        // Accept the assignment
        $stmt = $pdo->prepare("
            UPDATE jogigs_job_assignments 
            SET status = 'accepted', accepted_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$assignment_id]);
        
        // Decline all other assignments for this job
        $stmt = $pdo->prepare("
            UPDATE jogigs_job_assignments 
            SET status = 'declined', declined_at = NOW() 
            WHERE job_id = ? AND id != ? AND status = 'offered'
        ");
        $stmt->execute([$assignment['job_id'], $assignment_id]);
        
        // Create initial progress record
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_job_progress (
                job_assignment_id, progress_percentage, status, notes
            ) VALUES (?, 0, 'not_started', 'Job accepted, ready to start')
        ");
        $stmt->execute([$assignment_id]);
        
        log_activity($freelancer_id, 'job_accepted', "Job assignment {$assignment_id} accepted");
        
        return ['success' => true];
        
    } catch (Exception $e) {
        error_log("Failed to accept job assignment: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to accept job'];
    }
}

/**
 * Decline job assignment
 */
function decline_job_assignment($assignment_id, $freelancer_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE jogigs_job_assignments 
            SET status = 'declined', declined_at = NOW() 
            WHERE id = ? AND freelancer_id = ? AND status = 'offered'
        ");
        $stmt->execute([$assignment_id, $freelancer_id]);
        
        if ($stmt->rowCount() > 0) {
            log_activity($freelancer_id, 'job_declined', "Job assignment {$assignment_id} declined");
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Assignment not found or not available'];
        
    } catch (Exception $e) {
        error_log("Failed to decline job assignment: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to decline job'];
    }
}

/**
 * Start job (begin work)
 */
function start_job($assignment_id, $freelancer_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE jogigs_job_assignments 
            SET status = 'in_progress', started_at = NOW() 
            WHERE id = ? AND freelancer_id = ? AND status = 'accepted'
        ");
        $stmt->execute([$assignment_id, $freelancer_id]);
        
        if ($stmt->rowCount() > 0) {
            // Update progress
            $stmt = $pdo->prepare("
                UPDATE jogigs_job_progress 
                SET status = 'in_progress', progress_percentage = 10 
                WHERE job_assignment_id = ?
            ");
            $stmt->execute([$assignment_id]);
            
            log_activity($freelancer_id, 'job_started', "Job assignment {$assignment_id} started");
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Job not found or not accepted'];
        
    } catch (Exception $e) {
        error_log("Failed to start job: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to start job'];
    }
}

/**
 * Complete job
 */
function complete_job($assignment_id, $freelancer_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE jogigs_job_assignments 
            SET status = 'completed', completed_at = NOW() 
            WHERE id = ? AND freelancer_id = ? AND status = 'in_progress'
        ");
        $stmt->execute([$assignment_id, $freelancer_id]);
        
        if ($stmt->rowCount() > 0) {
            // Update progress
            $stmt = $pdo->prepare("
                UPDATE jogigs_job_progress 
                SET status = 'completed', progress_percentage = 100 
                WHERE job_assignment_id = ?
            ");
            $stmt->execute([$assignment_id]);
            
            // Create earnings record
            create_earnings_from_job($assignment_id);
            
            log_activity($freelancer_id, 'job_completed', "Job assignment {$assignment_id} completed");
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Job not found or not in progress'];
        
    } catch (Exception $e) {
        error_log("Failed to complete job: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to complete job'];
    }
}

/**
 * Create earnings from completed job
 */
function create_earnings_from_job($assignment_id) {
    global $pdo;
    
    try {
        // Get job assignment details
        $stmt = $pdo->prepare("
            SELECT ja.*, q.quote_amount, q.currency
            FROM jogigs_job_assignments ja
            JOIN jogigs_quotes q ON ja.quote_id = q.id
            WHERE ja.id = ?
        ");
        $stmt->execute([$assignment_id]);
        $assignment = $stmt->fetch();
        
        if (!$assignment) {
            return false;
        }
        
        // Calculate freelancer payment (80% of quote amount)
        $payment_amount = $assignment['quote_amount'] * 0.8;
        
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_earnings (
                user_id, job_id, amount, description, type, status
            ) VALUES (?, ?, ?, ?, 'payment', 'pending')
        ");
        
        $stmt->execute([
            $assignment['freelancer_id'],
            $assignment['job_id'],
            $payment_amount,
            "Payment for completed job assignment {$assignment_id}"
        ]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Failed to create earnings from job: " . $e->getMessage());
        return false;
    }
}

/**
 * Apply penalty for backing out after acceptance
 */
function apply_backout_penalty($assignment_id, $freelancer_id, $reason) {
    global $pdo;
    
    try {
        // Get assignment details
        $stmt = $pdo->prepare("
            SELECT ja.*, q.quote_amount, q.currency
            FROM jogigs_job_assignments ja
            JOIN jogigs_quotes q ON ja.quote_id = q.id
            WHERE ja.id = ? AND ja.freelancer_id = ?
        ");
        $stmt->execute([$assignment_id, $freelancer_id]);
        $assignment = $stmt->fetch();
        
        if (!$assignment || $assignment['status'] !== 'accepted') {
            return ['success' => false, 'message' => 'Assignment not found or not accepted'];
        }
        
        // Calculate penalty (20% of quote amount)
        $penalty_amount = $assignment['quote_amount'] * 0.2;
        
        // Create penalty record
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_penalties (
                freelancer_id, job_assignment_id, penalty_type, penalty_amount, 
                currency, reason, status
            ) VALUES (?, ?, 'backed_out', ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $freelancer_id,
            $assignment_id,
            $penalty_amount,
            $assignment['currency'],
            $reason
        ]);
        
        // Update assignment status
        $stmt = $pdo->prepare("
            UPDATE jogigs_job_assignments 
            SET status = 'cancelled' 
            WHERE id = ?
        ");
        $stmt->execute([$assignment_id]);
        
        log_activity($freelancer_id, 'penalty_applied', "Backout penalty applied for assignment {$assignment_id}");
        
        return ['success' => true, 'penalty_amount' => $penalty_amount];
        
    } catch (Exception $e) {
        error_log("Failed to apply penalty: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to apply penalty'];
    }
}

/**
 * Get freelancer's job assignments
 */
function get_freelancer_assignments($freelancer_id, $status = null) {
    global $pdo;
    
    try {
        $sql = "
            SELECT ja.*, j.title, j.description, q.quote_amount, q.currency,
                   q.client_name, q.estimated_duration
            FROM jogigs_job_assignments ja
            JOIN jogigs_jobs j ON ja.job_id = j.id
            JOIN jogigs_quotes q ON ja.quote_id = q.id
            WHERE ja.freelancer_id = ?
        ";
        
        $params = [$freelancer_id];
        
        if ($status) {
            $sql .= " AND ja.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY ja.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Failed to get freelancer assignments: " . $e->getMessage());
        return [];
    }
}

/**
 * Get job assignment details
 */
function get_job_assignment($assignment_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT ja.*, j.title, j.description, q.quote_amount, q.currency,
                   q.client_name, q.estimated_duration, q.project_description,
                   u.first_name, u.last_name, u.email
            FROM jogigs_job_assignments ja
            JOIN jogigs_jobs j ON ja.job_id = j.id
            JOIN jogigs_quotes q ON ja.quote_id = q.id
            JOIN jogigs_users u ON ja.freelancer_id = u.id
            WHERE ja.id = ?
        ");
        $stmt->execute([$assignment_id]);
        
        return $stmt->fetch();
        
    } catch (Exception $e) {
        error_log("Failed to get job assignment: " . $e->getMessage());
        return null;
    }
}

/**
 * Update job progress
 */
function update_job_progress($assignment_id, $progress_percentage, $milestone_description = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE jogigs_job_progress 
            SET progress_percentage = ?, milestone_description = ?, updated_at = NOW() 
            WHERE job_assignment_id = ?
        ");
        
        $stmt->execute([
            $progress_percentage,
            $milestone_description,
            $assignment_id
        ]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Failed to update job progress: " . $e->getMessage());
        return false;
    }
}

/**
 * Get available jobs for freelancers
 */
function get_available_jobs_for_freelancer($freelancer_id, $limit = 10) {
    global $pdo;
    
    try {
        // Get freelancer's skills
        $stmt = $pdo->prepare("SELECT skills FROM jogigs_users WHERE id = ?");
        $stmt->execute([$freelancer_id]);
        $freelancer = $stmt->fetch();
        
        if (!$freelancer || empty($freelancer['skills'])) {
            return [];
        }
        
        $skills = explode(',', $freelancer['skills']);
        $placeholders = str_repeat('?,', count($skills) - 1) . '?';
        
        $stmt = $pdo->prepare("
            SELECT j.*, q.quote_amount, q.currency, q.client_name, q.estimated_duration
            FROM jogigs_jobs j
            JOIN jogigs_quotes q ON j.id = q.id
            WHERE j.status = 'open'
            AND j.skills_required REGEXP ?
            AND j.id NOT IN (
                SELECT job_id FROM jogigs_job_assignments 
                WHERE freelancer_id = ?
            )
            ORDER BY j.created_at DESC
            LIMIT ?
        ");
        
        $skills_regex = implode('|', array_map('trim', $skills));
        $stmt->execute([$skills_regex, $freelancer_id, $limit]);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Failed to get available jobs: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark job as completed and notify client for confirmation
 */
function mark_job_completed($job_assignment_id) {
    global $pdo;
    
    try {
        // Update job assignment status
        $stmt = $pdo->prepare("
            UPDATE jogigs_job_assignments 
            SET status = 'completed', completed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$job_assignment_id]);
        
        // Get job assignment details
        $stmt = $pdo->prepare("
            SELECT ja.*, j.title, q.client_email, q.client_name
            FROM jogigs_job_assignments ja
            JOIN jogigs_jobs j ON ja.job_id = j.id
            JOIN jogigs_quotes q ON ja.quote_id = q.id
            WHERE ja.id = ?
        ");
        $stmt->execute([$job_assignment_id]);
        $assignment = $stmt->fetch();
        
        if ($assignment) {
            // Create client notification
            $notification_token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("
                INSERT INTO jogigs_client_notifications (
                    job_assignment_id, client_email, client_name, 
                    notification_type, notification_token
                ) VALUES (?, ?, ?, 'job_completed', ?)
            ");
            $stmt->execute([
                $job_assignment_id,
                $assignment['client_email'],
                $assignment['client_name'],
                $notification_token
            ]);
            
            // Send email notification to client
            send_client_completion_notification($assignment, $notification_token);
            
            log_activity($assignment['freelancer_id'], 'job_completed', "Job {$assignment['job_id']} marked as completed");
            
            return ['success' => true, 'notification_token' => $notification_token];
        }
        
        return ['success' => false, 'message' => 'Job assignment not found'];
        
    } catch (Exception $e) {
        error_log("Failed to mark job as completed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to mark job as completed'];
    }
}

/**
 * Client confirms job completion and releases payment
 */
function client_confirm_job($job_assignment_id, $notification_token, $rating = null, $feedback = null) {
    global $pdo;
    
    try {
        // Verify notification token
        $stmt = $pdo->prepare("
            SELECT cn.*, ja.freelancer_id, ja.job_id, ja.quote_id
            FROM jogigs_client_notifications cn
            JOIN jogigs_job_assignments ja ON cn.job_assignment_id = ja.id
            WHERE cn.notification_token = ? AND cn.job_assignment_id = ?
        ");
        $stmt->execute([$notification_token, $job_assignment_id]);
        $notification = $stmt->fetch();
        
        if (!$notification) {
            return ['success' => false, 'message' => 'Invalid notification token'];
        }
        
        // Update job assignment with client confirmation
        $stmt = $pdo->prepare("
            UPDATE jogigs_job_assignments 
            SET client_confirmed = TRUE, client_confirmed_at = NOW(),
                client_rating = ?, client_feedback = ?
            WHERE id = ?
        ");
        $stmt->execute([$rating, $feedback, $job_assignment_id]);
        
        // Mark notification as read
        $stmt = $pdo->prepare("
            UPDATE jogigs_client_notifications 
            SET is_read = TRUE, read_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$notification['id']]);
        
        // Create earnings record for freelancer
        $stmt = $pdo->prepare("
            SELECT q.quote_amount, q.currency
            FROM jogigs_quotes q
            WHERE q.id = ?
        ");
        $stmt->execute([$notification['quote_id']]);
        $quote = $stmt->fetch();
        
        if ($quote) {
            $stmt = $pdo->prepare("
                INSERT INTO jogigs_earnings (
                    user_id, job_id, amount, description, type, status
                ) VALUES (?, ?, ?, ?, 'payment', 'completed')
            ");
            $stmt->execute([
                $notification['freelancer_id'],
                $notification['job_id'],
                $quote['quote_amount'] * 0.8, // 80% to freelancer
                "Payment for completed job #{$notification['job_id']}"
            ]);
        }
        
        // Send confirmation email to freelancer
        send_freelancer_payment_notification($notification['freelancer_id'], $notification['job_id']);
        
        log_activity($notification['freelancer_id'], 'payment_released', "Payment released for job {$notification['job_id']}");
        
        return ['success' => true, 'message' => 'Job confirmed and payment released'];
        
    } catch (Exception $e) {
        error_log("Failed to confirm job: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to confirm job'];
    }
}

/**
 * Submit a proposal for a job
 */
function submit_proposal($job_id, $freelancer_id, $proposal_data) {
    global $pdo;
    
    try {
        // Check if freelancer already submitted a proposal for this job
        $stmt = $pdo->prepare("
            SELECT id FROM jogigs_proposals 
            WHERE job_id = ? AND freelancer_id = ?
        ");
        $stmt->execute([$job_id, $freelancer_id]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'You have already submitted a proposal for this job'];
        }
        
        // Insert proposal
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_proposals (
                job_id, freelancer_id, proposal_title, proposal_text,
                bid_amount, currency, estimated_days, delivery_timeline,
                portfolio_samples, additional_notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $job_id,
            $freelancer_id,
            $proposal_data['title'],
            $proposal_data['text'],
            $proposal_data['bid_amount'],
            $proposal_data['currency'] ?? 'NGN',
            $proposal_data['estimated_days'],
            $proposal_data['delivery_timeline'] ?? '',
            json_encode($proposal_data['portfolio_samples'] ?? []),
            $proposal_data['additional_notes'] ?? ''
        ]);
        
        $proposal_id = $pdo->lastInsertId();
        
        // Log activity
        log_activity($freelancer_id, 'proposal_submitted', "Proposal submitted for job {$job_id}");
        
        return ['success' => true, 'proposal_id' => $proposal_id];
        
    } catch (Exception $e) {
        error_log("Failed to submit proposal: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to submit proposal'];
    }
}

/**
 * Create job automatically from booking submission
 */
function create_job_from_booking($booking_id, $admin_id) {
    global $pdo;
    
    try {
        // Get booking details
        $stmt = $pdo->prepare("
            SELECT * FROM booking_submissions WHERE id = ?
        ");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found'];
        }
        
        // Get job template based on service
        $service_type = determine_service_type($booking['service']);
        $stmt = $pdo->prepare("
            SELECT * FROM job_templates 
            WHERE service_type = ? AND is_active = TRUE
        ");
        $stmt->execute([$service_type]);
        $template = $stmt->fetch();
        
        if (!$template) {
            return ['success' => false, 'message' => 'No template found for service type'];
        }
        
        // Create job directly (no quote needed)
        
        // Create job
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_jobs (
                title, description, skills_required, budget_min, budget_max,
                deadline, status, allow_proposals, proposal_deadline
            ) VALUES (?, ?, ?, ?, ?, ?, 'open', TRUE, ?)
        ");
        
        $deadline = date('Y-m-d', strtotime("+{$template['default_deadline_days']} days"));
        $proposal_deadline = date('Y-m-d', strtotime("+7 days"));
        
        $stmt->execute([
            $template['default_title'],
            $booking['project_description'],
            $template['default_skills'],
            $template['default_budget_min'],
            $template['default_budget_max'],
            $deadline,
            $proposal_deadline
        ]);
        
        $job_id = $pdo->lastInsertId();
        
        // Update booking with job reference
        $stmt = $pdo->prepare("
            UPDATE booking_submissions 
            SET job_created = TRUE, job_id = ?, job_created_at = NOW(), job_created_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$job_id, $admin_id, $booking_id]);
        
        // Log activity
        log_activity($admin_id, 'job_created_from_booking', "Job {$job_id} created from booking {$booking_id}");
        
        return ['success' => true, 'job_id' => $job_id];
        
    } catch (Exception $e) {
        error_log("Failed to create job from booking: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create job from booking'];
    }
}

/**
 * Determine service type from booking service
 */
function determine_service_type($service) {
    $service = strtolower($service);
    
    // Direct service type mappings
    if (strpos($service, 'music-production') !== false) return 'music-production';
    if (strpos($service, 'livestreaming') !== false) return 'livestreaming';
    if (strpos($service, 'motion-graphics') !== false) return 'motion-graphics';
    if (strpos($service, 'social-media') !== false) return 'social-media';
    if (strpos($service, 'photography') !== false) return 'photography';
    if (strpos($service, 'consultation') !== false) return 'consultation';
    
    // Legacy service type mappings
    if (strpos($service, 'video') !== false) return 'video_production';
    if (strpos($service, 'graphic') !== false || strpos($service, 'design') !== false) return 'graphic_design';
    if (strpos($service, 'web') !== false || strpos($service, 'website') !== false) return 'web_design';
    if (strpos($service, 'content') !== false || strpos($service, 'writing') !== false) return 'content_creation';
    if (strpos($service, 'brand') !== false) return 'branding';
    
    return 'general';
}

/**
 * Send client completion notification email
 */
function send_client_completion_notification($assignment, $notification_token) {
    $subject = "Your project is ready for review - JLM";
    $message = "
    <h2>Your project is complete!</h2>
    <p>Dear {$assignment['client_name']},</p>
    <p>Your project '{$assignment['title']}' has been completed and is ready for your review.</p>
    <p>Please click the link below to review the work and confirm completion:</p>
    <p><a href='" . APP_URL . "/client/confirm_job.php?token={$notification_token}&id={$assignment['id']}'>Review and Confirm Project</a></p>
    <p>Once you confirm, payment will be released to the freelancer.</p>
    <p>Best regards,<br>JLM Team</p>
    ";
    
    // Use existing email sending function
    return send_email($assignment['client_email'], $subject, $message);
}

/**
 * Send freelancer payment notification email
 */
function send_freelancer_payment_notification($freelancer_id, $job_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT email, first_name FROM jogigs_users WHERE id = ?");
    $stmt->execute([$freelancer_id]);
    $freelancer = $stmt->fetch();
    
    if (!$freelancer) return false;
    
    $subject = "Payment Released - Job #{$job_id}";
    $message = "
    <h2>Payment Released!</h2>
    <p>Dear {$freelancer['first_name']},</p>
    <p>Great news! The client has confirmed completion of job #{$job_id} and your payment has been released.</p>
    <p>You can view your earnings in your dashboard.</p>
    <p>Thank you for your excellent work!</p>
    <p>Best regards,<br>JLM Team</p>
    ";
    
    return send_email($freelancer['email'], $subject, $message);
}

// Create activity logs table
create_activity_logs_table();

// ========================================
// ADMIN FUNCTIONS
// ========================================

/**
 * Get total users count
 */
function get_total_users() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jogigs_users WHERE status = 'active'");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    } catch (Exception $e) {
        error_log("Failed to get total users: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get total jobs count
 */
function get_total_jobs() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jogigs_jobs WHERE status = 'open'");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    } catch (Exception $e) {
        error_log("Failed to get total jobs: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get total platform earnings
 */
function get_total_platform_earnings() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT SUM(q.quote_amount * 0.20) as total_earnings
            FROM jogigs_job_assignments ja
            JOIN jogigs_quotes q ON ja.quote_id = q.id
            WHERE ja.status = 'completed'
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total_earnings'] ?? 0;
    } catch (Exception $e) {
        error_log("Failed to get total platform earnings: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get pending assignments
 */
function get_pending_assignments() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT ja.*, j.title, u.first_name, u.last_name, q.client_name
            FROM jogigs_job_assignments ja
            JOIN jogigs_jobs j ON ja.job_id = j.id
            JOIN jogigs_users u ON ja.freelancer_id = u.id
            JOIN jogigs_quotes q ON ja.quote_id = q.id
            WHERE ja.status = 'offered'
            ORDER BY ja.assignment_date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get pending assignments: " . $e->getMessage());
        return [];
    }
}

/**
 * Get recent admin activities
 */
function get_recent_admin_activities() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT al.*, u.first_name, u.last_name
            FROM jogigs_activity_logs al
            LEFT JOIN jogigs_users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get recent activities: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if user is admin
 */
function is_admin_user($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT email FROM jogigs_users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        return $user && $user['email'] === 'admin@jogigs.com';
    } catch (Exception $e) {
        error_log("Failed to check admin status: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all users for admin
 */
function get_all_users_for_admin($limit = 50, $offset = 0) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, email, first_name, last_name, phone, status, created_at,
                   (SELECT COUNT(*) FROM jogigs_job_assignments WHERE freelancer_id = u.id) as total_assignments,
                   (SELECT COUNT(*) FROM jogigs_job_assignments WHERE freelancer_id = u.id AND status = 'completed') as completed_assignments
            FROM jogigs_users u
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get all users: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all jobs for admin
 */
function get_all_jobs_for_admin($limit = 50, $offset = 0) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT j.*, q.client_name, q.quote_amount, q.currency,
                   (SELECT COUNT(*) FROM jogigs_job_assignments WHERE job_id = j.id) as total_assignments
            FROM jogigs_jobs j
            LEFT JOIN jogigs_quotes q ON j.id = q.id
            ORDER BY j.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get all jobs: " . $e->getMessage());
        return [];
    }
}

/**
 * Update user status
 */
function update_user_status($user_id, $status) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE jogigs_users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $user_id]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to update user status: " . $e->getMessage());
        return false;
    }
}

/**
 * Update job status
 */
function update_job_status($job_id, $status) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE jogigs_jobs SET status = ? WHERE id = ?");
        $stmt->execute([$status, $job_id]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to update job status: " . $e->getMessage());
        return false;
    }
}

/**
 * Get platform statistics
 */
function get_platform_statistics() {
    global $pdo;
    
    try {
        $stats = [];
        
        // Total users
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jogigs_users WHERE status = 'active'");
        $stmt->execute();
        $stats['total_users'] = $stmt->fetch()['total'] ?? 0;
        
        // Total jobs
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jogigs_jobs");
        $stmt->execute();
        $stats['total_jobs'] = $stmt->fetch()['total'] ?? 0;
        
        // Active jobs
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jogigs_jobs WHERE status = 'open'");
        $stmt->execute();
        $stats['active_jobs'] = $stmt->fetch()['total'] ?? 0;
        
        // Completed assignments
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jogigs_job_assignments WHERE status = 'completed'");
        $stmt->execute();
        $stats['completed_assignments'] = $stmt->fetch()['total'] ?? 0;
        
        // Total earnings
        $stmt = $pdo->prepare("
            SELECT SUM(q.quote_amount * 0.20) as total_earnings
            FROM jogigs_job_assignments ja
            JOIN jogigs_quotes q ON ja.quote_id = q.id
            WHERE ja.status = 'completed'
        ");
        $stmt->execute();
        $stats['total_earnings'] = $stmt->fetch()['total_earnings'] ?? 0;
        
        return $stats;
    } catch (Exception $e) {
        error_log("Failed to get platform statistics: " . $e->getMessage());
        return [];
    }
}

/**
 * Get detailed job information
 */
function get_job_details($job_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT j.*, 
                   (SELECT COUNT(*) FROM jogigs_proposals WHERE job_id = j.id) as proposal_count
            FROM jogigs_jobs j
            WHERE j.id = ?
        ");
        $stmt->execute([$job_id]);
        
        return $stmt->fetch();
        
    } catch (Exception $e) {
        error_log("Failed to get job details: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if user has already submitted a proposal for a job
 */
function check_user_proposal($user_id, $job_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id FROM jogigs_proposals 
            WHERE job_id = ? AND freelancer_id = ?
        ");
        $stmt->execute([$job_id, $user_id]);
        
        return $stmt->fetch() !== false;
        
    } catch (Exception $e) {
        error_log("Failed to check user proposal: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user has saved a job
 */
function is_job_saved($user_id, $job_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id FROM jogigs_saved_jobs 
            WHERE user_id = ? AND job_id = ?
        ");
        $stmt->execute([$user_id, $job_id]);
        
        return $stmt->fetch() !== false;
        
    } catch (Exception $e) {
        error_log("Failed to check if job is saved: " . $e->getMessage());
        return false;
    }
}

/**
 * Save a job for a user
 */
function save_job($user_id, $job_id, $notes = '') {
    global $pdo;
    
    try {
        // Check if already saved
        if (is_job_saved($user_id, $job_id)) {
            return ['success' => false, 'message' => 'Job is already saved'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_saved_jobs (user_id, job_id, notes) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $job_id, $notes]);
        
        log_activity($user_id, 'job_saved', "Job {$job_id} saved");
        
        return ['success' => true, 'message' => 'Job saved successfully'];
        
    } catch (Exception $e) {
        error_log("Failed to save job: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to save job'];
    }
}

/**
 * Unsave a job for a user
 */
function unsave_job($user_id, $job_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM jogigs_saved_jobs 
            WHERE user_id = ? AND job_id = ?
        ");
        $stmt->execute([$user_id, $job_id]);
        
        if ($stmt->rowCount() > 0) {
            log_activity($user_id, 'job_unsaved', "Job {$job_id} unsaved");
            return ['success' => true, 'message' => 'Job removed from saved list'];
        } else {
            return ['success' => false, 'message' => 'Job was not saved'];
        }
        
    } catch (Exception $e) {
        error_log("Failed to unsave job: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to remove job'];
    }
}

/**
 * Get user's saved jobs
 */
function get_saved_jobs($user_id, $limit = 20, $offset = 0) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT j.*, sj.saved_at, sj.notes,
                   (SELECT COUNT(*) FROM jogigs_proposals WHERE job_id = j.id) as proposal_count
            FROM jogigs_saved_jobs sj
            JOIN jogigs_jobs j ON sj.job_id = j.id
            WHERE sj.user_id = ?
            ORDER BY sj.saved_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $limit, $offset]);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Failed to get saved jobs: " . $e->getMessage());
        return [];
    }
}

/**
 * Get count of user's saved jobs
 */
function get_saved_jobs_count($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM jogigs_saved_jobs 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Failed to get saved jobs count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Submit completed work files for a job
 */
function submit_job_files($assignment_id, $freelancer_id, $files_data) {
    global $pdo;
    
    try {
        // Verify assignment belongs to freelancer and is in progress
        $stmt = $pdo->prepare("
            SELECT ja.*, j.title 
            FROM jogigs_job_assignments ja
            JOIN jogigs_jobs j ON ja.job_id = j.id
            WHERE ja.id = ? AND ja.freelancer_id = ? AND ja.status = 'in_progress'
        ");
        $stmt->execute([$assignment_id, $freelancer_id]);
        $assignment = $stmt->fetch();
        
        if (!$assignment) {
            return ['success' => false, 'message' => 'Job assignment not found or not in progress'];
        }
        
        // Create job submission record
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_job_submissions (
                job_assignment_id, freelancer_id, submission_notes, status
            ) VALUES (?, ?, ?, 'submitted')
        ");
        $stmt->execute([
            $assignment_id,
            $freelancer_id,
            $files_data['notes'] ?? ''
        ]);
        
        $submission_id = $pdo->lastInsertId();
        
        // Handle file uploads
        $uploaded_files = [];
        if (isset($files_data['files']) && is_array($files_data['files'])) {
            foreach ($files_data['files'] as $file) {
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $upload_result = upload_job_file($file, $submission_id);
                    if ($upload_result['success']) {
                        $uploaded_files[] = $upload_result['filename'];
                    }
                }
            }
        }
        
        // Update job assignment status to 'submitted_for_review'
        $stmt = $pdo->prepare("
            UPDATE jogigs_job_assignments 
            SET status = 'submitted_for_review', submitted_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$assignment_id]);
        
        // Log activity
        log_activity($freelancer_id, 'job_files_submitted', "Job files submitted for assignment {$assignment_id}");
        
        return [
            'success' => true, 
            'submission_id' => $submission_id,
            'uploaded_files' => $uploaded_files,
            'message' => 'Job files submitted successfully!'
        ];
        
    } catch (Exception $e) {
        error_log("Failed to submit job files: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to submit job files'];
    }
}

/**
 * Upload file for job submission
 */
function upload_job_file($file, $submission_id) {
    $upload_dir = __DIR__ . '/../uploads/job_submissions/' . $submission_id . '/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Define allowed file types for job submissions
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'psd', 'ai', 'eps', 'svg', 'zip', 'rar'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    // Check file size (50MB max for job submissions)
    if ($file['size'] > 50 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File too large (max 50MB)'];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Save file record to database
        global $pdo;
        try {
            $stmt = $pdo->prepare("
                INSERT INTO jogigs_job_submission_files (
                    submission_id, original_filename, stored_filename, file_path, file_size, file_type
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $submission_id,
                $file['name'],
                $filename,
                $filepath,
                $file['size'],
                $file['type']
            ]);
            
            return ['success' => true, 'filename' => $filename, 'path' => $filepath];
        } catch (Exception $e) {
            error_log("Failed to save file record: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to save file record'];
        }
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

/**
 * Get job submission details
 */
function get_job_submission($submission_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT js.*, ja.job_id, j.title, u.first_name, u.last_name
            FROM jogigs_job_submissions js
            JOIN jogigs_job_assignments ja ON js.job_assignment_id = ja.id
            JOIN jogigs_jobs j ON ja.job_id = j.id
            JOIN jogigs_users u ON js.freelancer_id = u.id
            WHERE js.id = ?
        ");
        $stmt->execute([$submission_id]);
        $submission = $stmt->fetch();
        
        if ($submission) {
            // Get files
            $stmt = $pdo->prepare("
                SELECT * FROM jogigs_job_submission_files 
                WHERE submission_id = ?
                ORDER BY uploaded_at ASC
            ");
            $stmt->execute([$submission_id]);
            $submission['files'] = $stmt->fetchAll();
        }
        
        return $submission;
        
    } catch (Exception $e) {
        error_log("Failed to get job submission: " . $e->getMessage());
        return null;
    }
}

/**
 * Admin approves job submission and makes files available to client
 */
function approve_job_submission($submission_id, $admin_id) {
    global $pdo;
    
    try {
        // Get submission details
        $submission = get_job_submission($submission_id);
        if (!$submission) {
            return ['success' => false, 'message' => 'Submission not found'];
        }
        
        // Update submission status
        $stmt = $pdo->prepare("
            UPDATE jogigs_job_submissions 
            SET status = 'approved', approved_at = NOW(), approved_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$admin_id, $submission_id]);
        
        // Update job assignment status to completed
        $stmt = $pdo->prepare("
            UPDATE jogigs_job_assignments 
            SET status = 'completed', completed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$submission['job_assignment_id']]);
        
        // Create client notification for file download
        $notification_token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("
            INSERT INTO jogigs_client_notifications (
                job_assignment_id, client_email, client_name, 
                notification_type, notification_token, submission_id
            ) VALUES (?, ?, ?, 'files_ready', ?, ?)
        ");
        $stmt->execute([
            $submission['job_assignment_id'],
            $submission['client_email'] ?? 'client@example.com',
            $submission['client_name'] ?? 'Client',
            $notification_token,
            $submission_id
        ]);
        
        // Send email notification to client
        send_client_files_ready_notification($submission, $notification_token);
        
        log_activity($admin_id, 'job_submission_approved', "Job submission {$submission_id} approved");
        
        return ['success' => true, 'notification_token' => $notification_token];
        
    } catch (Exception $e) {
        error_log("Failed to approve job submission: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to approve submission'];
    }
}

/**
 * Send client notification that files are ready for download
 */
function send_client_files_ready_notification($submission, $notification_token) {
    $subject = "Your project files are ready for download!";
    $message = "
    <h2>Project Files Ready!</h2>
    <p>Dear {$submission['client_name']},</p>
    <p>Your project '{$submission['title']}' has been completed and the files are ready for download.</p>
    <p>Please click the link below to download your files:</p>
    <p><a href='" . APP_URL . "/client/download_files.php?token={$notification_token}&id={$submission['id']}'>Download Project Files</a></p>
    <p>After downloading, please review the work and confirm completion to release payment to the freelancer.</p>
    <p>Best regards,<br>JLM Team</p>
    ";
    
    return send_email($submission['client_email'] ?? 'client@example.com', $subject, $message);
}

/**
 * Get freelancer's job submissions
 */
function get_freelancer_submissions($freelancer_id, $limit = 10, $offset = 0) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT js.*, j.title, ja.job_id
            FROM jogigs_job_submissions js
            JOIN jogigs_job_assignments ja ON js.job_assignment_id = ja.id
            JOIN jogigs_jobs j ON ja.job_id = j.id
            WHERE js.freelancer_id = ?
            ORDER BY js.submitted_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$freelancer_id, $limit, $offset]);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Failed to get freelancer submissions: " . $e->getMessage());
        return [];
    }
}

/**
 * Create job submissions table if it doesn't exist
 */
function create_job_submissions_table() {
    global $pdo;
    
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS jogigs_job_submissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                job_assignment_id INT NOT NULL,
                freelancer_id INT NOT NULL,
                submission_notes TEXT,
                status ENUM('submitted', 'under_review', 'approved', 'rejected', 'revision_requested') DEFAULT 'submitted',
                submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                reviewed_at TIMESTAMP NULL,
                reviewed_by INT,
                approved_at TIMESTAMP NULL,
                approved_by INT,
                rejection_reason TEXT,
                FOREIGN KEY (job_assignment_id) REFERENCES jogigs_job_assignments(id) ON DELETE CASCADE,
                FOREIGN KEY (freelancer_id) REFERENCES jogigs_users(id) ON DELETE CASCADE,
                FOREIGN KEY (reviewed_by) REFERENCES jogigs_users(id) ON DELETE SET NULL,
                FOREIGN KEY (approved_by) REFERENCES jogigs_users(id) ON DELETE SET NULL,
                INDEX idx_status (status),
                INDEX idx_submitted_at (submitted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS jogigs_job_submission_files (
                id INT AUTO_INCREMENT PRIMARY KEY,
                submission_id INT NOT NULL,
                original_filename VARCHAR(255) NOT NULL,
                stored_filename VARCHAR(255) NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                file_size INT,
                file_type VARCHAR(100),
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (submission_id) REFERENCES jogigs_job_submissions(id) ON DELETE CASCADE,
                INDEX idx_submission_id (submission_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        return true;
        
    } catch (Exception $e) {
        error_log("Failed to create job submissions table: " . $e->getMessage());
        return false;
    }
}

// Create job submissions table
create_job_submissions_table();

/**
 * Get freelancers with pagination
 */
function get_freelancers_with_pagination($limit = 10, $offset = 0) {
    global $pdo;
    
    try {
        // First, let's get basic freelancer data
        $stmt = $pdo->prepare("
            SELECT u.*
            FROM jogigs_users u
            WHERE u.email != 'admin@jogigs.com'
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $freelancers = $stmt->fetchAll();
        
        error_log("Debug: Found " . count($freelancers) . " freelancers in basic query");
        
        // Now add the additional data for each freelancer
        foreach ($freelancers as &$freelancer) {
                    // Get active jobs count
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM jogigs_job_assignments 
                WHERE freelancer_id = ? AND status IN ('offered', 'accepted', 'in_progress')
            ");
            $stmt->execute([$freelancer['id']]);
            $result = $stmt->fetch();
            $freelancer['active_jobs'] = $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getting active jobs for freelancer {$freelancer['id']}: " . $e->getMessage());
            $freelancer['active_jobs'] = 0;
        }
            
            // Get total earnings
            try {
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(amount), 0) as total 
                    FROM jogigs_earnings 
                    WHERE freelancer_id = ?
                ");
                $stmt->execute([$freelancer['id']]);
                $result = $stmt->fetch();
                $freelancer['total_earnings'] = $result['total'] ?? 0;
            } catch (Exception $e) {
                error_log("Error getting earnings for freelancer {$freelancer['id']}: " . $e->getMessage());
                $freelancer['total_earnings'] = 0;
            }
        }
        
        error_log("Debug: Returning " . count($freelancers) . " freelancers");
        return $freelancers;
        
    } catch (Exception $e) {
        error_log("Failed to get freelancers: " . $e->getMessage());
        return [];
    }
}

/**
 * Get total freelancers count
 */
function get_total_freelancers_count() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM jogigs_users 
            WHERE email != 'admin@jogigs.com'
        ");
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Failed to get freelancers count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get available jobs for admin assignment
 */
function get_available_jobs_for_admin() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT j.id, j.title, j.budget_min, j.budget_max, j.status, j.deadline
            FROM jogigs_jobs j
            WHERE j.status = 'open'
            AND j.id NOT IN (
                SELECT DISTINCT job_id 
                FROM jogigs_job_assignments 
                WHERE status IN ('offered', 'accepted', 'in_progress')
            )
            ORDER BY j.created_at DESC
        ");
        $stmt->execute();
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Failed to get available jobs: " . $e->getMessage());
        return [];
    }
}

/**
 * Update freelancer status
 */
function update_freelancer_status($freelancer_id, $status) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE jogigs_users 
            SET status = ?, updated_at = NOW()
            WHERE id = ? AND email != 'admin@jogigs.com'
        ");
        $stmt->execute([$status, $freelancer_id]);
        
        if ($stmt->rowCount() > 0) {
            log_activity($freelancer_id, 'status_updated', "Freelancer status updated to {$status}");
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Freelancer not found'];
        
    } catch (Exception $e) {
        error_log("Failed to update freelancer status: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update status'];
    }
}


?> 