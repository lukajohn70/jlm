<?php
// JLM Freelancer Web App - Dashboard
session_start();

// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require authentication
require_auth();

// Get current user
$user = get_current_jogigs_user();

// Check if user is admin and redirect to admin dashboard
if ($user['email'] === 'admin@jogigs.com') {
    header('Location: admin_dashboard.php');
    exit;
}

// Get user statistics
$total_earnings = calculate_total_earnings($user['id']);
$recent_earnings = get_user_earnings($user['id'], 5);
$available_jobs = get_jobs_for_freelancer(5);

// Get freelancer's job assignments
$my_assignments = get_freelancer_assignments($user['id']);
$pending_assignments = array_filter($my_assignments, function($assignment) {
    return $assignment['status'] === 'offered';
});
$active_assignments = array_filter($my_assignments, function($assignment) {
    return in_array($assignment['status'], ['accepted', 'in_progress']);
});
$completed_assignments = array_filter($my_assignments, function($assignment) {
    return $assignment['status'] === 'completed';
});

// Get user's proposals
$my_proposals = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, j.title as job_title
        FROM jogigs_proposals p
        JOIN jogigs_jobs j ON p.job_id = j.id
        WHERE p.freelancer_id = ?
        ORDER BY p.submitted_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user['id']]);
    $my_proposals = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}

