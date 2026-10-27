<?php
session_start();
require_once __DIR__ . '/includes/admin_auth.php';

header('Content-Type: application/json; charset=UTF-8');

require_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded.']);
    exit;
}

$file = $_FILES['file'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Upload failed.']);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
];

if (!isset($allowedTypes[$mimeType])) {
    http_response_code(400);
    echo json_encode(['error' => 'Only JPG, PNG, GIF, and WebP images are allowed.']);
    exit;
}

if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Maximum size is 5MB.']);
    exit;
}

$uploadDir = __DIR__ . '/uploads/blog/';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to create upload directory.']);
    exit;
}

$filename = 'blog_' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $allowedTypes[$mimeType];
$targetPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save uploaded image.']);
    exit;
}

echo json_encode(['url' => 'uploads/blog/' . $filename]);
