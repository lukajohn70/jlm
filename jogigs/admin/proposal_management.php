<?php
// JLM Freelancer Web App - Admin Proposal Management
session_start();



require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require admin authentication
require_auth();
$user = get_current_jogigs_user();

// Check if user is admin
if ($user['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
$success = '';
$action = $_GET['action'] ?? 'list';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_type = $_POST['action_type'] ?? '';
    
    switch ($action_type) {
        case 'accept_proposal':
            $proposal_id = (int)$_POST['proposal_id'];
            $admin_notes = trim($_POST['admin_notes'] ?? '');
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE jogigs_proposals 
                    SET status = 'accepted', accepted_at = NOW(), admin_notes = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$admin_notes, $proposal_id]);
                
                // Get proposal details and assign job
                $stmt = $pdo->prepare("SELECT * FROM jogigs_proposals WHERE id = ?");
                $stmt->execute([$proposal_id]);
                $proposal = $stmt->fetch();
                
                if ($proposal) {
                    $result = assign_job_directly($proposal['job_id'], null, $proposal['freelancer_id']);
                    if ($result['success']) {
                        $success = 'Proposal accepted and job assigned!';
                    } else {
                        $error = 'Proposal accepted but job assignment failed: ' . $result['message'];
                    }
                }
            } catch (Exception $e) {
                $error = 'Failed to accept proposal: ' . $e->getMessage();
            }
            break;
            
        case 'reject_proposal':
            $proposal_id = (int)$_POST['proposal_id'];
            $admin_notes = trim($_POST['admin_notes'] ?? '');
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE jogigs_proposals 
                    SET status = 'rejected', rejected_at = NOW(), admin_notes = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$admin_notes, $proposal_id]);
                $success = 'Proposal rejected successfully!';
            } catch (Exception $e) {
                $error = 'Failed to reject proposal: ' . $e->getMessage();
            }
            break;
            
        case 'review_proposal':
            $proposal_id = (int)$_POST['proposal_id'];
            $admin_notes = trim($_POST['admin_notes'] ?? '');
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE jogigs_proposals 
                    SET status = 'under_review', reviewed_at = NOW(), reviewed_by = ?, admin_notes = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$user['id'], $admin_notes, $proposal_id]);
                $success = 'Proposal marked for review!';
            } catch (Exception $e) {
                $error = 'Failed to review proposal: ' . $e->getMessage();
            }
            break;
    }
}

// Get data based on action
switch ($action) {
    case 'list':
        $status_filter = $_GET['status'] ?? '';
        $where_clause = '';
        $params = [];
        
        if ($status_filter) {
            $where_clause = 'WHERE p.status = ?';
            $params[] = $status_filter;
        }
        
        try {
            $stmt = $pdo->prepare("
                SELECT p.*, j.title as job_title, j.budget_min, j.budget_max,
                       u.first_name, u.last_name, u.email, u.phone
                FROM jogigs_proposals p
                JOIN jogigs_jobs j ON p.job_id = j.id
                JOIN jogigs_users u ON p.freelancer_id = u.id
                {$where_clause}
                ORDER BY p.submitted_at DESC
            ");
            $stmt->execute($params);
            $proposals = $stmt->fetchAll();
            
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
            $proposals = [];
        }
        
        // Debug: Check if proposals variable is set
        if (!isset($proposals)) {
            $proposals = [];
        }
        break;
        
    case 'view':
        $proposal_id = (int)$_GET['proposal_id'];
        $stmt = $pdo->prepare("
            SELECT p.*, j.title as job_title, j.description as job_description, j.budget_min, j.budget_max,
                   u.first_name, u.last_name, u.email, u.phone, u.bio
            FROM jogigs_proposals p
            JOIN jogigs_jobs j ON p.job_id = j.id
            JOIN jogigs_users u ON p.freelancer_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$proposal_id]);
        $proposal = $stmt->fetch();
        break;
        
    default:
        $proposals = [];
        break;
}

