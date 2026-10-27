<?php
/**
 * JLM Creative Media - Functions Library
 * Contains reusable functions for the website
 */

// Include performance functions if not already included
if (!function_exists('cache_get')) {
    require_once __DIR__ . '/performance.php';
}

/**
 * Validate CSRF token
 * @param string $token Token to validate
 * @return bool True if valid, false otherwise
 */
function validate_csrf_token($token) {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token
 * @return string Generated token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Sanitize input data
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email address
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate unique ticket number
 * @param string $prefix Ticket prefix (CT, BK, TM)
 * @return string Unique ticket number
 */
function generate_ticket_number($prefix = 'JLM') {
    global $pdo;
    
    $timestamp = date('YmdHis');
    $random = strtoupper(substr(md5(uniqid()), 0, 6));
    $ticket_number = $prefix . '-' . $timestamp . '-' . $random;
    
    // Ensure uniqueness by checking database
    if ($pdo) {
        // Only check booking_submissions table as it's the only one with ticket_number column
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking_submissions WHERE ticket_number = ?");
        $stmt->execute([$ticket_number]);
        $booking_count = $stmt->fetchColumn();
        
        // If ticket number already exists, generate a new one
        if ($booking_count > 0) {
            return generate_ticket_number($prefix);
        }
    }
    
    return $ticket_number;
}

/**
 * Create ticket tracking record
 * @param string $ticket_number Unique ticket number
 * @param string $form_type Form type (contact, booking, testimonial)
 * @param int $form_id ID of the form submission
 * @return bool True if created successfully
 */
function create_ticket_tracking($ticket_number, $form_type, $form_id) {
    global $pdo;
    
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO ticket_tracking (ticket_number, form_type, form_id, status, priority) VALUES (?, ?, ?, 'open', 'medium')");
        $stmt->execute([$ticket_number, $form_type, $form_id]);
        
        // Log the ticket creation activity
        $stmt = $pdo->prepare("INSERT INTO ticket_activities (ticket_number, activity_type, description) VALUES (?, 'created', 'Ticket created for new submission')");
        $stmt->execute([$ticket_number]);
        
        return true;
    } catch (Exception $e) {
        error_log("Ticket tracking creation failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email using PHPMailer
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @return bool True if sent successfully, false otherwise
 */
function send_email($to, $subject, $message) {
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        require_once __DIR__ . '/../config/email_utils.php';

        $normalizedMessage = (string) $message;
        $looksLikeHtmlDocument = preg_match('/<html[\s>]|<body[\s>]/i', $normalizedMessage) === 1;
        $containsHtmlTags = preg_match('/<[^>]+>/', $normalizedMessage) === 1;

        if (!$looksLikeHtmlDocument) {
            if ($containsHtmlTags) {
                $normalizedMessage = getCompleteEmailTemplate($subject, $normalizedMessage);
            } else {
                $formattedMessage = nl2br(htmlspecialchars($normalizedMessage, ENT_QUOTES, 'UTF-8'));
                $normalizedMessage = getCompleteEmailTemplate(
                    $subject,
                    '<div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:18px 20px;color:#1f2937;">' . $formattedMessage . '</div>'
                );
            }
        }

        return sendEmail($to, $subject, $normalizedMessage);
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Enhanced input validation with security features
 * @param mixed $data Data to validate
 * @param string $type Validation type (text, email, int, url, phone)
 * @param array $options Additional validation options
 * @return array ['valid' => bool, 'value' => mixed, 'error' => string]
 */
function validate_input($data, $type = 'text', $options = []) {
    $result = ['valid' => false, 'value' => null, 'error' => ''];
    
    // Include security features if not already included
    if (!function_exists('enhanced_sanitize')) {
        require_once __DIR__ . '/security.php';
    }
    
    // Enhanced sanitization
    $data = enhanced_sanitize($data, $type);
    
    // Basic sanitization
    if (is_string($data)) {
        $data = trim($data);
        $data = stripslashes($data);
    }
    
    // Check if required
    if (isset($options['required']) && $options['required'] && empty($data)) {
        $result['error'] = 'This field is required.';
        return $result;
    }
    
    // Allow empty if not required
    if (empty($data) && !isset($options['required'])) {
        $result['valid'] = true;
        $result['value'] = '';
        return $result;
    }
    
    // Security: Check for suspicious patterns
    if (is_string($data)) {
        $suspicious_patterns = [
            '/<script/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/union\s+select/i',
            '/drop\s+table/i',
            '/insert\s+into/i',
            '/delete\s+from/i',
            '/update\s+set/i',
            '/--/',
            '/\/\*.*\*\//'
        ];
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $data)) {
                log_security_event('suspicious_input', "Suspicious pattern detected in input", [
                    'pattern' => $pattern,
                    'input' => substr($data, 0, 100)
                ]);
                $result['error'] = 'Invalid input detected.';
                return $result;
            }
        }
    }
    
    switch ($type) {
        case 'email':
            if (!filter_var($data, FILTER_VALIDATE_EMAIL)) {
                $result['error'] = 'Please enter a valid email address.';
                return $result;
            }
            // Additional email validation
            if (strlen($data) > 254) { // RFC 5321 limit
                $result['error'] = 'Email address is too long.';
                return $result;
            }
            break;
            
        case 'int':
            if (!filter_var($data, FILTER_VALIDATE_INT)) {
                $result['error'] = 'Please enter a valid number.';
                return $result;
            }
            $data = (int)$data;
            if (isset($options['min']) && $data < $options['min']) {
                $result['error'] = "Value must be at least {$options['min']}.";
                return $result;
            }
            if (isset($options['max']) && $data > $options['max']) {
                $result['error'] = "Value must be at most {$options['max']}.";
                return $result;
            }
            break;
            
        case 'url':
            if (!filter_var($data, FILTER_VALIDATE_URL)) {
                $result['error'] = 'Please enter a valid URL.';
                return $result;
            }
            // Additional URL validation
            $parsed_url = parse_url($data);
            if (!$parsed_url || !isset($parsed_url['scheme'])) {
                $result['error'] = 'Please enter a valid URL with protocol (http:// or https://).';
                return $result;
            }
            if (!in_array($parsed_url['scheme'], ['http', 'https'])) {
                $result['error'] = 'Only HTTP and HTTPS URLs are allowed.';
                return $result;
            }
            break;
            
        case 'phone':
            // Enhanced phone validation
            $phone = preg_replace('/[^0-9+\-\(\)\s]/', '', $data);
            if (strlen($phone) < 10) {
                $result['error'] = 'Please enter a valid phone number.';
                return $result;
            }
            if (strlen($phone) > 20) {
                $result['error'] = 'Phone number is too long.';
                return $result;
            }
            $data = $phone;
            break;
            
        case 'text':
        default:
            // Length validation
            if (isset($options['min_length']) && strlen($data) < $options['min_length']) {
                $result['error'] = "Text must be at least {$options['min_length']} characters long.";
                return $result;
            }
            if (isset($options['max_length']) && strlen($data) > $options['max_length']) {
                $result['error'] = "Text must be no more than {$options['max_length']} characters long.";
                return $result;
            }
            
            // Pattern validation
            if (isset($options['pattern']) && !preg_match($options['pattern'], $data)) {
                $result['error'] = isset($options['pattern_error']) ? $options['pattern_error'] : 'Invalid format.';
                return $result;
            }
            
            // Additional text validation
            if (strlen($data) > 10000) { // Reasonable limit for text input
                $result['error'] = 'Text is too long.';
                return $result;
            }
            
            // HTML encoding for security (already done by enhanced_sanitize)
            break;
    }
    
    $result['valid'] = true;
    $result['value'] = $data;
    return $result;
}

/**
 * Process contact form submission
 * @param array $data Form data
 * @return array Result with success status and message
 */
function process_contact_form($data) {
    global $pdo;
    
    try {
        // Validate required fields
        $required_fields = ['name', 'email', 'message'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => ucfirst($field) . ' is required.'];
            }
        }
        
        // Validate email
        if (!validate_email($data['email'])) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }
        
        // Sanitize data
        $name = sanitize_input($data['name']);
        $email = sanitize_input($data['email']);
        $phone = isset($data['phone']) ? sanitize_input($data['phone']) : '';
        $subject = isset($data['subject']) ? sanitize_input($data['subject']) : 'Contact Form Submission';
        $message = sanitize_input($data['message']);
        
        // Save to database if available
        if ($pdo) {
            // Generate unique ticket number
            $ticket_number = generate_ticket_number('CT');
            
            $stmt = $pdo->prepare('INSERT INTO contact_submissions (ticket_number, name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$ticket_number, $name, $email, $phone, $subject, $message]);
            
            // Get the inserted ID for ticket tracking
            $form_id = $pdo->lastInsertId();
            
            // Create ticket tracking record
            create_ticket_tracking($ticket_number, 'contact', $form_id);
        }
        
        // Send email notification
        $email_message = "New contact form submission:\n\n";
        $email_message .= "Ticket Number: $ticket_number\n";
        $email_message .= "Name: $name\n";
        $email_message .= "Email: $email\n";
        if ($phone) $email_message .= "Phone: $phone\n";
        $email_message .= "Subject: $subject\n";
        $email_message .= "Message: $message\n";
        
        send_email(defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'support@jlm.com.ng', 'New Contact Form Submission', $email_message);
        
        return ['success' => true, 'message' => 'Thank you! Your message has been sent successfully. We\'ll get back to you soon.'];
        
    } catch (Exception $e) {
        error_log("Contact form error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred. Please try again later.'];
    }
}

