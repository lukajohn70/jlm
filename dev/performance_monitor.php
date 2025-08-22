<?php
/**
 * Performance Monitoring Script
 * Use this to monitor website performance metrics
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/performance.php';

// Prevent direct access in production
if (!defined('DEVELOPMENT_MODE') || !DEVELOPMENT_MODE) {
    die('Access denied');
}

// Set content type
header('Content-Type: application/json');

// Get performance metrics
$metrics = [];

// Database performance
$start_time = microtime(true);
try {
    $pdo = get_optimized_pdo();
    if ($pdo) {
        $stmt = $pdo->query('SELECT COUNT(*) FROM testimonials');
        $testimonial_count = $stmt->fetchColumn();
        $db_time = microtime(true) - $start_time;
        
        $metrics['database'] = [
            'connection_time' => round($db_time, 4),
            'testimonial_count' => $testimonial_count,
            'status' => 'connected'
        ];
    } else {
        $metrics['database'] = [
            'status' => 'failed',
            'error' => 'Database connection failed'
        ];
    }
} catch (Exception $e) {
    $metrics['database'] = [
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

// Cache performance
$cache_start = microtime(true);
$test_cache_key = 'performance_test_' . time();
$test_data = ['test' => 'data', 'timestamp' => time()];
cache_set($test_cache_key, $test_data, 60);
$cached_data = cache_get($test_cache_key);
$cache_time = microtime(true) - $cache_start;

$metrics['cache'] = [
    'write_time' => round($cache_time, 4),
    'read_success' => $cached_data !== false,
    'status' => $cached_data !== false ? 'working' : 'failed'
];

// Memory usage
$metrics['memory'] = [
    'current' => round(memory_get_usage(true) / 1024 / 1024, 2),
    'peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
    'limit' => ini_get('memory_limit')
];

// PHP configuration
$metrics['php'] = [
    'version' => PHP_VERSION,
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'zlib' => extension_loaded('zlib'),
        'json' => extension_loaded('json')
    ],
    'settings' => [
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size')
    ]
];

// Server information
$metrics['server'] = [
    'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'php_sapi' => php_sapi_name(),
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
    'request_time' => $_SERVER['REQUEST_TIME'] ?? time()
];

// File system
$cache_dir = __DIR__ . '/../cache';
$metrics['filesystem'] = [
    'cache_dir_exists' => is_dir($cache_dir),
    'cache_dir_writable' => is_writable($cache_dir),
    'cache_files_count' => is_dir($cache_dir) ? count(glob($cache_dir . '/*.cache')) : 0
];

// Overall performance score
$score = 100;

// Deduct points for issues
if ($metrics['database']['status'] !== 'connected') {
    $score -= 30;
}
if ($metrics['cache']['status'] !== 'working') {
    $score -= 20;
}
if ($metrics['memory']['peak'] > 50) { // More than 50MB
    $score -= 10;
}
if ($db_time > 0.1) { // Database query takes more than 100ms
    $score -= 15;
}

$metrics['performance_score'] = max(0, $score);

// Output JSON
echo json_encode($metrics, JSON_PRETTY_PRINT); 