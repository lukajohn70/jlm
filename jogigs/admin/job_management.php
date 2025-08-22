<?php
// JLM Freelancer Web App - Admin Job Management

// Prevent any output before session configuration
ob_start();

// Session Configuration (must be at the very beginning)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Lax');

// Start session
session_start();

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require admin authentication
require_auth();
$user = get_current_jogigs_user();

// Check if user is admin
if (!$user || $user['email'] !== 'admin@jogigs.com') {
    header('Location: ../admin_login.php');
    exit;
}

$error = '';
$success = '';
$action = $_GET['action'] ?? 'list';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_type = $_POST['action_type'] ?? '';
    
    switch ($action_type) {
        case 'create_job':
            $job_data = [
                'title' => trim($_POST['title']),
                'description' => trim($_POST['description']),
                'skills_required' => trim($_POST['skills_required']),
                'budget_min' => (float)$_POST['budget_min'],
                'budget_max' => (float)$_POST['budget_max'],
                'deadline' => $_POST['deadline'],
                'allow_proposals' => isset($_POST['allow_proposals']),
                'proposal_deadline' => $_POST['proposal_deadline'] ?? null
            ];
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO jogigs_jobs (
                        title, description, skills_required, budget_min, budget_max,
                        deadline, status, allow_proposals, proposal_deadline
                    ) VALUES (?, ?, ?, ?, ?, ?, 'open', ?, ?)
                ");
                $stmt->execute([
                    $job_data['title'], $job_data['description'], $job_data['skills_required'],
                    $job_data['budget_min'], $job_data['budget_max'], $job_data['deadline'],
                    $job_data['allow_proposals'], $job_data['proposal_deadline']
                ]);
                $success = 'Job created successfully!';
            } catch (Exception $e) {
                $error = 'Failed to create job: ' . $e->getMessage();
            }
            break;
            
        case 'update_job_status':
            $job_id = (int)$_POST['job_id'];
            $new_status = $_POST['new_status'];
            
            try {
                $stmt = $pdo->prepare("UPDATE jogigs_jobs SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $job_id]);
                $success = 'Job status updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update job status: ' . $e->getMessage();
            }
            break;
            
        case 'assign_job':
            $job_id = (int)$_POST['job_id'];
            $freelancer_id = (int)$_POST['freelancer_id'];
            $assignment_type = $_POST['assignment_type'];
            
            try {
                $result = assign_job_directly($job_id, null, $freelancer_id);
                if ($result['success']) {
                    $success = 'Job assigned successfully!';
                } else {
                    $error = $result['message'];
                }
            } catch (Exception $e) {
                $error = 'Failed to assign job: ' . $e->getMessage();
            }
            break;
            
        case 'mark_completed':
            $assignment_id = (int)$_POST['assignment_id'];
            
            try {
                $result = mark_job_completed($assignment_id);
                if ($result['success']) {
                    $success = 'Job marked as completed and client notified!';
                } else {
                    $error = $result['message'];
                }
            } catch (Exception $e) {
                $error = 'Failed to mark job as completed: ' . $e->getMessage();
            }
            break;
            
        case 'accept_proposal':
            $proposal_id = (int)$_POST['proposal_id'];
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE jogigs_proposals 
                    SET status = 'accepted', accepted_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$proposal_id]);
                
                // Get proposal details and assign job
                $stmt = $pdo->prepare("SELECT * FROM jogigs_proposals WHERE id = ?");
                $stmt->execute([$proposal_id]);
                $proposal = $stmt->fetch();
                
                if ($proposal) {
                    $result = create_assignment_from_proposal($proposal_id);
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
    }
}

// Initialize variables
$jobs = [];
$assignments = [];
$proposals = [];
$freelancers = [];