/**
 * Process booking form submission
 * @param array $data Form data
 * @return array Result with success status and message
 */
function process_booking_form($data) {
    global $pdo;
    
    try {
        // Validate required fields
        $required_fields = ['firstName', 'lastName', 'email', 'services', 'projectDescription', 'consultationDate', 'consultationTime', 'consultationMethod'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required.'];
            }
        }
        
        // Validate email
        if (!validate_email($data['email'])) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }
        
        // Sanitize data
        $firstName = sanitize_input($data['firstName']);
        $lastName = sanitize_input($data['lastName']);
        $email = sanitize_input($data['email']);
        $phone = isset($data['phone']) ? sanitize_input($data['phone']) : '';
        $services = is_array($data['services']) ? implode(', ', array_map('sanitize_input', $data['services'])) : sanitize_input($data['services']);
        $projectDescription = sanitize_input($data['projectDescription']);
        $consultationDate = sanitize_input($data['consultationDate']);
        $consultationTime = sanitize_input($data['consultationTime']);
        $consultationMethod = sanitize_input($data['consultationMethod']);
        $budgetRange = isset($data['budgetRange']) ? sanitize_input($data['budgetRange']) : '';
        $timeline = isset($data['timeline']) ? sanitize_input($data['timeline']) : '';
        $hearAbout = isset($data['hearAbout']) ? sanitize_input($data['hearAbout']) : '';
        
        // Save to database if available
        if ($pdo) {
            $fullName = $firstName . ' ' . $lastName;
            $consultationDateTime = $consultationDate . ' ' . $consultationTime;
            
            // Generate unique ticket number
            $ticket_number = generate_ticket_number('BK');
            
            $stmt = $pdo->prepare("INSERT INTO booking_submissions (ticket_number, name, email, phone, service, project_description, datetime, consultation_method, budget, timeline, hear_about, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$ticket_number, $fullName, $email, $phone, $services, $projectDescription, $consultationDateTime, $consultationMethod, $budgetRange, $timeline, $hearAbout]);
            
            // Get the inserted ID for ticket tracking
            $form_id = $pdo->lastInsertId();
            
            // Create ticket tracking record
            create_ticket_tracking($ticket_number, 'booking', $form_id);
        }
        
        // Send email notification
        $email_message = "New booking consultation request:\n\n";
        $email_message .= "Ticket Number: $ticket_number\n";
        $email_message .= "Name: $firstName $lastName\n";
        $email_message .= "Email: $email\n";
        if ($phone) $email_message .= "Phone: $phone\n";
        $email_message .= "Services: $services\n";
        $email_message .= "Project Description: $projectDescription\n";
        $email_message .= "Consultation Date: $consultationDate\n";
        $email_message .= "Consultation Time: $consultationTime\n";
        $email_message .= "Consultation Method: $consultationMethod\n";
        if ($budgetRange) $email_message .= "Budget Range: $budgetRange\n";
        if ($timeline) $email_message .= "Timeline: $timeline\n";
        if ($hearAbout) $email_message .= "How they heard about us: $hearAbout\n";
        
        send_email(defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'support@jlm.com.ng', 'New Booking Consultation Request', $email_message);
        
        return ['success' => true, 'message' => 'Thank you! Your booking request has been submitted successfully. We\'ll contact you soon to confirm your consultation.'];
        
    } catch (Exception $e) {
        error_log("Booking form error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred. Please try again later.'];
    }
}

