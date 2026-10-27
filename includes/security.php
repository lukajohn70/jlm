<?php
/**
 * Advanced Security Features
 * Rate limiting, IP tracking, and security monitoring
 */

/**
 * Rate limiting implementation
 * @param string $action The action being rate limited (e.g., 'contact_form', 'login')
 * @param int $max_attempts Maximum attempts allowed
 * @param int $time_window Time window in seconds
 * @return array ['allowed' => bool, 'remaining' => int, 'reset_time' => int]
 */
function check_rate_limit($action, $max_attempts = 5, $time_window = 300) {
    $ip = get_client_ip();
    $cache_key = "rate_limit_{$action}_{$ip}";
    
    // Get current attempts
    $attempts = get_rate_limit_attempts($cache_key);
    $current_time = time();
    
    // Clean old attempts
    $attempts = array_filter($attempts, function($timestamp) use ($current_time, $time_window) {
        return ($current_time - $timestamp) < $time_window;
    });
    
    // Check if limit exceeded
    if (count($attempts) >= $max_attempts) {
        $oldest_attempt = min($attempts);
        $reset_time = $oldest_attempt + $time_window;
        
        return [
            'allowed' => false,
            'remaining' => 0,
            'reset_time' => $reset_time,
            'wait_time' => $reset_time - $current_time
        ];
    }
    
    // Add current attempt
    $attempts[] = $current_time;
    set_rate_limit_attempts($cache_key, $attempts);
    
    return [
        'allowed' => true,
        'remaining' => $max_attempts - count($attempts),
        'reset_time' => $current_time + $time_window
    ];
}

/**
 * Record a rate limit attempt
 * @param string $action The action being rate limited
 */
function record_rate_limit_attempt($action) {
    $ip = get_client_ip();
    $cache_key = "rate_limit_{$action}_{$ip}";
    
    $attempts = get_rate_limit_attempts($cache_key);
    $attempts[] = time();
    set_rate_limit_attempts($cache_key, $attempts);
}

/**
 * Get rate limit attempts from cache/file
 * @param string $key Cache key
 * @return array Array of timestamps
 */
function get_rate_limit_attempts($key) {
    $cache_file = __DIR__ . "/../cache/rate_limits/" . md5($key) . ".json";
    
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        return $data['attempts'] ?? [];
    }
    
    return [];
}

/**
 * Set rate limit attempts to cache/file
 * @param string $key Cache key
 * @param array $attempts Array of timestamps
 */
function set_rate_limit_attempts($key, $attempts) {
    $cache_dir = __DIR__ . "/../cache/rate_limits/";
    
    // Create cache directory if it doesn't exist
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    $cache_file = $cache_dir . md5($key) . ".json";
    $data = [
        'attempts' => $attempts,
        'created' => time()
    ];
    
    file_put_contents($cache_file, json_encode($data));
}

/**
 * Get client IP address
 * @return string Client IP
 */
function get_client_ip() {
    $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
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
    
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Clean old rate limit files
 * @param int $max_age Maximum age in seconds (default: 1 hour)
 */
function clean_rate_limit_cache($max_age = 3600) {
    $cache_dir = __DIR__ . "/../cache/rate_limits/";
    
    if (!is_dir($cache_dir)) {
        return;
    }
    
    $current_time = time();
    $files = glob($cache_dir . "*.json");
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['created']) && ($current_time - $data['created']) > $max_age) {
                unlink($file);
            }
        }
    }
}

/**
 * Enhanced input sanitization
 * @param mixed $data Data to sanitize
 * @param string $type Type of data (text, email, url, int, float)
 * @return mixed Sanitized data
 */