$page_title = 'Dashboard - JoGigs';
$page_description = 'Manage your freelancer profile, view earnings, and find new opportunities.';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    
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
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .card-shadow {
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }
            
            .w-64.sidebar-bg {
                width: 100%;
                min-height: auto;
                position: relative;
            }
            
            .flex {
                flex-direction: column;
            }
            
            .grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.gap-6 {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .max-w-7xl.mx-auto.px-4.sm\\:px-6.lg\\:px-8 {
                padding: 1rem;
            }
            
            .p-6 {
                padding: 1rem;
            }
            
            .text-lg.font-medium {
                font-size: 1.125rem;
            }
            
            .text-2xl.font-bold {
                font-size: 1.5rem;
            }
            
            .text-3xl.font-bold {
                font-size: 1.875rem;
            }
        }
        
        @media (max-width: 480px) {
            .p-6 {
                padding: 0.75rem;
            }
            
            .text-lg.font-medium {
                font-size: 1rem;
            }
            
            .text-2xl.font-bold {
                font-size: 1.25rem;
            }
            
            .text-3xl.font-bold {
                font-size: 1.5rem;
            }
            
            .w-8.h-8 {
                width: 2rem;
                height: 2rem;
            }
            
            .text-xl.font-semibold {
                font-size: 1.125rem;
            }
            
            .h-16 {
                height: 4rem;
            }
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
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white text-sm font-bold">JLM</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <h1 class="text-xl font-semibold text-gray-900">JoGigs</h1>
                    </div>
                </div>
                
                <!-- Desktop User Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                            <span class="text-gray-600 text-sm font-medium">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                            </span>
                        </div>
                        <span class="text-sm text-gray-700"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                    </div>
                    <a href="logout.php" class="text-sm text-gray-500 hover:text-gray-700">Logout</a>
                </div>

                <!-- Mobile Menu Button -->
                <button id="mobile-menu-toggle" class="md:hidden p-2 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
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
                    <a href="dashboard.php" class="flex items-center px-4 py-2 text-white bg-blue-600 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                        </svg>
                        Dashboard
                    </a>
                    <a href="jobs.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                        </svg>
                        Jobs
                    </a>
                    <a href="my-proposals.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        My Proposals
                    </a>
                    <a href="earnings.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                        Earnings
                    </a>
                    <a href="saved_jobs.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                        </svg>
                        Saved Jobs
                    </a>
                    <a href="profile.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Profile
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1">
            <!-- Mobile Menu -->
            <div id="mobile-menu" class="md:hidden sidebar-bg text-white">
                <nav class="p-4 space-y-2">
                    <a href="dashboard.php" class="flex items-center px-4 py-3 text-white bg-blue-600 rounded-lg">Dashboard</a>
                    <a href="jobs.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">Jobs</a>
                    <a href="my-proposals.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">My Proposals</a>
                    <a href="earnings.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">Earnings</a>
                    <a href="saved_jobs.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">Saved Jobs</a>
                    <a href="profile.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">Profile</a>
                </nav>
            </div>

            <div class="p-8">
            <!-- Flash Messages -->
            <?php $flash = get_flash_message(); ?>
            <?php if ($flash): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <!-- Welcome Section -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
                <p class="text-gray-600">Here's what's happening with your freelancer account.</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg card-shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Earnings</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo format_currency($total_earnings, $user['currency'] ?? 'NGN'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg card-shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Available Jobs</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo count($available_jobs); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg card-shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Hourly Rate</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo format_currency($user['hourly_rate'], $user['currency'] ?? 'NGN'); ?>/hr</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg card-shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-indigo-100">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">My Proposals</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo count($my_proposals); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Earnings -->
                <div class="bg-white rounded-lg card-shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Earnings</h3>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recent_earnings)): ?>
                            <p class="text-gray-500 text-center py-4">No recent earnings</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($recent_earnings as $earning): ?>
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($earning['description']); ?></p>
                                            <p class="text-sm text-gray-500"><?php echo time_ago($earning['created_at']); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-green-600"><?php echo format_currency($earning['amount'], $user['currency'] ?? 'NGN'); ?></p>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $earning['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                <?php echo ucfirst($earning['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="mt-4">
                            <a href="earnings.php" class="text-sm text-blue-600 hover:text-blue-500">View all earnings →</a>
                        </div>
                    </div>
                </div>

                <!-- Available Jobs -->
                <div class="bg-white rounded-lg card-shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Available Jobs</h3>
                    </div>
                    <div class="p-6">
                        <?php if (empty($available_jobs)): ?>
                            <p class="text-gray-500 text-center py-4">No available jobs at the moment</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($available_jobs as $job): ?>
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <h4 class="text-sm font-medium text-gray-900 mb-1"><?php echo htmlspecialchars($job['title']); ?></h4>
                                        <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars(substr($job['description'], 0, 100)) . '...'; ?></p>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-500"><?php echo time_ago($job['created_at']); ?></span>
                                            <a href="job-details.php?id=<?php echo $job['id']; ?>" class="text-sm text-blue-600 hover:text-blue-500">View Details</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="mt-4">
                            <a href="jobs.php" class="text-sm text-blue-600 hover:text-blue-500">View all jobs →</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Proposals Section -->
            <div class="mt-8">
                <div class="bg-white rounded-lg card-shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Proposals</h3>
                    </div>
                    <div class="p-6">
                        <?php if (empty($my_proposals)): ?>
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No proposals yet</h3>
                                <p class="mt-1 text-sm text-gray-500">You haven't submitted any proposals yet. Start by browsing available jobs.</p>
                                <div class="mt-6">
                                    <a href="jobs.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors duration-200">
                                        Browse Jobs
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($my_proposals as $proposal): ?>
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($proposal['proposal_title']); ?></h4>
                                            <span class="px-2 py-1 text-xs font-medium <?php 
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
                                        <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($proposal['job_title']); ?></p>
                                        <div class="flex items-center justify-between">
                                            <div class="text-sm text-gray-500">
                                                <span class="font-medium"><?php echo format_currency($proposal['bid_amount'], $proposal['currency']); ?></span>
                                                <span class="mx-2">•</span>
                                                <span><?php echo time_ago($proposal['submitted_at']); ?></span>
                                            </div>
                                            <a href="job-details.php?id=<?php echo $proposal['job_id']; ?>" class="text-sm text-blue-600 hover:text-blue-500">View Job</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-4">
                                <a href="my-proposals.php" class="text-sm text-blue-600 hover:text-blue-500">View all proposals →</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Job Assignments Section -->
            <div class="mt-8">
                <div class="bg-white rounded-lg card-shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">My Job Assignments</h3>
                    </div>
                    <div class="p-6">
                        <?php if (empty($my_assignments)): ?>
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No job assignments</h3>
                                <p class="mt-1 text-sm text-gray-500">You haven't been assigned any jobs yet. Check the jobs page for available opportunities.</p>
                                <div class="mt-6">
                                    <a href="jobs.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors duration-200">
                                        Browse Jobs
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Pending Assignments -->
                            <?php if (!empty($pending_assignments)): ?>
                                <div class="mb-6">
                                    <h4 class="text-md font-medium text-gray-900 mb-3">Pending Offers (<?php echo count($pending_assignments); ?>)</h4>
                                    <div class="space-y-3">
                                        <?php foreach ($pending_assignments as $assignment): ?>
                                            <div class="border border-yellow-200 bg-yellow-50 rounded-lg p-4">
                                                <div class="flex items-center justify-between mb-2">
                                                    <h5 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($assignment['title']); ?></h5>
                                                    <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                                                        Pending Response
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars(substr($assignment['description'], 0, 100)) . '...'; ?></p>
                                                <div class="flex items-center justify-between">
                                                    <div class="text-sm text-gray-500">
                                                        <span class="font-medium"><?php echo format_currency($assignment['quote_amount'], $assignment['currency']); ?></span>
                                                        <span class="mx-2">•</span>
                                                        <span>Deadline: <?php echo format_date($assignment['deadline']); ?></span>
                                                    </div>
                                                    <div class="flex space-x-2">
                                                        <button onclick="acceptAssignment(<?php echo $assignment['id']; ?>)" class="bg-green-600 text-white px-3 py-1 rounded text-xs font-medium hover:bg-green-700">
                                                            Accept
                                                        </button>
                                                        <button onclick="declineAssignment(<?php echo $assignment['id']; ?>)" class="bg-red-600 text-white px-3 py-1 rounded text-xs font-medium hover:bg-red-700">
                                                            Decline
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Active Assignments -->
                            <?php if (!empty($active_assignments)): ?>
                                <div class="mb-6">
                                    <h4 class="text-md font-medium text-gray-900 mb-3">Active Jobs (<?php echo count($active_assignments); ?>)</h4>
                                    <div class="space-y-3">
                                        <?php foreach ($active_assignments as $assignment): ?>
                                            <div class="border border-green-200 bg-green-50 rounded-lg p-4">
                                                <div class="flex items-center justify-between mb-2">
                                                    <h5 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($assignment['title']); ?></h5>
                                                    <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                                        <?php echo ucfirst(str_replace('_', ' ', $assignment['status'])); ?>
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars(substr($assignment['description'], 0, 100)) . '...'; ?></p>
                                                <div class="flex items-center justify-between">
                                                    <div class="text-sm text-gray-500">
                                                        <span class="font-medium"><?php echo format_currency($assignment['quote_amount'], $assignment['currency']); ?></span>
                                                        <span class="mx-2">•</span>
                                                        <span>Deadline: <?php echo format_date($assignment['deadline']); ?></span>
                                                    </div>
                                                    <div class="flex space-x-2">
                                                        <?php if ($assignment['status'] === 'accepted'): ?>
                                                            <button onclick="startJob(<?php echo $assignment['id']; ?>)" class="bg-blue-600 text-white px-3 py-1 rounded text-xs font-medium hover:bg-blue-700">
                                                                Start Work
                                                            </button>
                                                        <?php elseif ($assignment['status'] === 'in_progress'): ?>
                                                            <div class="flex space-x-2">
                                                                <a href="submit_job_files.php?assignment_id=<?php echo $assignment['id']; ?>" class="bg-blue-600 text-white px-3 py-1 rounded text-xs font-medium hover:bg-blue-700">
                                                                    Submit Files
                                                                </a>
                                                                <button onclick="completeJob(<?php echo $assignment['id']; ?>)" class="bg-green-600 text-white px-3 py-1 rounded text-xs font-medium hover:bg-green-700">
                                                                    Mark Complete
                                                                </button>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Completed Assignments -->
                            <?php if (!empty($completed_assignments)): ?>
                                <div>
                                    <h4 class="text-md font-medium text-gray-900 mb-3">Completed Jobs (<?php echo count($completed_assignments); ?>)</h4>
                                    <div class="space-y-3">
                                        <?php foreach ($completed_assignments as $assignment): ?>
                                            <div class="border border-gray-200 bg-gray-50 rounded-lg p-4">
                                                <div class="flex items-center justify-between mb-2">
                                                    <h5 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($assignment['title']); ?></h5>
                                                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                                                        Completed
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars(substr($assignment['description'], 0, 100)) . '...'; ?></p>
                                                <div class="text-sm text-gray-500">
                                                    <span class="font-medium"><?php echo format_currency($assignment['quote_amount'], $assignment['currency']); ?></span>
                                                    <span class="mx-2">•</span>
                                                    <span>Completed: <?php echo format_date($assignment['completed_at']); ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Payout Accounts Section -->
            <div class="mt-8">
                <div class="bg-white rounded-lg card-shadow">
                    <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">Payout Accounts</h3>
                        <button onclick="openPayoutModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors duration-200">
                            Add Account
                        </button>
                    </div>
                    <div class="p-6">
                        <?php 
                        $payout_accounts = get_user_payout_accounts($user['id']);
                        if (empty($payout_accounts)): 
                        ?>
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No payout accounts</h3>
                                <p class="mt-1 text-sm text-gray-500">Get started by adding a payout account to receive your earnings.</p>
                                <div class="mt-6">
                                    <button onclick="openPayoutModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors duration-200">
                                        Add Your First Account
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($payout_accounts as $account): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                <?php if ($account['account_type'] === 'bank'): ?>
                                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                                    </svg>
                                                <?php elseif ($account['account_type'] === 'paypal'): ?>
                                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                    </svg>
                                                <?php else: ?>
                                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                    </svg>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($account['account_name']); ?></h4>
                                                <p class="text-sm text-gray-500">
                                                    <?php 
                                                    switch ($account['account_type']) {
                                                        case 'bank':
                                                            echo htmlspecialchars($account['bank_name'] . ' - ' . substr($account['account_number'], -4));
                                                            break;
                                                        case 'paypal':
                                                            echo htmlspecialchars($account['paypal_email']);
                                                            break;
                                                        case 'mobile_money':
                                                            echo htmlspecialchars($account['mobile_provider'] . ' - ' . $account['mobile_number']);
                                                            break;
                                                        default:
                                                            echo ucfirst($account['account_type']);
                                                    }
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <?php if ($account['is_default']): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Default
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($account['is_verified']): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    Verified
                                                </span>
                                            <?php endif; ?>
                                            <button onclick="editPayoutAccount(<?php echo $account['id']; ?>)" class="text-blue-600 hover:text-blue-500 text-sm">
                                                Edit
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payout Account Modal -->
    <div id="payoutModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Add Payout Account</h3>
                    <button onclick="closePayoutModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="payoutForm" method="POST" action="payout_accounts.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="account_id" id="accountId">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Type</label>
                            <select name="account_type" id="accountType" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <option value="">Select account type</option>
                                <option value="bank">Bank Account</option>
                                <option value="paypal">PayPal</option>
                                <option value="mobile_money">Mobile Money</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                            <input type="text" name="account_name" id="accountName" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        
                        <!-- Bank Account Fields -->
                        <div id="bankFields" class="hidden space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                                <input type="text" name="bank_name" id="bankName" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Account Number</label>
                                <input type="text" name="account_number" id="accountNumber" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <!-- PayPal Fields -->
                        <div id="paypalFields" class="hidden">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PayPal Email</label>
                                <input type="email" name="paypal_email" id="paypalEmail" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <!-- Mobile Money Fields -->
                        <div id="mobileFields" class="hidden space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                                <input type="text" name="mobile_number" id="mobileNumber" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Provider</label>
                                <select name="mobile_provider" id="mobileProvider" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select provider</option>
                                    <option value="MTN">MTN</option>
                                    <option value="Airtel">Airtel</option>
                                    <option value="Glo">Glo</option>
                                    <option value="9mobile">9mobile</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="is_default" id="isDefault" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="isDefault" class="ml-2 text-sm text-gray-700">Set as default account</label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closePayoutModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                            Save Account
                        </button>
                    </div>
                </form>
            </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Auto-refresh dashboard every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);

        // Function to open the payout modal
        function openPayoutModal() {
            document.getElementById('payoutModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = 'Add Payout Account';
            document.getElementById('accountId').value = '';
            document.getElementById('payoutForm').reset();
            hideAllFields();
        }

        // Function to close the payout modal
        function closePayoutModal() {
            document.getElementById('payoutModal').classList.add('hidden');
            document.getElementById('payoutForm').reset();
            hideAllFields();
        }

        // Function to hide all field groups
        function hideAllFields() {
            document.getElementById('bankFields').classList.add('hidden');
            document.getElementById('paypalFields').classList.add('hidden');
            document.getElementById('mobileFields').classList.add('hidden');
        }

        // Function to show fields based on account type
        function showFieldsForAccountType(accountType) {
            hideAllFields();
            switch(accountType) {
                case 'bank':
                    document.getElementById('bankFields').classList.remove('hidden');
                    break;
                case 'paypal':
                    document.getElementById('paypalFields').classList.remove('hidden');
                    break;
                case 'mobile_money':
                    document.getElementById('mobileFields').classList.remove('hidden');
                    break;
            }
        }

        // Function to edit an existing payout account
        function editPayoutAccount(accountId) {
            document.getElementById('payoutModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = 'Edit Payout Account';
            document.getElementById('accountId').value = accountId;

            // Fetch account details
            fetch(`includes/api/get_payout_account.php?id=${accountId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const account = data.account;
                        document.getElementById('accountType').value = account.account_type;
                        document.getElementById('accountName').value = account.account_name;
                        document.getElementById('isDefault').checked = account.is_default == 1;

                        showFieldsForAccountType(account.account_type);

                        if (account.account_type === 'bank') {
                            document.getElementById('bankName').value = account.bank_name || '';
                            document.getElementById('accountNumber').value = account.account_number || '';
                        } else if (account.account_type === 'paypal') {
                            document.getElementById('paypalEmail').value = account.paypal_email || '';
                        } else if (account.account_type === 'mobile_money') {
                            document.getElementById('mobileNumber').value = account.mobile_number || '';
                            document.getElementById('mobileProvider').value = account.mobile_provider || '';
                        }
                    } else {
                        alert('Error fetching account details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching account details.');
                });
        }

        // Add event listener for account type change
        document.getElementById('accountType').addEventListener('change', function() {
            showFieldsForAccountType(this.value);
        });

        // Handle form submission
        document.getElementById('payoutForm').addEventListener('submit', function(event) {
            event.preventDefault();
            
            const formData = new FormData(this);
            const accountId = document.getElementById('accountId').value;
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            // Show loading state
            submitButton.textContent = 'Saving...';
            submitButton.disabled = true;

            if (accountId) {
                // Editing existing account
                fetch('includes/api/update_payout_account.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Account updated successfully!');
                        location.reload();
                    } else {
                        alert('Error updating account: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating account.');
                })
                .finally(() => {
                    submitButton.textContent = originalText;
                    submitButton.disabled = false;
                });
            } else {
                // Adding new account
                fetch('includes/api/add_payout_account.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Account added successfully!');
                        location.reload();
                    } else {
                        alert('Error adding account: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding account.');
                })
                .finally(() => {
                    submitButton.textContent = originalText;
                    submitButton.disabled = false;
                });
            }
        });

        // Close modal when clicking outside
        document.getElementById('payoutModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closePayoutModal();
            }
        });

        // Mobile menu toggle - only controls sidebar
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenuIconOpen = document.getElementById('mobile-menu-icon-open');
        const mobileMenuIconClose = document.getElementById('mobile-menu-icon-close');
        const sidebar = document.getElementById('sidebar');

        mobileMenuToggle.addEventListener('click', function() {
            mobileMenuIconOpen.classList.toggle('hidden');
            mobileMenuIconClose.classList.toggle('hidden');
            
            // Toggle sidebar on mobile only
            if (window.innerWidth < 768) {
                sidebar.classList.toggle('hidden');
            }
        });

        // Close sidebar when clicking on a link (mobile only)
        document.querySelectorAll('#sidebar nav a').forEach(function(link) {
            link.addEventListener('click', function() {
                // Hide sidebar on mobile when navigating
                if (window.innerWidth < 768) {
                    sidebar.classList.add('hidden');
                    mobileMenuIconOpen.classList.remove('hidden');
                    mobileMenuIconClose.classList.add('hidden');
                }
            });
        });

        // Job Assignment Functions
        function acceptAssignment(assignmentId) {
            if (confirm('Are you sure you want to accept this job assignment? Once accepted, you cannot decline it without penalties.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'job_actions.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="accept_assignment">
                    <input type="hidden" name="assignment_id" value="${assignmentId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function declineAssignment(assignmentId) {
            if (confirm('Are you sure you want to decline this job assignment?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'job_actions.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="decline_assignment">
                    <input type="hidden" name="assignment_id" value="${assignmentId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function startJob(assignmentId) {
            if (confirm('Are you ready to start working on this job?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'job_actions.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="start_job">
                    <input type="hidden" name="assignment_id" value="${assignmentId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function completeJob(assignmentId) {
            if (confirm('Are you sure you want to mark this job as complete? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'job_actions.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="complete_job">
                    <input type="hidden" name="assignment_id" value="${assignmentId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html> 