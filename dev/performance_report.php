<?php
/**
 * Comprehensive Performance Report
 * Provides detailed performance insights for the JLM website
 */

// Prevent direct access in production
if (!defined('DEVELOPMENT_MODE') || !DEVELOPMENT_MODE) {
    die('Access denied');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/performance.php';

// Set content type
header('Content-Type: text/html; charset=utf-8');

// Get performance data
$start_time = microtime(true);

// Database performance test
$db_performance = [];
$db_start = microtime(true);
try {
    $pdo = get_optimized_pdo();
    if ($pdo) {
        // Test basic query
        $stmt = $pdo->query('SELECT COUNT(*) FROM testimonials');
        $testimonial_count = $stmt->fetchColumn();
        
        // Test complex query
        $stmt = $pdo->prepare('SELECT * FROM testimonials WHERE status = ? ORDER BY created_at DESC LIMIT ?');
        $stmt->execute(['approved', 10]);
        $testimonials = $stmt->fetchAll();
        
        $db_performance = [
            'connection_time' => round(microtime(true) - $db_start, 4),
            'testimonial_count' => $testimonial_count,
            'complex_query_time' => round(microtime(true) - $db_start, 4),
            'status' => 'connected'
        ];
    }
} catch (Exception $e) {
    $db_performance = ['status' => 'error', 'error' => $e->getMessage()];
}

// Cache performance test
$cache_performance = [];
$cache_start = microtime(true);
$test_key = 'performance_test_' . time();
$test_data = ['test' => 'data', 'timestamp' => time()];

cache_set($test_key, $test_data, 60);
$cached_data = cache_get($test_key);
$cache_time = microtime(true) - $cache_start;

$cache_performance = [
    'write_time' => round($cache_time, 4),
    'read_success' => $cached_data !== false,
    'status' => $cached_data !== false ? 'working' : 'failed'
];

// Memory usage
$memory_usage = [
    'current' => round(memory_get_usage(true) / 1024 / 1024, 2),
    'peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
    'limit' => ini_get('memory_limit')
];

// File system check
$cache_dir = __DIR__ . '/../cache';
$filesystem = [
    'cache_dir_exists' => is_dir($cache_dir),
    'cache_dir_writable' => is_writable($cache_dir),
    'cache_files_count' => is_dir($cache_dir) ? count(glob($cache_dir . '/*.cache')) : 0,
    'cache_dir_size' => is_dir($cache_dir) ? round(dirSize($cache_dir) / 1024 / 1024, 2) : 0
];

// PHP configuration
$php_config = [
    'version' => PHP_VERSION,
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'zlib' => extension_loaded('zlib'),
        'json' => extension_loaded('json'),
        'mbstring' => extension_loaded('mbstring')
    ],
    'settings' => [
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'zlib.output_compression' => ini_get('zlib.output_compression')
    ]
];

// Performance score calculation
$score = 100;
$issues = [];

if ($db_performance['status'] !== 'connected') {
    $score -= 30;
    $issues[] = 'Database connection failed';
}
if ($cache_performance['status'] !== 'working') {
    $score -= 20;
    $issues[] = 'Cache system not working';
}
if ($memory_usage['peak'] > 50) {
    $score -= 10;
    $issues[] = 'High memory usage: ' . $memory_usage['peak'] . 'MB';
}

$performance_score = max(0, $score);

// Helper function to calculate directory size
function dirSize($dir) {
    $size = 0;
    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        $size += is_file($each) ? filesize($each) : dirSize($each);
    }
    return $size;
}