function enhanced_sanitize($data, $type = 'text') {
    if (is_array($data)) {
        return array_map(function($item) use ($type) {
            return enhanced_sanitize($item, $type);
        }, $data);
    }
    
    if (!is_string($data)) {
        return $data;
    }
    
    $data = trim($data);
    
    switch ($type) {
        case 'email':
            return filter_var($data, FILTER_SANITIZE_EMAIL);
            
        case 'url':
            return filter_var($data, FILTER_SANITIZE_URL);
            
        case 'int':
            return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
            
        case 'float':
            return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
        case 'text':
        default:
            // Remove null bytes and other control characters
            $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
            // Convert special characters to HTML entities
            return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}



/**
 * Log security events
 * @param string $event_type Type of security event
 * @param string $description Event description
 * @param array $context Additional context data
 */
function log_security_event($event_type, $description, $context = []) {
    $log_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => get_client_ip(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'event_type' => $event_type,
        'description' => $description,
        'context' => $context,
        'url' => $_SERVER['REQUEST_URI'] ?? '',
        'method' => $_SERVER['REQUEST_METHOD'] ?? ''
    ];
    
    $log_file = __DIR__ . "/../logs/security.log";
    $log_dir = dirname($log_file);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_entry = json_encode($log_data) . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Check for suspicious activity patterns
 * @return array Array of detected threats
 */
function detect_suspicious_activity() {
    $threats = [];
    
    // Check for SQL injection patterns
    $sql_patterns = [
        '/union\s+select/i',
        '/drop\s+table/i',
        '/insert\s+into/i',
        '/delete\s+from/i',
        '/update\s+set/i',
        '/--/',
        '/\/\*.*\*\//',
        '/xp_cmdshell/i',
        '/exec\s*\(/i'
    ];
    
    $input_data = array_merge($_GET, $_POST, $_COOKIE);
    
    foreach ($input_data as $key => $value) {
        if (is_string($value)) {
            foreach ($sql_patterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    $threats[] = [
                        'type' => 'sql_injection',
                        'pattern' => $pattern,
                        'input' => $key,
                        'value' => substr($value, 0, 100) // Truncate for logging
                    ];
                }
            }
        }
    }
    
    // Check for XSS patterns
    $xss_patterns = [
        '/<script/i',
        '/javascript:/i',
        '/on\w+\s*=/i',
        '/<iframe/i',
        '/<object/i',
        '/<embed/i'
    ];
    
    foreach ($input_data as $key => $value) {
        if (is_string($value)) {
            foreach ($xss_patterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    $threats[] = [
                        'type' => 'xss',
                        'pattern' => $pattern,
                        'input' => $key,
                        'value' => substr($value, 0, 100)
                    ];
                }
            }
        }
    }
    
    return $threats;
}

/**
 * Detect likely bot submissions using a hidden honeypot field and timing checks.
 * @param array $post Incoming POST data
 * @param string $honeypot_field Hidden field name expected to stay empty
 * @param int $min_submit_seconds Minimum realistic submit time in seconds
 * @param int $max_submit_age Maximum accepted form age in seconds
 * @return array ['is_bot' => bool, 'reason' => string]
 */
function detect_bot_form_submission($post, $honeypot_field = 'website', $min_submit_seconds = 3, $max_submit_age = 86400) {
    if (!is_array($post)) {
        return ['is_bot' => true, 'reason' => 'invalid_post_payload'];
    }

    $honeypot_value = trim((string)($post[$honeypot_field] ?? ''));
    if ($honeypot_value !== '') {
        return ['is_bot' => true, 'reason' => 'honeypot_filled'];
    }

    $started_at = isset($post['form_started_at']) ? (int)$post['form_started_at'] : 0;
    if ($started_at <= 0) {
        return ['is_bot' => true, 'reason' => 'missing_form_timestamp'];
    }

    $age = time() - $started_at;
    if ($age < $min_submit_seconds) {
        return ['is_bot' => true, 'reason' => 'submitted_too_fast'];
    }
    if ($age > $max_submit_age) {
        return ['is_bot' => true, 'reason' => 'stale_form_submission'];
    }

    return ['is_bot' => false, 'reason' => ''];
}

/**
 * Verify Cloudflare Turnstile token.
 * @param string $token Turnstile response token from form
 * @param string $remoteIp Optional remote IP
 * @return array ['success' => bool, 'error' => string]
 */
function verify_turnstile_token($token, $remoteIp = '') {
    $secret = getenv('JLM_TURNSTILE_SECRET_KEY') ?: '';
    $token = trim((string)$token);
    if ($secret === '' || $token === '') {
        return ['success' => false, 'error' => 'missing_secret_or_token'];
    }

    $payload = [
        'secret' => $secret,
        'response' => $token
    ];
    if ($remoteIp !== '') {
        $payload['remoteip'] = $remoteIp;
    }

    $endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $rawResponse = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $rawResponse = (string)curl_exec($ch);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($payload),
                'timeout' => 10
            ]
        ]);
        $rawResponse = (string)@file_get_contents($endpoint, false, $context);
    }

    if ($rawResponse === '') {
        return ['success' => false, 'error' => 'verification_request_failed'];
    }

    $decoded = json_decode($rawResponse, true);
    if (!is_array($decoded)) {
        return ['success' => false, 'error' => 'invalid_verification_response'];
    }

    if (!empty($decoded['success'])) {
        return ['success' => true, 'error' => ''];
    }

    $codes = isset($decoded['error-codes']) && is_array($decoded['error-codes']) ? implode(',', $decoded['error-codes']) : 'turnstile_failed';
    return ['success' => false, 'error' => $codes];
}

