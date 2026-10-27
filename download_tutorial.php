<?php
require_once __DIR__ . '/config/config.php';

$token = $_GET['token'] ?? '';
$type = $_GET['type'] ?? '';

if (empty($token) || !in_array($type, ['manual', 'landscape', 'portrait'])) {
    http_response_code(404);
    die("Tutorial file not found or invalid token.");
}

$stmt = $pdo->prepare("SELECT * FROM client_tutorials WHERE access_token = ?");
$stmt->execute([$token]);
$tutorial = $stmt->fetch();

if (!$tutorial) {
    http_response_code(404);
    die("Tutorial not found or invalid token.");
}

$isExpired = !empty($tutorial['expires_at']) && strtotime($tutorial['expires_at']) < time();
if ($isExpired) {
    http_response_code(403);
    die("This file link has expired.");
}

$relative_path = '';
if ($type === 'manual') {
    $relative_path = $tutorial['user_manual_path'];
} elseif ($type === 'landscape') {
    $relative_path = $tutorial['landscape_video_path'];
} elseif ($type === 'portrait') {
    $relative_path = $tutorial['portrait_video_path'];
}

if (empty($relative_path)) {
    http_response_code(404);
    die("Requested file does not exist.");
}

$file_path = __DIR__ . '/' . ltrim($relative_path, '/');

if (!file_exists($file_path)) {
    http_response_code(404);
    die("File not found on server.");
}

// Security: Make sure the file is within the uploads/tutorials directory
$realFilePath = realpath($file_path);
$realUploadsDir = realpath(__DIR__ . '/uploads/tutorials');

if ($realFilePath === false || strpos($realFilePath, $realUploadsDir) !== 0) {
    http_response_code(403);
    die("Access denied.");
}

// Determine MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $realFilePath);
finfo_close($finfo);

if (!$mime_type) {
    $mime_type = 'application/octet-stream';
}

// Serve the file
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . basename($realFilePath) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($realFilePath));

// Clear output buffer and flush
if (ob_get_level()) ob_end_clean();
flush();

// Read the file and output it
readfile($realFilePath);
exit;