/**
 * Process testimonial submission
 * @param array $data Form data
 * @return array Result with success status and message
 */
function process_testimonial_form($data) {
    global $pdo;
    
    try {
        // Validate required fields
        $required_fields = ['name', 'rating', 'text'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => ucfirst($field) . ' is required.'];
            }
        }
        
        // Validate rating
        $rating = (int)$data['rating'];
        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'message' => 'Please select a valid rating.'];
        }
        
        // Sanitize data
        $name = sanitize_input($data['name']);
        $role = isset($data['role']) ? sanitize_input($data['role']) : '';
        $company = isset($data['company']) ? sanitize_input($data['company']) : '';
        $text = sanitize_input($data['text']);
        
        // Generate unique ticket number
        $ticket_number = generate_ticket_number('TM');
        
        // Insert into database
        $stmt = $pdo->prepare('INSERT INTO testimonials (ticket_number, name, role, company, rating, text, status) VALUES (?, ?, ?, ?, ?, ?, "pending")');
        $stmt->execute([$ticket_number, $name, $role, $company, $rating, $text]);
        cache_delete('approved_testimonials_10');
        
        // Get the inserted ID for ticket tracking
        $form_id = $pdo->lastInsertId();
        
        // Create ticket tracking record
        create_ticket_tracking($ticket_number, 'testimonial', $form_id);
        
        // Send email notification to admin
        $email_message = "New testimonial submission received:\n\n";
        $email_message .= "Ticket Number: $ticket_number\n";
        $email_message .= "Name: $name\n";
        $email_message .= "Role: " . ($role ?: 'Not specified') . "\n";
        $email_message .= "Company: " . ($company ?: 'Not specified') . "\n";
        $email_message .= "Rating: $rating/5 stars\n";
        $email_message .= "Testimonial: $text\n\n";
        $email_message .= "Please review and approve/reject this testimonial in the admin panel.\n";
        $email_message .= "Admin Panel: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]/admin_dashboard.php?tab=testimonials";
        
        send_email(defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'support@jlm.com.ng', 'New Testimonial Submission - Review Required', $email_message);
        
        return ['success' => true, 'message' => 'Thank you! Your testimonial has been submitted and will be reviewed before publishing.'];
        
    } catch (Exception $e) {
        error_log("Testimonial form error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred. Please try again later.'];
    }
}

