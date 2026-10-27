<?php
/**
 * stream_tutorial.php
 * Securely streams tutorial video files with HTTP Range support (seeking).
 * Bypasses the uploads/tutorials/.htaccess "Deny from all" rule.
 */
session_start();
require_once __DIR__ . '/config/config.php';

$token = $_GET['token'] ?? '';
$type  = $_GET['type']  ?? '';

if (empty($token) || !in_array($type, ['landscape', 'portrait'])) {
    http_response_code(400);
    exit('Invalid request.');
}

// Fetch tutorial record
$stmt = $pdo->prepare("SELECT * FROM client_tutorials WHERE access_token = ?");
$stmt->execute([$token]);
$tutorial = $stmt->fetch();

if (!$tutorial) {
    http_response_code(404);
    exit('Tutorial not found.');
}

// Check expiry
if (!empty($tutorial['expires_at']) && strtotime($tutorial['expires_at']) < time()) {
    http_response_code(403);
    exit('This tutorial has expired.');
}

// Check passcode authentication (session gate)
if (!empty($tutorial['passcode'])) {
    $session_key = 'auth_tutorial_' . $tutorial['id'];
    if (empty($_SESSION[$session_key])) {
        http_response_code(403);
        exit('Authentication required.');
    }
}

// Resolve file path
$relative_path = ($type === 'landscape')
    ? $tutorial['landscape_video_path']
    : $tutorial['portrait_video_path'];

if (empty($relative_path)) {
    http_response_code(404);
    exit('Video not found.');
}

$file_path = realpath(__DIR__ . '/' . ltrim($relative_path, '/'));
$allowed_dir = realpath(__DIR__ . '/uploads/tutorials');

// Security: prevent path traversal
if ($file_path === false || strpos($file_path, $allowed_dir) !== 0) {
    http_response_code(403);
    exit('Access denied.');
}

if (!file_exists($file_path)) {
    http_response_code(404);
    exit('File not found on server.');
}

$file_size = filesize($file_path);
$mime_type = 'video/mp4';

// --- HTTP Range support (for video seeking) ---
$start = 0;
$end   = $file_size - 1;

if (isset($_SERVER['HTTP_RANGE'])) {
    preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches);
    $start = (int)($matches[1] ?? 0);
    $end   = isset($matches[2]) && $matches[2] !== '' ? (int)$matches[2] : $file_size - 1;

    if ($start > $end || $start >= $file_size || $end >= $file_size) {
        header('HTTP/1.1 416 Range Not Satisfiable');
        header("Content-Range: bytes */$file_size");
        exit;
    }

    http_response_code(206);
    header("Content-Range: bytes $start-$end/$file_size");
} else {
    http_response_code(200);
}

$length = $end - $start + 1;

header('Content-Type: ' . $mime_type);
header('Content-Length: ' . $length);
header('Accept-Ranges: bytes');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Content-Disposition: inline; filename="' . basename($file_path) . '"');

if (ob_get_level()) ob_end_clean();

// Stream the requested byte range
$fp = fopen($file_path, 'rb');
fseek($fp, $start);
$remaining = $length;
while ($remaining > 0 && !feof($fp)) {
    $chunk = min(8192, $remaining);
    echo fread($fp, $chunk);
    $remaining -= $chunk;
    flush();
}
fclose($fp);
exit;