// Get data based on action
switch ($action) {
    case 'list':
        try {
            $stmt = $pdo->prepare("
                SELECT j.*, 
                       COUNT(DISTINCT p.id) as proposal_count,
                       COUNT(DISTINCT ja.id) as assignment_count
                FROM jogigs_jobs j
                LEFT JOIN jogigs_proposals p ON j.id = p.job_id
                LEFT JOIN jogigs_job_assignments ja ON j.id = ja.job_id
                GROUP BY j.id
                ORDER BY j.created_at DESC
            ");
            $stmt->execute();
            $jobs = $stmt->fetchAll();
        } catch (Exception $e) {
            $error = 'Failed to load jobs: ' . $e->getMessage();
            $jobs = [];
        }
        break;
        
    case 'proposals':
        try {
            $job_id = (int)$_GET['job_id'];
            $stmt = $pdo->prepare("
                SELECT p.*, u.first_name, u.last_name, u.email
                FROM jogigs_proposals p
                JOIN jogigs_users u ON p.freelancer_id = u.id
                WHERE p.job_id = ?
                ORDER BY p.submitted_at DESC
            ");
            $stmt->execute([$job_id]);
            $proposals = $stmt->fetchAll();
            
            $stmt = $pdo->prepare("SELECT * FROM jogigs_jobs WHERE id = ?");
            $stmt->execute([$job_id]);
            $job = $stmt->fetch();
        } catch (Exception $e) {
            $error = 'Failed to load proposals: ' . $e->getMessage();
        }
        break;
        
    case 'assignments':
        try {
            $stmt = $pdo->prepare("
                SELECT ja.*, j.title, u.first_name, u.last_name, u.email
                FROM jogigs_job_assignments ja
                JOIN jogigs_jobs j ON ja.job_id = j.id
                JOIN jogigs_users u ON ja.freelancer_id = u.id
                ORDER BY ja.assignment_date DESC
            ");
            $stmt->execute();
            $assignments = $stmt->fetchAll();
        } catch (Exception $e) {
            $error = 'Failed to load assignments: ' . $e->getMessage();
        }
        break;
        
    case 'create':
        try {
            // Get freelancers for assignment dropdown
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM jogigs_users WHERE email != 'admin@jogigs.com'");
            $stmt->execute();
            $freelancers = $stmt->fetchAll();
        } catch (Exception $e) {
            $error = 'Failed to load freelancers: ' . $e->getMessage();
        }
        break;
        
    case 'view_proposal':
        try {
            $proposal_id = (int)$_GET['id'];
            $stmt = $pdo->prepare("
                SELECT p.*, u.first_name, u.last_name, u.email, j.title as job_title
                FROM jogigs_proposals p
                JOIN jogigs_users u ON p.freelancer_id = u.id
                JOIN jogigs_jobs j ON p.job_id = j.id
                WHERE p.id = ?
            ");
            $stmt->execute([$proposal_id]);
            $proposal = $stmt->fetch();
            
            if (!$proposal) {
                $error = 'Proposal not found';
            }
        } catch (Exception $e) {
            $error = 'Failed to load proposal: ' . $e->getMessage();
        }
        break;
        
    case 'view_assignment':
        try {
            $assignment_id = (int)$_GET['id'];
            $stmt = $pdo->prepare("
                SELECT ja.*, j.title, j.description, u.first_name, u.last_name, u.email
                FROM jogigs_job_assignments ja
                JOIN jogigs_jobs j ON ja.job_id = j.id
                JOIN jogigs_users u ON ja.freelancer_id = u.id
                WHERE ja.id = ?
            ");
            $stmt->execute([$assignment_id]);
            $assignment = $stmt->fetch();
            
            if (!$assignment) {
                $error = 'Assignment not found';
            }
        } catch (Exception $e) {
            $error = 'Failed to load assignment: ' . $e->getMessage();
        }
        break;
}

$page_title = 'Job Management - Admin';
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
                    <a href="job_management.php" class="flex items-center px-4 py-2 text-white bg-red-600 rounded-lg">
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
                    <a href="proposal_management.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
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
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Job Management</h1>
                    <p class="text-gray-600 mt-2">Manage jobs, proposals, and assignments with professional oversight</p>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-2">
                        <div class="text-sm font-medium text-blue-900">Total Jobs</div>
                        <div class="text-2xl font-bold text-blue-600"><?php echo count($jobs); ?></div>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg px-4 py-2">
                        <div class="text-sm font-medium text-green-900">Active</div>
                        <div class="text-2xl font-bold text-green-600"><?php echo count(array_filter($jobs, function($job) { return $job['status'] === 'open'; })); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="mb-6">
            <nav class="flex space-x-1 bg-gray-100 p-1 rounded-lg">
                <a href="?action=list" class="<?php echo $action === 'list' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'; ?> px-4 py-2 rounded-md font-medium text-sm transition-colors duration-200">
                    <span class="inline-flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        All Jobs
                    </span>
                </a>
                <a href="?action=assignments" class="<?php echo $action === 'assignments' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'; ?> px-4 py-2 rounded-md font-medium text-sm transition-colors duration-200">
                    <span class="inline-flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Job Assignments
                    </span>
                </a>
                <a href="?action=create" class="<?php echo $action === 'create' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'; ?> px-4 py-2 rounded-md font-medium text-sm transition-colors duration-200">
                    <span class="inline-flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create Job
                    </span>
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
            <!-- Jobs List -->
            <div class="bg-white rounded-lg card-shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">All Jobs</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Job Details</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Budget Range</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Proposals</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Assignments</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($jobs as $job): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-lg bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm">
                                                    <?php echo strtoupper(substr($job['title'], 0, 2)); ?>
                                                </div>
                                            </div>
                                            <div class="ml-4 flex-1">
                                                <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($job['title']); ?></div>
                                                <div class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars(substr($job['description'], 0, 80)) . (strlen($job['description']) > 80 ? '...' : ''); ?></div>
                                                <div class="text-xs text-gray-400 mt-1">
                                                    <span class="inline-flex items-center">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                        Created: <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                            <?php echo $job['status'] === 'open' ? 'bg-green-100 text-green-800 border border-green-200' : 
                                                   ($job['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' : 
                                                   'bg-gray-100 text-gray-800 border border-gray-200'); ?>">
                                            <span class="w-2 h-2 rounded-full mr-2 
                                                <?php echo $job['status'] === 'open' ? 'bg-green-400' : 
                                                       ($job['status'] === 'in_progress' ? 'bg-yellow-400' : 
                                                       'bg-gray-400'); ?>"></span>
                                            <?php echo ucfirst(str_replace('_', ' ', $job['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            ₦<?php echo number_format($job['budget_min']); ?> - ₦<?php echo number_format($job['budget_max']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <span class="inline-flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                                </svg>
                                                Budget Range
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-center">
                                            <div class="text-lg font-semibold text-blue-600"><?php echo $job['proposal_count']; ?></div>
                                            <div class="text-xs text-gray-500">Proposals</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-center">
                                            <div class="text-lg font-semibold text-purple-600"><?php echo $job['assignment_count']; ?></div>
                                            <div class="text-xs text-gray-500">Assignments</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="?action=proposals&job_id=<?php echo $job['id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                View Proposals
                                            </a>
                                            <button onclick="updateJobStatus(<?php echo $job['id']; ?>)" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                                Update Status
                                            </button>
                                            <button onclick="assignJob(<?php echo $job['id']; ?>)" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors duration-200">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                                Assign
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($action === 'proposals' && isset($job)): ?>
            <!-- Proposals List -->
            <div class="bg-white rounded-lg card-shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-medium text-gray-900">Proposals for: <?php echo htmlspecialchars($job['title']); ?></h2>
                        <a href="?action=list" class="text-blue-600 hover:text-blue-900">← Back to Jobs</a>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Freelancer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proposal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bid Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timeline</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($proposals as $proposal): ?>
                                <tr>
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
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $proposal['estimated_days']; ?> days
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $proposal['status'] === 'submitted' ? 'bg-blue-100 text-blue-800' : 
                                                   ($proposal['status'] === 'accepted' ? 'bg-green-100 text-green-800' : 
                                                   'bg-red-100 text-red-800'); ?>">
                                            <?php echo ucfirst($proposal['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <?php if ($proposal['status'] === 'submitted'): ?>
                                                <button onclick="acceptProposal(<?php echo $proposal['id']; ?>)" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                    Accept
                                                </button>
                                                <button onclick="rejectProposal(<?php echo $proposal['id']; ?>)" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    Reject
                                                </button>
                                            <?php endif; ?>
                                                                                          <button onclick="viewProposalDetails(<?php echo $proposal['id']; ?>)" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                  </svg>
                                                  View Details
                                              </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($action === 'assignments'): ?>
            <!-- Job Assignments -->
            <div class="bg-white rounded-lg card-shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Job Assignments</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Freelancer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client Confirmed</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($assignment['title']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($assignment['email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $assignment['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                                   ($assignment['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 
                                                   'bg-blue-100 text-blue-800'); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $assignment['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y', strtotime($assignment['deadline'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($assignment['client_confirmed']): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                ✓ Confirmed
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <?php if ($assignment['status'] === 'in_progress'): ?>
                                                <button onclick="markCompleted(<?php echo $assignment['id']; ?>)" class="text-green-600 hover:text-green-900">Mark Complete</button>
                                            <?php endif; ?>
                                            <button onclick="viewDetails(<?php echo $assignment['id']; ?>)" class="text-blue-600 hover:text-blue-900">View Details</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($action === 'create'): ?>
            <!-- Create Job Form -->
            <div class="bg-white rounded-lg card-shadow p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6">Create New Job</h2>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action_type" value="create_job">
                    
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Job Title *</label>
                        <input type="text" id="title" name="title" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                        <textarea id="description" name="description" rows="4" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label for="budget_min" class="block text-sm font-medium text-gray-700 mb-2">Minimum Budget (₦) *</label>
                            <input type="number" id="budget_min" name="budget_min" min="0" step="0.01" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="budget_max" class="block text-sm font-medium text-gray-700 mb-2">Maximum Budget (₦) *</label>
                            <input type="number" id="budget_max" name="budget_max" min="0" step="0.01" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label for="skills_required" class="block text-sm font-medium text-gray-700 mb-2">Required Skills</label>
                        <input type="text" id="skills_required" name="skills_required"
                               placeholder="e.g., graphic_design,illustration,web_design"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label for="deadline" class="block text-sm font-medium text-gray-700 mb-2">Deadline *</label>
                            <input type="date" id="deadline" name="deadline" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="proposal_deadline" class="block text-sm font-medium text-gray-700 mb-2">Proposal Deadline</label>
                            <input type="date" id="proposal_deadline" name="proposal_deadline"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="allow_proposals" name="allow_proposals" checked
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="allow_proposals" class="ml-2 block text-sm text-gray-900">
                            Allow freelancers to submit proposals
                        </label>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <a href="?action=list" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors duration-200">
                            Cancel
                        </a>
                        <button type="submit" class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-2 rounded-lg font-medium hover:from-blue-600 hover:to-purple-700 transition-all duration-200">
                            Create Job
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Proposal Details View -->
        <?php if ($action === 'view_proposal' && isset($proposal)): ?>
            <div class="bg-white rounded-lg card-shadow p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Proposal Details</h2>
                    <a href="?action=proposals&job_id=<?php echo $proposal['job_id']; ?>" class="text-blue-600 hover:text-blue-800">← Back to Proposals</a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Job Information</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-gray-500">Job Title:</span>
                                <p class="text-gray-900"><?php echo htmlspecialchars($proposal['job_title']); ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Freelancer:</span>
                                <p class="text-gray-900"><?php echo htmlspecialchars($proposal['first_name'] . ' ' . $proposal['last_name']); ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Email:</span>
                                <p class="text-gray-900"><?php echo htmlspecialchars($proposal['email']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Proposal Details</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-gray-500">Bid Amount:</span>
                                <p class="text-2xl font-bold text-green-600">₦<?php echo number_format($proposal['bid_amount'], 2); ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Timeline:</span>
                                <p class="text-gray-900"><?php echo $proposal['estimated_days']; ?> days</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Status:</span>
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?php echo $proposal['status'] === 'submitted' ? 'bg-blue-100 text-blue-800' : 
                                          ($proposal['status'] === 'accepted' ? 'bg-green-100 text-green-800' : 
                                          'bg-red-100 text-red-800'); ?>">
                                    <?php echo ucfirst($proposal['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Proposal Content</h3>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($proposal['proposal_title']); ?></h4>
                        <p class="text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($proposal['proposal_text']); ?></p>
                    </div>
                </div>
                
                <?php if ($proposal['additional_notes']): ?>
                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Notes</h3>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-gray-700"><?php echo htmlspecialchars($proposal['additional_notes']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="?action=proposals&job_id=<?php echo $proposal['job_id']; ?>" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Back to Proposals
                    </a>
                    <?php if ($proposal['status'] === 'submitted'): ?>
                        <button onclick="acceptProposal(<?php echo $proposal['id']; ?>)" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Accept Proposal
                        </button>
                        <button onclick="rejectProposal(<?php echo $proposal['id']; ?>)" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            Reject Proposal
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Assignment Details View -->
        <?php if ($action === 'view_assignment' && isset($assignment)): ?>
            <div class="bg-white rounded-lg card-shadow p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Assignment Details</h2>
                    <a href="?action=assignments" class="text-blue-600 hover:text-blue-800">← Back to Assignments</a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Job Information</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-gray-500">Job Title:</span>
                                <p class="text-gray-900"><?php echo htmlspecialchars($assignment['title']); ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Description:</span>
                                <p class="text-gray-700"><?php echo htmlspecialchars($assignment['description']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Assignment Details</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-gray-500">Freelancer:</span>
                                <p class="text-gray-900"><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Email:</span>
                                <p class="text-gray-900"><?php echo htmlspecialchars($assignment['email']); ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Status:</span>
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?php echo $assignment['status'] === 'in_progress' ? 'bg-blue-100 text-blue-800' : 
                                          ($assignment['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                          'bg-yellow-100 text-yellow-800'); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $assignment['status'])); ?>
                                </span>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Assigned:</span>
                                <p class="text-gray-900"><?php echo date('M j, Y g:i A', strtotime($assignment['assignment_date'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="?action=assignments" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Back to Assignments
                    </a>
                    <?php if ($assignment['status'] === 'in_progress'): ?>
                        <button onclick="markCompleted(<?php echo $assignment['id']; ?>)" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Mark as Completed
                        </button>
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
        function updateJobStatus(jobId) {
            const newStatus = prompt('Enter new status (open, in_progress, completed, cancelled):');
            if (newStatus) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action_type" value="update_job_status">
                    <input type="hidden" name="job_id" value="${jobId}">
                    <input type="hidden" name="new_status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function assignJob(jobId) {
            const freelancerId = prompt('Enter freelancer ID:');
            if (freelancerId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action_type" value="assign_job">
                    <input type="hidden" name="job_id" value="${jobId}">
                    <input type="hidden" name="freelancer_id" value="${freelancerId}">
                    <input type="hidden" name="assignment_type" value="direct_assignment">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function acceptProposal(proposalId) {
            if (confirm('Accept this proposal? This will assign the job to the freelancer.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action_type" value="accept_proposal">
                    <input type="hidden" name="proposal_id" value="${proposalId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function rejectProposal(proposalId) {
            const adminNotes = prompt('Enter rejection reason (optional):');
            if (confirm('Reject this proposal?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action_type" value="reject_proposal">
                    <input type="hidden" name="proposal_id" value="${proposalId}">
                    <input type="hidden" name="admin_notes" value="${adminNotes || ''}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function markCompleted(assignmentId) {
            if (confirm('Mark this job as completed? This will notify the client for confirmation.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action_type" value="mark_completed">
                    <input type="hidden" name="assignment_id" value="${assignmentId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function viewDetails(assignmentId) {
            // Redirect to assignment details page
            window.location.href = '?action=view_assignment&id=' + assignmentId;
        }

        function viewProposalDetails(proposalId) {
            // Show proposal details in a modal or redirect
            window.location.href = '?action=view_proposal&id=' + proposalId;
        }
    </script>
</body>
</html>