$total_time = microtime(true) - $start_time;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JLM Performance Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .score { font-size: 3em; font-weight: bold; margin: 20px 0; }
        .score.excellent { color: #10b981; }
        .score.good { color: #f59e0b; }
        .score.poor { color: #ef4444; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #e5e7eb; border-radius: 5px; }
        .section h3 { margin-top: 0; color: #374151; }
        .metric { display: flex; justify-content: space-between; margin: 5px 0; padding: 5px 0; border-bottom: 1px solid #f3f4f6; }
        .metric:last-child { border-bottom: none; }
        .status { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; }
        .status.success { background: #d1fae5; color: #065f46; }
        .status.error { background: #fee2e2; color: #991b1b; }
        .status.warning { background: #fef3c7; color: #92400e; }
        .issues { background: #fee2e2; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .issues h4 { margin-top: 0; color: #991b1b; }
        .issues ul { margin: 0; padding-left: 20px; }
        .recommendations { background: #d1fae5; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .recommendations h4 { margin-top: 0; color: #065f46; }
        .recommendations ul { margin: 0; padding-left: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>JLM Creative Media - Performance Report</h1>
            <p>Generated on <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>

        <div class="score <?php echo $performance_score >= 80 ? 'excellent' : ($performance_score >= 60 ? 'good' : 'poor'); ?>">
            Performance Score: <?php echo $performance_score; ?>/100
        </div>

        <?php if (!empty($issues)): ?>
        <div class="issues">
            <h4>⚠️ Issues Found:</h4>
            <ul>
                <?php foreach ($issues as $issue): ?>
                <li><?php echo htmlspecialchars($issue); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="section">
            <h3>📊 Database Performance</h3>
            <div class="metric">
                <span>Status:</span>
                <span class="status <?php echo $db_performance['status'] === 'connected' ? 'success' : 'error'; ?>">
                    <?php echo ucfirst($db_performance['status']); ?>
                </span>
            </div>
            <?php if ($db_performance['status'] === 'connected'): ?>
            <div class="metric">
                <span>Connection Time:</span>
                <span><?php echo $db_performance['connection_time']; ?>s</span>
            </div>
            <div class="metric">
                <span>Testimonials Count:</span>
                <span><?php echo $db_performance['testimonial_count']; ?></span>
            </div>
            <?php else: ?>
            <div class="metric">
                <span>Error:</span>
                <span><?php echo htmlspecialchars($db_performance['error']); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>💾 Cache Performance</h3>
            <div class="metric">
                <span>Status:</span>
                <span class="status <?php echo $cache_performance['status'] === 'working' ? 'success' : 'error'; ?>">
                    <?php echo ucfirst($cache_performance['status']); ?>
                </span>
            </div>
            <div class="metric">
                <span>Write/Read Time:</span>
                <span><?php echo $cache_performance['write_time']; ?>s</span>
            </div>
            <div class="metric">
                <span>Cache Files:</span>
                <span><?php echo $filesystem['cache_files_count']; ?></span>
            </div>
            <div class="metric">
                <span>Cache Size:</span>
                <span><?php echo $filesystem['cache_dir_size']; ?>MB</span>
            </div>
        </div>

        <div class="section">
            <h3>🧠 Memory Usage</h3>
            <div class="metric">
                <span>Current Memory:</span>
                <span><?php echo $memory_usage['current']; ?>MB</span>
            </div>
            <div class="metric">
                <span>Peak Memory:</span>
                <span><?php echo $memory_usage['peak']; ?>MB</span>
            </div>
            <div class="metric">
                <span>Memory Limit:</span>
                <span><?php echo $memory_usage['limit']; ?></span>
            </div>
        </div>

        <div class="recommendations">
            <h4>🚀 Performance Recommendations:</h4>
            <ul>
                <?php if ($performance_score >= 80): ?>
                <li>Excellent performance! Keep monitoring for any degradation.</li>
                <?php else: ?>
                <?php if ($db_performance['status'] !== 'connected'): ?>
                <li>Fix database connection issues immediately.</li>
                <?php endif; ?>
                <?php if ($cache_performance['status'] !== 'working'): ?>
                <li>Ensure cache directory is writable and cache system is working.</li>
                <?php endif; ?>
                <?php if ($memory_usage['peak'] > 50): ?>
                <li>Consider optimizing memory usage or increasing memory limit.</li>
                <?php endif; ?>
                <?php endif; ?>
                <li>Run database optimization script regularly.</li>
                <li>Monitor cache file growth and clean expired files.</li>
                <li>Consider implementing CDN for static assets.</li>
            </ul>
        </div>

        <div class="section">
            <h3>⏱️ Report Generation Time</h3>
            <div class="metric">
                <span>Total Time:</span>
                <span><?php echo round($total_time, 4); ?>s</span>
            </div>
        </div>
    </div>
</body>
</html> 