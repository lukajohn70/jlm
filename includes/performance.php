<?php
/**
 * Performance Optimization Module
 * Handles caching, asset optimization, and performance monitoring
 */

// Prevent direct access
if (!defined('PERFORMANCE_LOADED')) {
    define('PERFORMANCE_LOADED', true);
}

/**
 * Initialize performance optimizations
 */
function init_performance_optimizations() {
    // Enable output buffering for better performance
    if (!ob_get_level()) {
        ob_start();
    }
    
    // Set performance headers
    set_performance_headers();
    
    // Initialize caching
    init_caching_system();
}

/**
 * Set performance-related HTTP headers
 */
function set_performance_headers() {
    // Check if headers have already been sent
    if (headers_sent()) {
        return;
    }
    
    // Enable Keep-Alive
    header('Connection: keep-alive');
    
    // Set cache control for static assets
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/', $request_uri)) {
        $cache_time = 31536000; // 1 year for static assets
        header("Cache-Control: public, max-age=$cache_time, immutable");
        header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_time));
    }
    
    // Enable compression for text-based content
    if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
        ini_set('zlib.output_compression', 1);
    }
}

/**
 * Initialize caching system
 */
function init_caching_system() {
    // Create cache directory if it doesn't exist
    $cache_dir = __DIR__ . '/../cache';
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    // Clean expired cache files periodically
    if (rand(1, 100) <= 5) { // 5% chance to clean cache
        clean_expired_cache();
    }
}

/**
 * Cache data with expiration
 */
function cache_set($key, $data, $expiration = 3600) {
    $cache_file = __DIR__ . '/../cache/' . md5($key) . '.cache';
    $cache_data = [
        'data' => $data,
        'expires' => time() + $expiration
    ];
    
    return file_put_contents($cache_file, serialize($cache_data), LOCK_EX);
}

/**
 * Get cached data
 */
function cache_get($key) {
    $cache_file = __DIR__ . '/../cache/' . md5($key) . '.cache';
    
    if (!file_exists($cache_file)) {
        return false;
    }
    
    $cache_data = unserialize(file_get_contents($cache_file));
    
    if (!$cache_data || $cache_data['expires'] < time()) {
        unlink($cache_file);
        return false;
    }
    
    return $cache_data['data'];
}

/**
 * Clean expired cache files
 */
function clean_expired_cache() {
    $cache_dir = __DIR__ . '/../cache';
    $files = glob($cache_dir . '/*.cache');
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            $cache_data = unserialize(file_get_contents($file));
            if (!$cache_data || $cache_data['expires'] < time()) {
                unlink($file);
            }
        }
    }
}

/**
 * Optimize CSS by removing comments and whitespace
 */
function optimize_css($css) {
    // Remove comments
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    
    // Remove unnecessary whitespace
    $css = preg_replace('/\s+/', ' ', $css);
    $css = str_replace(['; ', ' {', '{ ', ' }', '} ', ': '], [';', '{', '{', '}', '}', ':'], $css);
    
    return trim($css);
}

/**
 * Optimize JavaScript by removing comments and whitespace
 */
function optimize_js($js) {
    // Remove single-line comments (but preserve URLs)
    $js = preg_replace('/(?<!:)\/\/.*$/m', '', $js);
    
    // Remove multi-line comments
    $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
    
    // Remove unnecessary whitespace
    $js = preg_replace('/\s+/', ' ', $js);
    
    return trim($js);
}

/**
 * Generate optimized asset URLs with versioning
 */
function get_optimized_asset_url($file_path, $type = 'css') {
    $file_path = ltrim($file_path, '/');
    $full_path = __DIR__ . '/../' . $file_path;
    
    if (!file_exists($full_path)) {
        return $file_path;
    }
    
    // Add version based on file modification time
    $version = filemtime($full_path);
    $extension = pathinfo($file_path, PATHINFO_EXTENSION);
    
    // For CSS and JS files, check if we should serve optimized versions
    if (in_array($extension, ['css', 'js']) && !is_development_mode()) {
        $optimized_path = str_replace(".$extension", ".min.$extension", $file_path);
        $optimized_full_path = __DIR__ . '/../' . $optimized_path;
        
        if (file_exists($optimized_full_path)) {
            return $optimized_path . "?v=$version";
        }
    }
    
    return $file_path . "?v=$version";
}

/**
 * Check if we're in development mode
 */
function is_development_mode() {
    return defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE;
}

/**
 * Monitor page load performance
 */
function start_performance_monitor() {
    if (!isset($_SESSION['performance_start'])) {
        $_SESSION['performance_start'] = microtime(true);
    }
}

/**
 * End performance monitoring and log if needed
 */
function end_performance_monitor() {
    if (isset($_SESSION['performance_start'])) {
        $load_time = microtime(true) - $_SESSION['performance_start'];
        
        // Log slow pages (over 2 seconds)
        if ($load_time > 2.0) {
            $page = $_SERVER['REQUEST_URI'] ?? 'unknown';
            $memory_usage = memory_get_peak_usage(true);
            error_log("SLOW_PAGE: $page took " . round($load_time, 3) . "s, memory: " . round($memory_usage / 1024 / 1024, 2) . "MB");
        }
        
        unset($_SESSION['performance_start']);
    }
}

/**
 * Optimize database queries with connection pooling
 */
function get_optimized_pdo() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    PDO::ATTR_PERSISTENT => true // Enable connection pooling
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return false;
        }
    }
    
    return $pdo;
}

/**
 * Optimize images by serving WebP when supported
 */
function get_optimized_image_url($image_path, $webp_path = null) {
    $accept_header = $_SERVER['HTTP_ACCEPT'] ?? '';
    
    // Check if browser supports WebP
    if (strpos($accept_header, 'image/webp') !== false && $webp_path && file_exists($webp_path)) {
        return $webp_path;
    }
    
    return $image_path;
}

/**
 * Lazy load images with JavaScript
 */
function get_lazy_load_script() {
    return "
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    });
    </script>";
}

/**
 * Preload critical resources
 */
function get_critical_resources() {
    return "
    <link rel='preload' href='/assets/css/tailwind.css' as='style' onload='this.onload=null;this.rel=\"stylesheet\"'>
    <link rel='preload' href='/assets/js/main.js' as='script'>
    <link rel='dns-prefetch' href='//fonts.googleapis.com'>
    <link rel='preconnect' href='//fonts.googleapis.com' crossorigin>
    ";
}

// Initialize performance optimizations only if not already initialized
if (!defined('PERFORMANCE_INITIALIZED')) {
    define('PERFORMANCE_INITIALIZED', true);
    init_performance_optimizations();
    start_performance_monitor();
} 