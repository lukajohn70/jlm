<?php
// JLM Freelancer Web App - Submit Job Files
session_start();

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require authentication
require_auth();

// Get current user
$user = get_current_jogigs_user();

$error = '';
$success = '';
$assignment = null;

// Get assignment details
if (isset($_GET['assignment_id'])) {
    $assignment_id = (int)$_GET['assignment_id'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT ja.*, j.title, j.description, q.quote_amount, q.currency
            FROM jogigs_job_assignments ja
            JOIN jogigs_jobs j ON ja.job_id = j.id
            JOIN jogigs_quotes q ON ja.quote_id = q.id
            WHERE ja.id = ? AND ja.freelancer_id = ? AND ja.status = 'in_progress'
        ");
        $stmt->execute([$assignment_id, $user['id']]);
        $assignment = $stmt->fetch();
        
        if (!$assignment) {
            $error = 'Job assignment not found or not in progress.';
        }
    } catch (Exception $e) {
        $error = 'Error loading assignment details.';
    }
} else {
    $error = 'No assignment specified.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $assignment) {
    $notes = trim($_POST['submission_notes'] ?? '');
    
    // Check if files were uploaded
    if (!isset($_FILES['job_files']) || empty($_FILES['job_files']['name'][0])) {
        $error = 'Please select at least one file to upload.';
    } else {
        $files_data = [
            'notes' => $notes,
            'files' => $_FILES['job_files']
        ];
        
        $result = submit_job_files($assignment['id'], $user['id'], $files_data);
        
        if ($result['success']) {
            $success = 'Job files submitted successfully! Your work is now under review.';
        } else {
            $error = $result['message'];
        }
    }
}

$page_title = 'Submit Job Files';
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
        .file-upload-area {
            border: 2px dashed #d1d5db;
            transition: all 0.3s ease;
        }
        .file-upload-area:hover {
            border-color: #667eea;
            background-color: #f8fafc;
        }
        .file-upload-area.dragover {
            border-color: #667eea;
            background-color: #eff6ff;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white text-sm font-bold">JLM</span>
                        </div>
                        <span class="text-xl font-bold text-gray-900">JoGigs</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 transition-colors duration-200">
                        Dashboard
                    </a>
                    <a href="logout.php" class="text-gray-700 hover:text-red-600 transition-colors duration-200">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Submit Job Files</h1>
            <p class="text-gray-600">Upload your completed work files for review and approval.</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-600"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-green-600"><?php echo htmlspecialchars($success); ?></p>
                <div class="mt-4">
                    <a href="dashboard.php" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors duration-200">
                        Return to Dashboard
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($assignment && !$success): ?>
            <!-- Job Details -->
            <div class="bg-white rounded-lg card-shadow p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Job Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($assignment['description']); ?></p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-gray-900">
                            <?php echo format_currency($assignment['quote_amount'], $assignment['currency']); ?>
                        </div>
                        <div class="text-sm text-gray-500">
                            Deadline: <?php echo format_date($assignment['deadline']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- File Submission Form -->
            <div class="bg-white rounded-lg card-shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Submit Your Work</h2>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <!-- File Upload Area -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Upload Files *
                        </label>
                        <div id="fileUploadArea" class="file-upload-area rounded-lg p-8 text-center">
                            <div class="mb-4">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div class="mb-4">
                                <p class="text-lg font-medium text-gray-900">Drop files here or click to browse</p>
                                <p class="text-sm text-gray-500">Support for images, videos, documents, and design files (max 50MB each)</p>
                            </div>
                            <input type="file" name="job_files[]" id="jobFiles" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.mp4,.avi,.mov,.wmv,.flv,.webm,.psd,.ai,.eps,.svg,.zip,.rar" class="hidden">
                            <button type="button" onclick="document.getElementById('jobFiles').click()" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200">
                                Choose Files
                            </button>
                        </div>
                        
                        <!-- File List -->
                        <div id="fileList" class="mt-4 space-y-2"></div>
                    </div>

                    <!-- Submission Notes -->
                    <div>
                        <label for="submission_notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Submission Notes (Optional)
                        </label>
                        <textarea 
                            id="submission_notes" 
                            name="submission_notes" 
                            rows="4" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Add any notes about your submission, special instructions, or file descriptions..."
                        ></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button 
                            type="submit" 
                            class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-purple-700 transition-all duration-200"
                        >
                            Submit Files for Review
                        </button>
                    </div>
                </form>
            </div>

            <!-- Guidelines -->
            <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-blue-900 mb-4">Submission Guidelines</h3>
                <ul class="space-y-2 text-blue-800">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Upload all final files in their original format</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Maximum file size: 50MB per file</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Supported formats: Images, Videos, Documents, Design files</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Your files will be reviewed by admin before being sent to the client</span>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // File upload handling
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('jobFiles');
        const fileList = document.getElementById('fileList');

        // Drag and drop functionality
        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            updateFileList();
        });

        // File input change
        fileInput.addEventListener('change', updateFileList);

        function updateFileList() {
            fileList.innerHTML = '';
            const files = fileInput.files;
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileSize = (file.size / (1024 * 1024)).toFixed(2);
                
                const fileItem = document.createElement('div');
                fileItem.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg';
                fileItem.innerHTML = `
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm font-medium text-gray-900">${file.name}</span>
                    </div>
                    <span class="text-sm text-gray-500">${fileSize} MB</span>
                `;
                fileList.appendChild(fileItem);
            }
        }
    </script>
</body>
</html>