/**
 * Get approved testimonials for display
 * @param int $limit Number of testimonials to return
 * @return array Array of testimonials
 */
function get_approved_testimonials($limit = 10) {
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        global $pdo;
        try {
            $stmt = $pdo->prepare('SELECT * FROM testimonials WHERE status = "approved" ORDER BY created_at DESC LIMIT :limit');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching testimonials: " . $e->getMessage());
            return [];
        }
    }

    // Use caching for better performance
    $cache_key = "approved_testimonials_$limit";
    $cached_result = cache_get($cache_key);
    
    if ($cached_result !== false) {
        return $cached_result;
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare('SELECT * FROM testimonials WHERE status = "approved" ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Cache for 15 minutes
        cache_set($cache_key, $result, 900);
        
        return $result;
    } catch (Exception $e) {
        error_log("Error fetching testimonials: " . $e->getMessage());
        return [];
    }
}

/**
 * Get portfolio items for display
 * @param mixed $parent_id Parent ID to filter by. NULL for main categories, 'all' for everything, or INT for specific children.
 * @param int $limit Number of items to return
 * @return array Array of portfolio items
 */
function get_portfolio_items($parent_id = null, $limit = 100) {
    global $pdo;
    
    try {
        if ($parent_id === 'all') {
            $stmt = $pdo->prepare('SELECT * FROM portfolio ORDER BY sort_order ASC, created_at DESC LIMIT :limit');
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($parent_id === null) {
            $stmt = $pdo->prepare('SELECT * FROM portfolio WHERE parent_id IS NULL ORDER BY sort_order ASC, created_at DESC LIMIT :limit');
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare('SELECT * FROM portfolio WHERE parent_id = :parent_id ORDER BY sort_order ASC, created_at DESC LIMIT :limit');
            $stmt->bindValue(':parent_id', (int)$parent_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching portfolio items: " . $e->getMessage());
        return [];
    }
}

/**
 * Extract a YouTube video ID from a URL when present.
 * @param string $url
 * @return string|null
 */
function get_youtube_video_id($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return null;
    }

    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * Build a preview image URL for a portfolio external link.
 * @param string $url
 * @return string|null
 */
function get_portfolio_link_preview_image($url) {
    $url = trim((string) $url);
    if ($url === '' || !preg_match('/^https?:\/\//i', $url)) {
        return null;
    }

    $youtubeId = get_youtube_video_id($url);
    if ($youtubeId) {
        return 'https://img.youtube.com/vi/' . rawurlencode($youtubeId) . '/hqdefault.jpg';
    }

    return 'https://s.wordpress.com/mshots/v1/' . rawurlencode($url) . '?w=1200';
}

/**
 * Resolve the image that should be displayed for a portfolio item.
 * Falls back to the external link preview when no thumbnail is stored.
 * @param array $item
 * @return string|null
 */
function get_portfolio_display_image($item) {
    $image = trim((string) ($item['image'] ?? ''));
    if ($image !== '') {
        return $image;
    }

    return get_portfolio_link_preview_image($item['external_url'] ?? '');
}



/**
 * Validate and sanitize file upload
 * @param array $file $_FILES array element
 * @param array $options Validation options
 * @return array ['valid' => bool, 'path' => string, 'error' => string]
 */
function validate_file_upload($file, $options = []) {
    $result = ['valid' => false, 'path' => '', 'error' => ''];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'File upload failed.';
        return $result;
    }
    
    // Check file size
    $max_size = $options['max_size'] ?? 5 * 1024 * 1024; // 5MB default
    if ($file['size'] > $max_size) {
        $result['error'] = 'File is too large. Maximum size is ' . ($max_size / 1024 / 1024) . 'MB.';
        return $result;
    }
    
    // Check file type
    $allowed_types = $options['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_types)) {
        $result['error'] = 'File type not allowed. Allowed types: ' . implode(', ', $allowed_types);
        return $result;
    }
    
    // Generate safe filename
    $safe_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $upload_dir = $options['upload_dir'] ?? 'uploads/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $upload_path = $upload_dir . $safe_filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        $result['valid'] = true;
        $result['path'] = $upload_path;
    } else {
        $result['error'] = 'Failed to save uploaded file.';
    }
    
    return $result;
}

/**
 * Generate absolute URL for an asset
 * @param string $path Relative path to the asset (e.g. 'assets/images/logo.png')
 * @return string Absolute URL
 */
function asset_url($path) {
    $path = (string)$path;
    if (strpos($path, 'http') === 0 || strpos($path, '//') === 0) {
        return $path;
    }

    $path = ltrim($path, '/');

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = rtrim(dirname($scriptName), '/');
    if ($scriptDir === '.' || $scriptDir === '/') {
        $scriptDir = '';
    }

    $configuredBasePath = trim((string) getenv('JLM_BASE_PATH'));
    if ($configuredBasePath !== '') {
        $configuredBasePath = '/' . trim($configuredBasePath, '/');
        if ($configuredBasePath === '/') {
            $configuredBasePath = '';
        }
    } else {
        $configuredBasePath = $scriptDir;
    }

    $siteUrl = trim((string) getenv('JLM_SITE_URL'));
    if ($host !== '') {
        $origin = $scheme . '://' . $host;
    } elseif ($siteUrl !== '') {
        $origin = rtrim($siteUrl, '/');
    } else {
        $origin = '';
    }

    return rtrim($origin . $configuredBasePath, '/') . '/' . $path;
}

/**
 * Log user activity
 * @param int $user_id User ID
 * @param string $action Action performed
 * @param string $details Additional details
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
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Retrieve user's cookie consent preferences from request cookie
 * Compliant with GDPR / ePrivacy / NDPA 2023
 * @return array|null Returns array of consent choices or null if not yet configured
 */
function jlm_get_cookie_consent() {
    if (!isset($_COOKIE['jlm_cookie_consent'])) {
        return null;
    }
    $raw = (string)$_COOKIE['jlm_cookie_consent'];
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = json_decode(rawurldecode($raw), true);
    }
    if (!is_array($data)) {
        return null;
    }
    return [
        'version' => (string)($data['version'] ?? '1.0'),
        'necessary' => true,
        'functional' => !empty($data['functional']),
        'analytics' => !empty($data['analytics']),
        'marketing' => !empty($data['marketing']),
        'timestamp' => (string)($data['timestamp'] ?? ''),
    ];
}

