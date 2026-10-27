<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_admin_auth();
require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json');

$uploadDir = __DIR__ . '/uploads/tutorials/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    file_put_contents($uploadDir . '.htaccess', "Deny from all\n");
}

// 1. Finalize Multi-File Upload
if (isset($_POST['finalize_upload'])) {
    $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['upload_id'] ?? '');
    $fileName = $_POST['file_name'] ?? 'file';
    $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
    
    if (empty($uploadId)) {
        echo json_encode(['error' => 'Missing upload ID']);
        exit;
    }
    
    $sourceFile = $uploadDir . 'chunks_' . $uploadId . '/' . $fileName;
    if (!file_exists($sourceFile)) {
        echo json_encode(['error' => 'Reassembled file not found at ' . $sourceFile]);
        exit;
    }
    
    $finalName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
    $finalPath = $uploadDir . $finalName;
    
    if (rename($sourceFile, $finalPath)) {
        // Clean up temp dir
        @rmdir($uploadDir . 'chunks_' . $uploadId . '/');
        
        echo json_encode([
            'success' => true,
            'file_path' => 'uploads/tutorials/' . $finalName,
            'file_name' => $fileName
        ]);
    } else {
        echo json_encode(['error' => 'Failed to move finalized file']);
    }
    exit;
}

// 2. Handle Chunked or Individual File Upload
if (isset($_POST['upload_id'])) {
    $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['upload_id']);
    $chunkIndex = (int)($_POST['chunk_index'] ?? -1);
    $totalChunks = (int)($_POST['total_chunks'] ?? 1);
    $relativeName = $_POST['relative_name'] ?? ''; // File name
    
    if (empty($relativeName)) {
        $relativeName = $_FILES['file']['name'] ?? 'file';
    }

    // Clean filename
    $relativeName = preg_replace('/[^a-zA-Z0-9._-]/', '', $relativeName);

    $targetDir = $uploadDir . 'chunks_' . $uploadId . '/';
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    
    $destFile = $targetDir . $relativeName;
    $destPath = dirname($destFile);
    if (!is_dir($destPath)) mkdir($destPath, 0755, true);

    // Chunked upload logic
    if ($chunkIndex >= 0) {
        $chunkFile = $destFile . '.part' . str_pad($chunkIndex, 6, '0', STR_PAD_LEFT);
        if (move_uploaded_file($_FILES['file']['tmp_name'], $chunkFile)) {
            // If it's the last chunk, combine them
            if ($chunkIndex === $totalChunks - 1) {
                $out = fopen($destFile, 'wb');
                for ($i = 0; $i < $totalChunks; $i++) {
                    $part = $destFile . '.part' . str_pad($i, 6, '0', STR_PAD_LEFT);
                    if (file_exists($part)) {
                        $in = fopen($part, 'rb');
                        while ($buff = fread($in, 8192)) fwrite($out, $buff);
                        fclose($in);
                        unlink($part);
                    }
                }
                fclose($out);
                echo json_encode(['success' => true, 'done' => true]);
            } else {
                echo json_encode(['success' => true, 'done' => false]);
            }
            exit;
        }
    } else {
        // Individual full file upload
        if (move_uploaded_file($_FILES['file']['tmp_name'], $destFile)) {
            echo json_encode(['success' => true, 'done' => true]);
            exit;
        }
    }
}

echo json_encode(['error' => 'No valid upload data received']);
