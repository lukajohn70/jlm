<?php
// JLM Freelancer Web App - Admin Review Submissions
session_start();

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require authentication
require_auth();

// Get current user
$user = get_current_jogigs_user();

// Check if user is admin
if ($user['email'] !== 'admin@jogigs.com') {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['submission_id'])) {
        $submission_id = (int)$_POST['submission_id'];
        $action = $_POST['action'];
        
        try {
            switch ($action) {
                case 'approve':
                    $result = approve_job_submission($submission_id, $user['id']);
                    if ($result['success']) {
                        $success = 'Job submission approved! Client has been notified to download files.';
                    } else {
                        $error = $result['message'];
                    }
                    break;
                    
                case 'reject':
                    $rejection_reason = trim($_POST['rejection_reason'] ?? '');
                    if (empty($rejection_reason)) {
                        $error = 'Please provide a reason for rejection.';
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE jogigs_job_submissions 
                            SET status = 'rejected', rejection_reason = ?, reviewed_at = NOW(), reviewed_by = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$rejection_reason, $user['id'], $submission_id]);
                        $success = 'Job submission rejected. Freelancer will be notified.';
                    }
                    break;
                    
                case 'request_revision':
                    $revision_notes = trim($_POST['revision_notes'] ?? '');
                    if (empty($revision_notes)) {
                        $error = 'Please provide revision notes.';
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE jogigs_job_submissions 
                            SET status = 'revision_requested', rejection_reason = ?, reviewed_at = NOW(), reviewed_by = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$revision_notes, $user['id'], $submission_id]);
                        $success = 'Revision requested. Freelancer will be notified.';
                    }
                    break;
            }
        } catch (Exception $e) {
            $error = 'An error occurred while processing your request.';
        }
    }
}