/**
 * Check if the user has given consent for a specific category of cookies
 * @param string $category 'necessary'|'functional'|'analytics'|'marketing'
 * @return bool
 */
function jlm_has_cookie_consent($category = 'necessary') {
    $category = strtolower(trim((string)$category));
    if ($category === 'necessary' || $category === 'essential') {
        return true;
    }
    $consent = jlm_get_cookie_consent();
    if ($consent === null) {
        return false;
    }
    return !empty($consent[$category]);
}

/**
 * Return organizations, clients, and partners worked with for rolling badges.
 * @return array
 */
function get_worked_with_organizations() {
    return [
        [
            'name' => 'Nigeria Reads Initiative',
            'short_name' => 'Nigeria Reads',
            'url' => 'http://nigeriareads.ng/',
            'logo' => 'assets/images/clients/nigeriareads.png',
            'tag' => 'Literacy Advocacy & Book Drives',
            'pill' => 'Partner',
            'pill_class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'
        ],
        [
            'name' => 'KrossKhana',
            'short_name' => 'KrossKhana',
            'url' => 'https://krosskhana.org/',
            'logo' => 'assets/images/clients/krosskhana.png',
            'tag' => 'Creative Media & Productions',
            'pill' => 'Featured Client',
            'pill_class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'
        ],
        [
            'name' => 'Naija ReadFest',
            'short_name' => 'Naija ReadFest',
            'url' => 'http://naijareadfest.ng/',
            'logo' => 'assets/images/clients/naijareadfest.png',
            'tag' => '431h Longest Reading Marathon',
            'pill' => 'Record Breaker',
            'pill_class' => 'bg-amber-50 text-amber-700 border-amber-200'
        ],
        [
            'name' => 'Guinness World Records™',
            'short_name' => 'GWR Project',
            'url' => 'https://naijareadfest.ng/',
            'logo' => 'assets/images/clients/gwr.png',
            'tag' => 'Official Technical Documentation',
            'pill' => 'World Record',
            'pill_class' => 'bg-rose-50 text-rose-700 border-rose-200'
        ],
        [
            'name' => 'Viva Doxa Music School',
            'short_name' => 'Viva Doxa',
            'url' => 'https://vivadoxamusic.com/',
            'logo' => 'assets/images/clients/vivadoxa.png',
            'tag' => 'Music Academy & Production',
            'pill' => 'Client',
            'pill_class' => 'bg-pink-50 text-pink-700 border-pink-200'
        ],
        [
            'name' => 'Deeper Life High School',
            'short_name' => 'DLHS',
            'url' => 'https://deeperlifehighschool.org/',
            'logo' => 'assets/images/clients/dlhs.webp',
            'tag' => 'Premier Secondary Education',
            'pill' => 'Education',
            'pill_class' => 'bg-blue-50 text-blue-700 border-blue-200'
        ],
        [
            'name' => 'The Famous Isaacs',
            'short_name' => 'Famous Isaacs',
            'url' => 'https://thefamousisaacs.com/',
            'logo' => 'assets/images/clients/thefamousisaacs.png',
            'tag' => 'Docufilm & Visual Storytelling',
            'pill' => 'Creative Partner',
            'pill_class' => 'bg-purple-50 text-purple-700 border-purple-200'
        ],
        [
            'name' => 'JS-Gold Digital World',
            'short_name' => 'JS-Gold',
            'url' => 'https://jsgold.org/',
            'logo' => 'assets/images/clients/jsgold.png',
            'tag' => 'Enterprise IT & Cloud Systems',
            'pill' => 'Tech Partner',
            'pill_class' => 'bg-cyan-50 text-cyan-700 border-cyan-200'
        ],
        [
            'name' => 'SOKATWA',
            'short_name' => 'SOKATWA',
            'url' => 'https://www.sokatwa.org/',
            'logo' => 'assets/images/clients/sokatwa.png',
            'tag' => 'Women Leadership & Dev',
            'pill' => 'Client',
            'pill_class' => 'bg-teal-50 text-teal-700 border-teal-200'
        ],
        [
            'name' => 'WAYTI',
            'short_name' => 'WAYTI',
            'url' => 'http://wayti.org/',
            'logo' => 'assets/images/clients/wayti.png',
            'tag' => 'Women & Youth Transformation Initiative',
            'pill' => 'Initiative',
            'pill_class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'
        ],
        [
            'name' => 'B&R Spices',
            'short_name' => 'B&R Spices',
            'url' => 'https://bandrspices.com/',
            'logo' => 'assets/images/clients/bandrspices.png',
            'tag' => 'Agro-Commodity & Spice Export',
            'pill' => 'Commerce',
            'pill_class' => 'bg-orange-50 text-orange-700 border-orange-200'
        ],
        [
            'name' => 'DCLM Kaduna Broadcast',
            'short_name' => 'DCLM',
            'url' => 'https://youtube.com/@dclmkadunahq',
            'logo' => 'assets/images/clients/dclm.png',
            'tag' => 'Live Streaming & Media Broadcast',
            'pill' => 'Broadcast',
            'pill_class' => 'bg-red-50 text-red-700 border-red-200'
        ],
        [
            'name' => 'JLM Creative Media',
            'short_name' => 'JLM Media',
            'url' => 'https://jlm.com.ng/',
            'logo' => 'assets/images/clients/jlm.png',
            'tag' => 'Creative Direction & Digital Hub',
            'pill' => 'Agency Hub',
            'pill_class' => 'bg-violet-50 text-[#1b2299] border-violet-200'
        ]
    ];
}


