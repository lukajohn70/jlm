<?php
// Database configuration
// Check if we're in development or production
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$isDevelopment = $httpHost === 'localhost' || 
                 $httpHost === '127.0.0.1' || 
                 strpos($httpHost, 'localhost:') === 0 || 
                 strpos($httpHost, '127.0.0.1:') === 0 ||
                 php_sapi_name() === 'cli' || // Command line interface
                 empty($httpHost); // No HTTP_HOST (CLI or local development)

// Define development mode constant
define('DEVELOPMENT_MODE', $isDevelopment);

// Include PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

if ($isDevelopment) {
    // Development database settings
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'gwylvxeo_jlm'); // Local development database
    define('DB_USER', 'root');
    define('DB_PASS', 'root');
} else {
    // Production database settings
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'gwylvxeo_jlm');
    define('DB_USER', 'gwylvxeo_gwylvxeo');
    define('DB_PASS', 'ko8DM74Y5)@zeS');
}

// For production, use environment variables or a secure method to store DB credentials.
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    if ($isDevelopment) {
        // In development, log the error but don't exit
        error_log('Database connection failed: ' . $e->getMessage());
        // Create a mock PDO object for development
        $pdo = null;
    } else {
        // In production, show error and exit
        echo 'Database connection failed: ' . $e->getMessage();
        exit;
    }
}

// Helper function to generate a password hash
function jlm_password_hash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
} 