// Get all job submissions
try {
    $stmt = $pdo->prepare("
        SELECT js.*, j.title, u.first_name, u.last_name, u.email,
               (SELECT COUNT(*) FROM jogigs_job_submission_files WHERE submission_id = js.id) as file_count
        FROM jogigs_job_submissions js
        JOIN jogigs_job_assignments ja ON js.job_assignment_id = ja.id
        JOIN jogigs_jobs j ON ja.job_id = j.id
        JOIN jogigs_users u ON js.freelancer_id = u.id
        ORDER BY js.submitted_at DESC
    ");
    $stmt->execute();
    $submissions = $stmt->fetchAll();
} catch (Exception $e) {
    $submissions = [];
    $error = 'Error loading submissions.';
}

$page_title = 'Review Job Submissions';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JoGigs Admin</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #7c3aed 100%);
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
                    <a href="../admin_dashboard.php" class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                            <span class="text-white text-sm font-bold">JLM</span>
                        </div>
                        <span class="text-xl font-bold">JoGigs Admin</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../admin_dashboard.php" class="text-white/80 hover:text-white transition-colors duration-200">
                        Dashboard
                    </a>
                    <a href="../logout.php" class="text-white/80 hover:text-red-200 transition-colors duration-200">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Review Job Submissions</h1>
            <p class="text-gray-600">Review and approve freelancer work submissions before sending to clients.</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-600"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-green-600"><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <!-- Submissions List -->
        <div class="bg-white rounded-lg card-shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Job Submissions (<?php echo count($submissions); ?>)</h2>
            </div>
            
            <?php if (empty($submissions)): ?>
                <div class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No submissions</h3>
                    <p class="mt-1 text-sm text-gray-500">No job submissions to review at this time.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($submissions as $submission): ?>
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">
                                        <?php echo htmlspecialchars($submission['title']); ?>
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        Submitted by <?php echo htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']); ?> 
                                        on <?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?>
                                    </p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-3 py-1 text-xs font-medium rounded-full 
                                        <?php 
                                        switch ($submission['status']) {
                                            case 'submitted':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'under_review':
                                                echo 'bg-blue-100 text-blue-800';
                                                break;
                                            case 'approved':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'rejected':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            case 'revision_requested':
                                                echo 'bg-orange-100 text-orange-800';
                                                break;
                                            default:
                                                echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $submission['status'])); ?>
                                    </span>
                                    <span class="px-3 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                                        <?php echo $submission['file_count']; ?> files
                                    </span>
                                </div>
                            </div>

                            <?php if ($submission['submission_notes']): ?>
                                <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                                    <p class="text-sm text-gray-700">
                                        <strong>Notes:</strong> <?php echo htmlspecialchars($submission['submission_notes']); ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <!-- Action Buttons -->
                            <?php if ($submission['status'] === 'submitted' || $submission['status'] === 'under_review'): ?>
                                <div class="flex items-center space-x-3">
                                    <button onclick="viewSubmission(<?php echo $submission['id']; ?>)" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors duration-200">
                                        View Files
                                    </button>
                                    <button onclick="approveSubmission(<?php echo $submission['id']; ?>)" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors duration-200">
                                        Approve
                                    </button>
                                    <button onclick="requestRevision(<?php echo $submission['id']; ?>)" class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-700 transition-colors duration-200">
                                        Request Revision
                                    </button>
                                    <button onclick="rejectSubmission(<?php echo $submission['id']; ?>)" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition-colors duration-200">
                                        Reject
                                    </button>
                                </div>
                            <?php elseif ($submission['status'] === 'approved'): ?>
                                <div class="text-sm text-green-600">
                                    ✓ Approved on <?php echo date('M j, Y g:i A', strtotime($submission['approved_at'])); ?>
                                </div>
                            <?php elseif ($submission['status'] === 'rejected'): ?>
                                <div class="text-sm text-red-600">
                                    ✗ Rejected on <?php echo date('M j, Y g:i A', strtotime($submission['reviewed_at'])); ?>
                                </div>
                            <?php elseif ($submission['status'] === 'revision_requested'): ?>
                                <div class="text-sm text-orange-600">
                                    ↻ Revision requested on <?php echo date('M j, Y g:i A', strtotime($submission['reviewed_at'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- View Submission Modal -->
    <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-4xl w-full max-h-screen overflow-y-auto">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Submission Details</h3>
                        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div id="modalContent" class="p-6">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Reject Submission</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="submission_id" id="rejectSubmissionId">
                        <div class="mb-4">
                            <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-2">
                                Reason for Rejection *
                            </label>
                            <textarea 
                                id="rejection_reason" 
                                name="rejection_reason" 
                                rows="4" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                placeholder="Please provide a reason for rejecting this submission..."
                                required
                            ></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeRejectionModal()" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200">
                                Reject
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Revision Modal -->
    <div id="revisionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Revision</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="request_revision">
                        <input type="hidden" name="submission_id" id="revisionSubmissionId">
                        <div class="mb-4">
                            <label for="revision_notes" class="block text-sm font-medium text-gray-700 mb-2">
                                Revision Notes *
                            </label>
                            <textarea 
                                id="revision_notes" 
                                name="revision_notes" 
                                rows="4" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                placeholder="Please provide specific revision requirements..."
                                required
                            ></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeRevisionModal()" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors duration-200">
                                Request Revision
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewSubmission(submissionId) {
            // Load submission details via AJAX
            fetch(`get_submission_details.php?submission_id=${submissionId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('modalContent').innerHTML = html;
                    document.getElementById('viewModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading submission details');
                });
        }

        function closeModal() {
            document.getElementById('viewModal').classList.add('hidden');
        }

        function approveSubmission(submissionId) {
            if (confirm('Are you sure you want to approve this submission? This will make the files available to the client.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="submission_id" value="${submissionId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function rejectSubmission(submissionId) {
            document.getElementById('rejectSubmissionId').value = submissionId;
            document.getElementById('rejectionModal').classList.remove('hidden');
        }

        function closeRejectionModal() {
            document.getElementById('rejectionModal').classList.add('hidden');
            document.getElementById('rejection_reason').value = '';
        }

        function requestRevision(submissionId) {
            document.getElementById('revisionSubmissionId').value = submissionId;
            document.getElementById('revisionModal').classList.remove('hidden');
        }

        function closeRevisionModal() {
            document.getElementById('revisionModal').classList.add('hidden');
            document.getElementById('revision_notes').value = '';
        }

        // Close modals when clicking outside
        document.getElementById('viewModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        document.getElementById('rejectionModal').addEventListener('click', function(e) {
            if (e.target === this) closeRejectionModal();
        });

        document.getElementById('revisionModal').addEventListener('click', function(e) {
            if (e.target === this) closeRevisionModal();
        });
    </script>
</body>
</html>
