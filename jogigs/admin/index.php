<?php
// JLM Freelancer Web App - Admin Dashboard

// Prevent any output before session configuration
ob_start();

// Session Configuration (must be at the very beginning)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Lax');

// Start session
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Get dashboard statistics
try {
    // Total jobs
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jogigs_jobs");
    $stmt->execute();
    $total_jobs = $stmt->fetch()['total'];
    
    // Open jobs
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jogigs_jobs WHERE status = 'open'");
    $stmt->execute();
    $open_jobs = $stmt->fetch()['total'];
    
    // Total proposals
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jogigs_proposals");
    $stmt->execute();
    $total_proposals = $stmt->fetch()['total'];
    
    // Pending proposals
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jogigs_proposals WHERE status = 'submitted'");
    $stmt->execute();
    $pending_proposals = $stmt->fetch()['total'];
    
    // Total assignments
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jogigs_job_assignments");
    $stmt->execute();
    $total_assignments = $stmt->fetch()['total'];
    
    // Active assignments
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jogigs_job_assignments WHERE status = 'in_progress'");
    $stmt->execute();
    $active_assignments = $stmt->fetch()['total'];
    
    // Total bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM booking_submissions");
    $stmt->execute();
    $total_bookings = $stmt->fetch()['total'];
    
    // Pending bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM booking_submissions WHERE status = 'pending'");
    $stmt->execute();
    $pending_bookings = $stmt->fetch()['total'];
    
    // Total freelancers
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jogigs_users WHERE role = 'freelancer'");
    $stmt->execute();
    $total_freelancers = $stmt->fetch()['total'];
    
    // Recent activities
    $stmt = $pdo->prepare("
        SELECT 'job_created' as type, j.title as description, j.created_at as date
        FROM jogigs_jobs j
        WHERE j.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        UNION ALL
        SELECT 'proposal_submitted' as type, CONCAT('Proposal for job #', p.job_id) as description, p.submitted_at as date
        FROM jogigs_proposals p
        WHERE p.submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        UNION ALL
        SELECT 'job_assigned' as type, CONCAT('Job #', ja.job_id, ' assigned') as description, ja.assignment_date as date
        FROM jogigs_job_assignments ja
        WHERE ja.assignment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY date DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_activities = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Failed to load dashboard statistics: ' . $e->getMessage();
}

$page_title = 'Admin Dashboard - JoGigs';
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
                    <a href="index.php" class="flex items-center px-4 py-2 text-white bg-red-600 rounded-lg">
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
            <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
            <p class="text-gray-600 mt-2">Manage your freelancer platform</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Jobs Card -->
            <div class="bg-white rounded-lg card-shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Jobs</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $total_jobs; ?></p>
                        <p class="text-sm text-gray-500"><?php echo $open_jobs; ?> open</p>
                    </div>
                </div>
            </div>

            <!-- Proposals Card -->
            <div class="bg-white rounded-lg card-shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Proposals</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $total_proposals; ?></p>
                        <p class="text-sm text-gray-500"><?php echo $pending_proposals; ?> pending</p>
                    </div>
                </div>
            </div>

            <!-- Assignments Card -->
            <div class="bg-white rounded-lg card-shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Assignments</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $total_assignments; ?></p>
                        <p class="text-sm text-gray-500"><?php echo $active_assignments; ?> active</p>
                    </div>
                </div>
            </div>

            <!-- Bookings Card -->
            <div class="bg-white rounded-lg card-shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Bookings</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $total_bookings; ?></p>
                        <p class="text-sm text-gray-500"><?php echo $pending_bookings; ?> pending</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Quick Actions -->
            <div class="bg-white rounded-lg card-shadow p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-2 gap-4">
                    <a href="job_management.php?action=create" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Create Job</p>
                            <p class="text-xs text-gray-500">Post new job</p>
                        </div>
                    </a>
                    
                    <a href="booking_management.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Manage Bookings</p>
                            <p class="text-xs text-gray-500">View & process</p>
                        </div>
                    </a>
                    
                    <a href="proposal_management.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Review Proposals</p>
                            <p class="text-xs text-gray-500">Accept/reject</p>
                        </div>
                    </a>
                    
                    <a href="job_management.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Manage Jobs</p>
                            <p class="text-xs text-gray-500">View all jobs</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="bg-white rounded-lg card-shadow p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Recent Activities</h2>
                <div class="space-y-3">
                    <?php if (empty($recent_activities)): ?>
                        <p class="text-gray-500 text-sm">No recent activities</p>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-6 h-6 rounded-full 
                                        <?php echo $activity['type'] === 'job_created' ? 'bg-blue-100' : 
                                               ($activity['type'] === 'proposal_submitted' ? 'bg-green-100' : 'bg-purple-100'); ?> 
                                        flex items-center justify-center">
                                        <svg class="w-3 h-3 
                                            <?php echo $activity['type'] === 'job_created' ? 'text-blue-600' : 
                                                   ($activity['type'] === 'proposal_submitted' ? 'text-green-600' : 'text-purple-600'); ?>" 
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900 truncate">
                                        <?php echo htmlspecialchars($activity['description']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?php echo date('M j, Y g:i A', strtotime($activity['date'])); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Platform Stats -->
        <div class="mt-8 bg-white rounded-lg card-shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Platform Statistics</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <p class="text-2xl font-bold text-blue-600"><?php echo $total_freelancers; ?></p>
                    <p class="text-sm text-gray-500">Total Freelancers</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-green-600"><?php echo $total_jobs; ?></p>
                    <p class="text-sm text-gray-500">Total Jobs Posted</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-purple-600"><?php echo $total_assignments; ?></p>
                    <p class="text-sm text-gray-500">Jobs Assigned</p>
                </div>
            </div>
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
</body>
</html>
