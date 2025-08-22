<?php
// JLM Freelancer Web App - Client File Download
session_start();

require_once '../includes/config.php';
require_once '../includes/functions.php';

$error = '';
$success = '';
$submission = null;
$notification = null;

// Validate token and get submission details
if (isset($_GET['token']) && isset($_GET['id'])) {
    $token = $_GET['token'];
    $submission_id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT cn.*, js.*, j.title, j.description, u.first_name, u.last_name
            FROM jogigs_client_notifications cn
            JOIN jogigs_job_submissions js ON cn.submission_id = js.id
            JOIN jogigs_job_assignments ja ON js.job_assignment_id = ja.id
            JOIN jogigs_jobs j ON ja.job_id = j.id
            JOIN jogigs_users u ON js.freelancer_id = u.id
            WHERE cn.notification_token = ? AND cn.submission_id = ? AND cn.notification_type = 'files_ready'
        ");
        $stmt->execute([$token, $submission_id]);
        $notification = $stmt->fetch();
        
        if ($notification) {
            $submission = $notification;
            
            // Get files
            $stmt = $pdo->prepare("
                SELECT * FROM jogigs_job_submission_files 
                WHERE submission_id = ?
                ORDER BY uploaded_at ASC
            ");
            $stmt->execute([$submission_id]);
            $submission['files'] = $stmt->fetchAll();
        } else {
            $error = 'Invalid or expired download link.';
        }
    } catch (Exception $e) {
        $error = 'Error loading file details.';
    }
} else {
    $error = 'Missing download parameters.';
}

// Handle file download
if (isset($_GET['download']) && $submission) {
    $file_id = (int)$_GET['download'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM jogigs_job_submission_files 
            WHERE id = ? AND submission_id = ?
        ");
        $stmt->execute([$file_id, $submission['id']]);
        $file = $stmt->fetch();
        
        if ($file && file_exists($file['file_path'])) {
            // Set headers for file download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $file['original_filename'] . '"');
            header('Content-Length: ' . filesize($file['file_path']));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            
            // Output file
            readfile($file['file_path']);
            exit;
        } else {
            $error = 'File not found.';
        }
    } catch (Exception $e) {
        $error = 'Error downloading file.';
    }
}

$page_title = 'Download Project Files';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JoGigs</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-shadow {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="gradient-bg text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                        <span class="text-white text-sm font-bold">JLM</span>
                    </div>
                    <span class="text-xl font-bold ml-2">JoGigs</span>
                </div>
                <div class="text-white/80">
                    Project Files Ready
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($error): ?>
            <div class="bg-white rounded-lg card-shadow p-6">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">Download Error</h3>
                    <p class="mt-1 text-gray-500"><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
        <?php elseif ($submission): ?>
            <!-- Project Details -->
            <div class="bg-white rounded-lg card-shadow p-6 mb-6">
                <div class="text-center mb-6">
                    <svg class="mx-auto h-12 w-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h1 class="text-2xl font-bold text-gray-900 mt-4">Your Project Files Are Ready!</h1>
                    <p class="text-gray-600 mt-2">Download your completed project files below.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Project Details</h2>
                        <div class="space-y-2 text-sm text-gray-600">
                            <p><strong>Project:</strong> <?php echo htmlspecialchars($submission['title']); ?></p>
                            <p><strong>Freelancer:</strong> <?php echo htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']); ?></p>
                            <p><strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?></p>
                            <p><strong>Files:</strong> <?php echo count($submission['files']); ?> file(s)</p>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Project Description</h2>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($submission['description']); ?></p>
                    </div>
                </div>

                <?php if ($submission['submission_notes']): ?>
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h3 class="text-sm font-medium text-blue-900 mb-2">Freelancer Notes</h3>
                        <p class="text-sm text-blue-800"><?php echo htmlspecialchars($submission['submission_notes']); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- File Downloads -->
            <div class="bg-white rounded-lg card-shadow p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Download Files</h2>
                
                <?php if (empty($submission['files'])): ?>
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No files available</h3>
                        <p class="mt-1 text-sm text-gray-500">No files have been uploaded for this project.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($submission['files'] as $file): ?>
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex items-center">
                                    <svg class="w-8 h-8 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($file['original_filename']); ?></p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo number_format($file['file_size'] / 1024 / 1024, 2); ?> MB • 
                                            <?php echo strtoupper($file['file_type']); ?>
                                        </p>
                                    </div>
                                </div>
                                <a 
                                    href="?token=<?php echo urlencode($token); ?>&id=<?php echo $submission_id; ?>&download=<?php echo $file['id']; ?>" 
                                    class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors duration-200"
                                >
                                    Download
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Next Steps -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-green-900 mb-4">Next Steps</h3>
                <div class="space-y-3 text-green-800">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Download and review all project files</span>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Check that the work meets your requirements</span>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>You will receive a separate email to confirm completion and release payment</span>
                    </div>
                </div>
            </div>

            <!-- Contact Support -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500">
                    Need help? Contact us at 
                    <a href="mailto:support@jogigs.com" class="text-blue-600 hover:text-blue-500">support@jogigs.com</a>
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