$page_title = 'Proposal Management - Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Styles -->
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-shadow {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .sidebar-bg {
            background: linear-gradient(180deg, #1e293b 0%, #334155 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-gradient-to-r from-red-500 to-pink-600 rounded-lg flex items-center justify-center">
                            <span class="text-white text-sm font-bold">A</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <h1 class="text-xl font-semibold text-gray-900">JoGigs Admin</h1>
                    </div>
                </div>
                
                <!-- Desktop User Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-red-300 rounded-full flex items-center justify-center">
                            <span class="text-red-600 text-sm font-medium">A</span>
                        </div>
                        <span class="text-sm text-gray-700">Admin User</span>
                    </div>
                    <a href="../logout.php" class="text-sm text-gray-500 hover:text-gray-700">Logout</a>
                </div>

                <!-- Mobile Menu Button -->
                <button id="mobile-menu-toggle" class="md:hidden p-2 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors duration-200">
                    <svg id="mobile-menu-icon-open" class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <svg id="mobile-menu-icon-close" class="w-6 h-6 text-gray-700 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 sidebar-bg min-h-screen hidden md:block">
            <div class="p-4">
                <nav class="space-y-2">
                    <a href="index.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                        </svg>
                        Dashboard
                    </a>
                    <a href="job_management.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                        </svg>
                        Job Management
                    </a>
                    <a href="booking_management.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Booking Management
                    </a>
                    <a href="proposal_management.php" class="flex items-center px-4 py-2 text-white bg-red-600 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Proposal Management
                    </a>
                    <a href="quotes.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Quotes
                    </a>
                    <a href="jobs.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                        </svg>
                        Jobs
                    </a>
                    <a href="freelancers.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Freelancers
                    </a>
                    <a href="earnings.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                        Earnings
                    </a>
                    <a href="../dashboard.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to JoGigs
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Proposal Management</h1>
            <p class="text-gray-600 mt-2">Review and manage freelancer proposals</p>
        </div>

        <!-- Navigation Tabs -->
        <div class="mb-6">
            <nav class="flex space-x-8">
                <?php 
                $all_active = ($action === 'list' && !isset($_GET['status'])) ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
                $pending_active = ($action === 'list' && isset($_GET['status']) && $_GET['status'] === 'submitted') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
                $review_active = ($action === 'list' && isset($_GET['status']) && $_GET['status'] === 'under_review') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
                ?>
                <a href="?action=list" class="<?php echo $all_active; ?> py-2 px-1 border-b-2 font-medium text-sm">
                    All Proposals
                </a>
                <a href="?action=list&status=submitted" class="<?php echo $pending_active; ?> py-2 px-1 border-b-2 font-medium text-sm">
                    Pending Review
                </a>
                <a href="?action=list&status=under_review" class="<?php echo $review_active; ?> py-2 px-1 border-b-2 font-medium text-sm">
                    Under Review
                </a>
            </nav>
        </div>

        <!-- Flash Messages -->
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-800"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-800"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Content based on action -->
        <?php if ($action === 'list'): ?>
            <!-- Proposals List -->
            <div class="bg-white rounded-lg card-shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">All Proposals</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Freelancer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proposal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bid Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($proposals as $proposal): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($proposal['job_title']); ?></div>
                                            <div class="text-sm text-gray-500">Budget: ₦<?php echo number_format($proposal['budget_min']); ?> - ₦<?php echo number_format($proposal['budget_max']); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($proposal['first_name'] . ' ' . $proposal['last_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($proposal['email']); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($proposal['proposal_title']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($proposal['proposal_text'], 0, 100)) . '...'; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₦<?php echo number_format($proposal['bid_amount']); ?>
                                        <div class="text-xs text-gray-500"><?php echo $proposal['estimated_days']; ?> days</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $proposal['status'] === 'submitted' ? 'bg-blue-100 text-blue-800' : 
                                                   ($proposal['status'] === 'accepted' ? 'bg-green-100 text-green-800' : 
                                                   ($proposal['status'] === 'rejected' ? 'bg-red-100 text-red-800' :
                                                   'bg-yellow-100 text-yellow-800')); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $proposal['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($proposal['submitted_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="?action=view&proposal_id=<?php echo $proposal['id']; ?>" class="text-blue-600 hover:text-blue-900">View Details</a>
                                            <?php if ($proposal['status'] === 'submitted'): ?>
                                                <button onclick="acceptProposal(<?php echo $proposal['id']; ?>)" class="text-green-600 hover:text-green-900">Accept</button>
                                                <button onclick="rejectProposal(<?php echo $proposal['id']; ?>)" class="text-red-600 hover:text-red-900">Reject</button>
                                                <button onclick="reviewProposal(<?php echo $proposal['id']; ?>)" class="text-yellow-600 hover:text-yellow-900">Review</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($proposals)): ?>
                    <div class="px-6 py-8 text-center">
                        <div class="text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No proposals found</h3>
                            <p class="mt-1 text-sm text-gray-500">There are no proposals to display at the moment.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($action === 'view' && isset($proposal)): ?>
            <!-- Proposal Details -->
            <div class="bg-white rounded-lg card-shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-medium text-gray-900">Proposal Details</h2>
                        <a href="?action=list" class="text-blue-600 hover:text-blue-900">← Back to Proposals</a>
                    </div>
                </div>
                
                <div class="px-6 py-4">
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Job Information -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Job Information</h3>
                            <div class="space-y-3">
                                <div>
                                    <strong class="text-gray-700">Job Title:</strong>
                                    <div class="text-gray-900"><?php echo htmlspecialchars($proposal['job_title']); ?></div>
                                </div>
                                <div>
                                    <strong class="text-gray-700">Job Description:</strong>
                                    <div class="text-gray-900"><?php echo nl2br(htmlspecialchars($proposal['job_description'])); ?></div>
                                </div>
                                <div>
                                    <strong class="text-gray-700">Budget Range:</strong>
                                    <div class="text-gray-900">₦<?php echo number_format($proposal['budget_min']); ?> - ₦<?php echo number_format($proposal['budget_max']); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Freelancer Information -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Freelancer Information</h3>
                            <div class="space-y-3">
                                <div>
                                    <strong class="text-gray-700">Name:</strong>
                                    <div class="text-gray-900"><?php echo htmlspecialchars($proposal['first_name'] . ' ' . $proposal['last_name']); ?></div>
                                </div>
                                <div>
                                    <strong class="text-gray-700">Email:</strong>
                                    <div class="text-gray-900"><?php echo htmlspecialchars($proposal['email']); ?></div>
                                </div>
                                <div>
                                    <strong class="text-gray-700">Phone:</strong>
                                    <div class="text-gray-900"><?php echo htmlspecialchars($proposal['phone'] ?? 'Not provided'); ?></div>
                                </div>
                                <?php if ($proposal['bio']): ?>
                                    <div>
                                        <strong class="text-gray-700">Bio:</strong>
                                        <div class="text-gray-900"><?php echo nl2br(htmlspecialchars($proposal['bio'])); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Proposal Details -->
                    <div class="mt-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Proposal Details</h3>
                        <div class="space-y-4">
                            <div>
                                <strong class="text-gray-700">Proposal Title:</strong>
                                <div class="text-gray-900 text-lg"><?php echo htmlspecialchars($proposal['proposal_title']); ?></div>
                            </div>
                            <div>
                                <strong class="text-gray-700">Proposal Text:</strong>
                                <div class="text-gray-900 bg-gray-50 p-4 rounded-lg">
                                    <?php echo nl2br(htmlspecialchars($proposal['proposal_text'])); ?>
                                </div>
                            </div>
                            <div class="grid md:grid-cols-3 gap-4">
                                <div>
                                    <strong class="text-gray-700">Bid Amount:</strong>
                                    <div class="text-gray-900 text-lg">₦<?php echo number_format($proposal['bid_amount']); ?></div>
                                </div>
                                <div>
                                    <strong class="text-gray-700">Estimated Days:</strong>
                                    <div class="text-gray-900"><?php echo $proposal['estimated_days']; ?> days</div>
                                </div>
                                <div>
                                    <strong class="text-gray-700">Status:</strong>
                                    <div class="text-gray-900">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $proposal['status'] === 'submitted' ? 'bg-blue-100 text-blue-800' : 
                                                   ($proposal['status'] === 'accepted' ? 'bg-green-100 text-green-800' : 
                                                   ($proposal['status'] === 'rejected' ? 'bg-red-100 text-red-800' :
                                                   'bg-yellow-100 text-yellow-800')); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $proposal['status'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php if ($proposal['delivery_timeline']): ?>
                                <div>
                                    <strong class="text-gray-700">Delivery Timeline:</strong>
                                    <div class="text-gray-900"><?php echo nl2br(htmlspecialchars($proposal['delivery_timeline'])); ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if ($proposal['additional_notes']): ?>
                                <div>
                                    <strong class="text-gray-700">Additional Notes:</strong>
                                    <div class="text-gray-900"><?php echo nl2br(htmlspecialchars($proposal['additional_notes'])); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Admin Actions -->
                    <?php if ($proposal['status'] === 'submitted'): ?>
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Admin Actions</h3>
                            <div class="flex space-x-4">
                                <button onclick="acceptProposal(<?php echo $proposal['id']; ?>)" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                    Accept Proposal
                                </button>
                                <button onclick="rejectProposal(<?php echo $proposal['id']; ?>)" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                                    Reject Proposal
                                </button>
                                <button onclick="reviewProposal(<?php echo $proposal['id']; ?>)" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                                    Mark for Review
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>

    <!-- Mobile Menu JavaScript -->
    <script>
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const mobileMenuIconOpen = document.getElementById('mobile-menu-icon-open');
        const mobileMenuIconClose = document.getElementById('mobile-menu-icon-close');

        if (mobileMenuToggle && sidebar) {
            mobileMenuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('hidden');
                mobileMenuIconOpen.classList.toggle('hidden');
                mobileMenuIconClose.classList.toggle('hidden');
            });
        }
    </script>

    <!-- JavaScript -->
    <script>
        function acceptProposal(proposalId) {
            const adminNotes = prompt('Enter admin notes (optional):');
            if (confirm('Accept this proposal? This will assign the job to the freelancer.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action_type" value="accept_proposal">
                    <input type="hidden" name="proposal_id" value="${proposalId}">
                    <input type="hidden" name="admin_notes" value="${adminNotes || ''}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function rejectProposal(proposalId) {
            const adminNotes = prompt('Enter rejection reason:');
            if (adminNotes && confirm('Reject this proposal?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action_type" value="reject_proposal">
                    <input type="hidden" name="proposal_id" value="${proposalId}">
                    <input type="hidden" name="admin_notes" value="${adminNotes}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function reviewProposal(proposalId) {
            const adminNotes = prompt('Enter review notes (optional):');
            if (confirm('Mark this proposal for review?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action_type" value="review_proposal">
                    <input type="hidden" name="proposal_id" value="${proposalId}">
                    <input type="hidden" name="admin_notes" value="${adminNotes || ''}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
