<?php
// JLM Freelancer Web App - My Proposals Page
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
$proposals = [];

// Get user's proposals
try {
    $stmt = $pdo->prepare("
        SELECT p.*, j.title as job_title, j.description as job_description, j.budget_min, j.budget_max
        FROM jogigs_proposals p
        JOIN jogigs_jobs j ON p.job_id = j.id
        WHERE p.freelancer_id = ?
        ORDER BY p.submitted_at DESC
    ");
    $stmt->execute([$user['id']]);
    $proposals = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Error loading proposals.';
}

$page_title = 'My Proposals - JoGigs';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
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
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 transition-colors duration-200">Dashboard</a>
                    <a href="jobs.php" class="text-gray-700 hover:text-blue-600 transition-colors duration-200">Jobs</a>
                    <a href="my-proposals.php" class="text-blue-600 font-medium">My Proposals</a>
                    <a href="earnings.php" class="text-gray-700 hover:text-blue-600 transition-colors duration-200">Earnings</a>
                    <a href="profile.php" class="text-gray-700 hover:text-blue-600 transition-colors duration-200">Profile</a>
                    <a href="logout.php" class="text-gray-700 hover:text-red-600 transition-colors duration-200">Logout</a>
                </nav>
                
                <!-- Mobile menu button -->
                <button id="mobile-menu-button" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t border-gray-200">
            <nav class="px-4 py-2 space-y-1">
                <a href="dashboard.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600">Dashboard</a>
                <a href="jobs.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600">Jobs</a>
                <a href="my-proposals.php" class="block px-3 py-2 text-blue-600 font-medium">My Proposals</a>
                <a href="earnings.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600">Earnings</a>
                <a href="profile.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600">Profile</a>
                <a href="logout.php" class="block px-3 py-2 text-gray-700 hover:text-red-600">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Proposals</h1>
                    <p class="text-gray-600 mt-2">Track the status of your job proposals</p>
                </div>
                <a href="jobs.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors duration-200">
                    Browse More Jobs
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php $flash = get_flash_message(); ?>
        <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-600"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <!-- Proposals List -->
        <?php if (empty($proposals)): ?>
            <div class="text-center py-12">
                <div class="mb-6">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No proposals yet</h3>
                <p class="text-gray-600 mb-6">You haven't submitted any proposals yet. Start by browsing available jobs.</p>
                <a href="jobs.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors duration-200">
                    Browse Jobs
                </a>
            </div>
        <?php else: ?>
            <!-- Proposals Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach ($proposals as $proposal): ?>
                    <div class="bg-white rounded-lg card-shadow overflow-hidden">
                        <!-- Proposal Header -->
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($proposal['proposal_title']); ?>
                                </h3>
                                <span class="px-3 py-1 rounded-full text-sm font-medium <?php 
                                    switch($proposal['status']) {
                                        case 'submitted':
                                            echo 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'under_review':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'accepted':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'rejected':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        case 'withdrawn':
                                            echo 'bg-gray-100 text-gray-800';
                                            break;
                                        default:
                                            echo 'bg-gray-100 text-gray-800';
                                    }
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $proposal['status'])); ?>
                                </span>
                            </div>
                            <p class="text-gray-600 text-sm">
                                For: <a href="job-details.php?id=<?php echo $proposal['job_id']; ?>" class="text-blue-600 hover:text-blue-500 font-medium">
                                    <?php echo htmlspecialchars($proposal['job_title']); ?>
                                </a>
                            </p>
                        </div>

                        <!-- Proposal Content -->
                        <div class="p-6">
                            <div class="mb-4">
                                <p class="text-gray-700 text-sm line-clamp-3">
                                    <?php echo htmlspecialchars(substr($proposal['proposal_text'], 0, 150)) . '...'; ?>
                                </p>
                            </div>

                            <!-- Proposal Details -->
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <span class="text-sm text-gray-500">Bid Amount:</span>
                                    <p class="font-semibold text-gray-900">
                                        <?php echo format_currency($proposal['bid_amount'], $proposal['currency']); ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Timeline:</span>
                                    <p class="font-semibold text-gray-900">
                                        <?php echo $proposal['estimated_days']; ?> days
                                    </p>
                                </div>
                            </div>

                            <!-- Job Budget Range -->
                            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                                <span class="text-sm text-gray-500">Job Budget Range:</span>
                                <p class="font-semibold text-gray-900">
                                    <?php echo format_currency($proposal['budget_min'], 'NGN'); ?> - <?php echo format_currency($proposal['budget_max'], 'NGN'); ?>
                                </p>
                            </div>

                            <!-- Submission Info -->
                            <div class="flex items-center justify-between text-sm text-gray-500">
                                <span>Submitted: <?php echo time_ago($proposal['submitted_at']); ?></span>
                                <?php if ($proposal['reviewed_at']): ?>
                                    <span>Reviewed: <?php echo time_ago($proposal['reviewed_at']); ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Admin Notes (if any) -->
                            <?php if (!empty($proposal['admin_notes'])): ?>
                                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                    <h4 class="text-sm font-medium text-blue-900 mb-1">Admin Notes:</h4>
                                    <p class="text-sm text-blue-800"><?php echo htmlspecialchars($proposal['admin_notes']); ?></p>
                                </div>
                            <?php endif; ?>

                            <!-- Action Buttons -->
                            <div class="mt-6 flex space-x-3">
                                <a href="job-details.php?id=<?php echo $proposal['job_id']; ?>" class="flex-1 bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors duration-200 text-center">
                                    View Job
                                </a>
                                <?php if ($proposal['status'] === 'submitted' || $proposal['status'] === 'under_review'): ?>
                                    <button onclick="withdrawProposal(<?php echo $proposal['id']; ?>)" class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition-colors duration-200">
                                        Withdraw
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Statistics -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-6">
                <?php
                $stats = [
                    'submitted' => 0,
                    'under_review' => 0,
                    'accepted' => 0,
                    'rejected' => 0
                ];
                
                foreach ($proposals as $proposal) {
                    if (isset($stats[$proposal['status']])) {
                        $stats[$proposal['status']]++;
                    }
                }
                ?>
                
                <div class="bg-white rounded-lg card-shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Submitted</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['submitted']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg card-shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Under Review</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['under_review']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg card-shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Accepted</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['accepted']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg card-shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Rejected</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['rejected']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });

        // Withdraw proposal function
        function withdrawProposal(proposalId) {
            if (confirm('Are you sure you want to withdraw this proposal? This action cannot be undone.')) {
                // This would typically make an AJAX call to withdraw the proposal
                alert('Proposal withdrawal functionality will be implemented soon.');
            }
        }
    </script>
</body>
</html>