/**
 * Block IP address temporarily
 * @param string $ip IP address to block
 * @param int $duration Block duration in seconds
 * @param string $reason Reason for blocking
 */
function block_ip($ip, $duration = 3600, $reason = 'Suspicious activity') {
    $block_file = __DIR__ . "/../cache/blocked_ips.json";
    $cache_dir = dirname($block_file);
    
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    $blocked_ips = [];
    if (file_exists($block_file)) {
        $blocked_ips = json_decode(file_get_contents($block_file), true) ?: [];
    }
    
    $blocked_ips[$ip] = [
        'blocked_until' => time() + $duration,
        'reason' => $reason,
        'blocked_at' => time()
    ];
    
    file_put_contents($block_file, json_encode($blocked_ips));
    
    log_security_event('ip_blocked', "IP {$ip} blocked for {$reason}", [
        'duration' => $duration,
        'blocked_until' => time() + $duration
    ]);
}

/**
 * Check if IP is blocked
 * @param string $ip IP address to check
 * @return bool True if blocked
 */
function is_ip_blocked($ip) {
    $block_file = __DIR__ . "/../cache/blocked_ips.json";
    
    if (!file_exists($block_file)) {
        return false;
    }
    
    $blocked_ips = json_decode(file_get_contents($block_file), true) ?: [];
    
    if (isset($blocked_ips[$ip])) {
        $block_data = $blocked_ips[$ip];
        
        // Check if block has expired
        if (time() > $block_data['blocked_until']) {
            unset($blocked_ips[$ip]);
            file_put_contents($block_file, json_encode($blocked_ips));
            return false;
        }
        
        return true;
    }
    
    return false;
}

/**
 * Clean expired IP blocks
 */
function clean_expired_blocks() {
    $block_file = __DIR__ . "/../cache/blocked_ips.json";
    

/**
 * Block IP address temporarily
 * @param string $ip IP address to block
 * @param int $duration Block duration in seconds
 * @param string $reason Reason for blocking
 */
function block_ip($ip, $duration = 3600, $reason = 'Suspicious activity') {
    $block_file = __DIR__ . "/../cache/blocked_ips.json";
    $cache_dir = dirname($block_file);
    
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    $blocked_ips = [];
    if (file_exists($block_file)) {
        $blocked_ips = json_decode(file_get_contents($block_file), true) ?: [];
    }
    
    $blocked_ips[$ip] = [
        'blocked_until' => time() + $duration,
        'reason' => $reason,
        'blocked_at' => time()
    ];
    
    file_put_contents($block_file, json_encode($blocked_ips));
    
    log_security_event('ip_blocked', "IP {$ip} blocked for {$reason}", [
        'duration' => $duration,
        'blocked_until' => time() + $duration
    ]);
}

/**
 * Check if IP is blocked
 * @param string $ip IP address to check
 * @return bool True if blocked
 */
function is_ip_blocked($ip) {
    $block_file = __DIR__ . "/../cache/blocked_ips.json";
    
    if (!file_exists($block_file)) {
        return false;
    }
    
    $blocked_ips = json_decode(file_get_contents($block_file), true) ?: [];
    
    if (isset($blocked_ips[$ip])) {
        $block_data = $blocked_ips[$ip];
        
        // Check if block has expired
        if (time() > $block_data['blocked_until']) {
            unset($blocked_ips[$ip]);
            file_put_contents($block_file, json_encode($blocked_ips));
            return false;
        }
        
        return true;
    }
    
    return false;
}
    if (!file_exists($block_file)) {
        return;
    }
    
    $blocked_ips = json_decode(file_get_contents($block_file), true) ?: [];
    $current_time = time();
    $cleaned = false;
    
    foreach ($blocked_ips as $ip => $block_data) {
        if ($current_time > $block_data['blocked_until']) {
            unset($blocked_ips[$ip]);
            $cleaned = true;
        }
    }
    
    if ($cleaned) {
        file_put_contents($block_file, json_encode($blocked_ips));
    }
}

if (rand(1, 100) === 1) {
    clean_rate_limit_cache();
    clean_expired_blocks();
}
