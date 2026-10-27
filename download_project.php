<?php
require_once __DIR__ . '/config/config.php';

$token = $_GET['token'] ?? '';
if (empty($token)) {
    http_response_code(404);
    die("Project not found or invalid token.");
}

$stmt = $pdo->prepare("SELECT * FROM client_projects WHERE access_token = ?");
$stmt->execute([$token]);
$project = $stmt->fetch();

if (!$project) {
    http_response_code(404);
    die("Project not found or invalid token.");
}

$isExpired = !empty($project['expires_at']) && strtotime($project['expires_at']) < time();
if ($isExpired) {
    http_response_code(403);
    die("This file link has expired.");
}

$file_path = __DIR__ . '/' . $project['file_path'];

if (empty($project['file_path']) || !file_exists($file_path)) {
    http_response_code(404);
    die("File not found.");
}

// Security: Make sure the file is within the uploads/projects directory
$realFilePath = realpath($file_path);
$realUploadsDir = realpath(__DIR__ . '/uploads/projects');

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
header('Content-Disposition: attachment; filename="' . basename($project['file_path']) . '"');
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
