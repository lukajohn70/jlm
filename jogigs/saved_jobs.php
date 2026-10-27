<?php
// JLM Freelancer Web App - Saved Jobs
session_start();

// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require authentication
require_auth();

// Get current user
$user = get_current_jogigs_user();

// Get saved jobs
$saved_jobs = get_saved_jobs($user['id'], 50);
$saved_count = get_saved_jobs_count($user['id']);

$page_title = 'Saved Jobs - JoGigs';
$page_description = 'View and manage your saved job opportunities.';
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
                    <a href="dashboard.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
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
                    <a href="saved_jobs.php" class="flex items-center px-4 py-2 text-white bg-blue-600 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                        </svg>
                        Saved Jobs
                    </a>
                    <a href="earnings.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                        Earnings
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
                    <a href="dashboard.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">Dashboard</a>
                    <a href="jobs.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">Jobs</a>
                    <a href="saved_jobs.php" class="flex items-center px-4 py-3 text-white bg-blue-600 rounded-lg">Saved Jobs</a>
                    <a href="earnings.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">Earnings</a>
                    <a href="profile.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">Profile</a>
                </nav>
            </div>

            <!-- Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Saved Jobs</h2>
                    <p class="text-gray-600">You have <?php echo $saved_count; ?> saved job<?php echo $saved_count !== 1 ? 's' : ''; ?>.</p>
                </div>

                <!-- Saved Jobs Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php if (empty($saved_jobs)): ?>
                        <div class="col-span-full">
                            <div class="bg-white rounded-lg card-shadow p-8 text-center">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No Saved Jobs</h3>
                                <p class="text-gray-600 mb-4">You haven't saved any jobs yet. Start browsing and save jobs you're interested in!</p>
                                <a href="jobs.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    Browse Jobs
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($saved_jobs as $job): ?>
                            <div class="bg-white rounded-lg card-shadow p-6 relative">
                                <!-- Saved indicator -->
                                <div class="absolute top-4 right-4">
                                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                                    </svg>
                                </div>
                                
                                <div class="flex justify-between items-start mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 pr-8"><?php echo htmlspecialchars($job['title']); ?></h3>
                                    <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                        <?php echo ucfirst($job['status']); ?>
                                    </span>
                                </div>
                                
                                <p class="text-gray-600 mb-4 line-clamp-3">
                                    <?php echo htmlspecialchars(substr($job['description'], 0, 150)) . (strlen($job['description']) > 150 ? '...' : ''); ?>
                                </p>
                                
                                <div class="space-y-2 mb-4">
                                    <?php if ($job['budget_min'] && $job['budget_max']): ?>
                                        <div class="flex items-center text-sm text-gray-600">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                            </svg>
                                            Budget: <?php echo format_currency($job['budget_min'], $user['currency'] ?? 'NGN'); ?> - <?php echo format_currency($job['budget_max'], $user['currency'] ?? 'NGN'); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($job['deadline']): ?>
                                        <div class="flex items-center text-sm text-gray-600">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            Deadline: <?php echo format_date($job['deadline']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                                        </svg>
                                        Saved: <?php echo time_ago($job['saved_at']); ?>
                                    </div>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <a href="job_details.php?id=<?php echo $job['id']; ?>" class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2 rounded-lg hover:from-blue-600 hover:to-purple-700 transition-all duration-200 text-center">
                                        View Details
                                    </a>
                                    <button class="unsave-job-btn px-4 py-2 border border-red-300 text-red-700 rounded-lg hover:bg-red-50 transition-colors" data-job-id="<?php echo $job['id']; ?>">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenuIconOpen = document.getElementById('mobile-menu-icon-open');
        const mobileMenuIconClose = document.getElementById('mobile-menu-icon-close');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            mobileMenuIconOpen.classList.toggle('hidden');
            mobileMenuIconClose.classList.toggle('hidden');
        });

        // Unsave job functionality
        document.querySelectorAll('.unsave-job-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const jobId = this.getAttribute('data-job-id');
                const jobCard = this.closest('.bg-white');
                
                if (confirm('Are you sure you want to remove this job from your saved list?')) {
                    // Disable button during request
                    this.disabled = true;
                    this.textContent = 'Removing...';
                    
                    // Send AJAX request
                    fetch('ajax_save_job.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=unsave&job_id=${jobId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the job card from the page
                            jobCard.remove();
                            
                            // Update the count
                            const countElement = document.querySelector('p.text-gray-600');
                            const currentCount = parseInt(countElement.textContent.match(/\d+/)[0]);
                            const newCount = currentCount - 1;
                            countElement.textContent = `You have ${newCount} saved job${newCount !== 1 ? 's' : ''}.`;
                            
                            // If no more jobs, show empty state
                            if (newCount === 0) {
                                location.reload();
                            }
                        } else {
                            alert(data.message || 'An error occurred');
                            this.disabled = false;
                            this.textContent = 'Remove';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while removing the job');
                        this.disabled = false;
                        this.textContent = 'Remove';
                    });
                }
            });
        });
    </script>
</body>
</html>
