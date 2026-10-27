<?php
// JLM Freelancer Web App - Job Details
session_start();

// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require authentication
require_auth();

// Get current user
$user = get_current_jogigs_user();

// Get job ID from URL
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$job_id) {
    header('Location: jobs.php');
    exit;
}

// Get job details
$job = get_job_details($job_id);

if (!$job) {
    header('Location: jobs.php');
    exit;
}

// Check if user has already submitted a proposal for this job
$has_submitted_proposal = check_user_proposal($user['id'], $job_id);

// Check if user has saved this job
$is_saved = is_job_saved($user['id'], $job_id);

$page_title = 'Job Details - ' . htmlspecialchars($job['title']);
$page_description = 'View detailed information about this job opportunity.';
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
            
            .max-w-7xl.mx-auto.px-4.sm\\:px-6.lg\\:px-8 {
                padding: 1rem;
            }
            
            .p-6 {
                padding: 1rem;
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
                    <a href="dashboard.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                        </svg>
                        Dashboard
                    </a>
                    <a href="jobs.php" class="flex items-center px-4 py-2 text-white bg-blue-600 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                        </svg>
                        Jobs
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
                    <a href="jobs.php" class="flex items-center px-4 py-3 text-white bg-blue-600 rounded-lg">Jobs</a>
                    <a href="earnings.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">Earnings</a>
                    <a href="profile.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">Profile</a>
                </nav>
            </div>

            <!-- Job Details Content -->
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Back Button -->
                <div class="mb-6">
                    <a href="jobs.php" class="inline-flex items-center text-sm text-blue-600 hover:text-blue-500">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Back to Jobs
                    </a>
                </div>

                <!-- Job Header -->
                <div class="bg-white rounded-lg card-shadow p-6 mb-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($job['title']); ?></h1>
                            <div class="flex items-center space-x-4 text-sm text-gray-500">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Posted <?php echo time_ago($job['created_at']); ?>
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <?php echo $job['views'] ?? 0; ?> views
                                </span>
                            </div>
                        </div>
                        <span class="px-3 py-1 text-sm font-medium bg-green-100 text-green-800 rounded-full">
                            <?php echo ucfirst($job['status']); ?>
                        </span>
                    </div>

                    <!-- Job Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Budget</p>
                                    <p class="text-lg font-semibold text-gray-900">
                                        <?php echo format_currency($job['budget_min'], $user['currency'] ?? 'NGN'); ?> - <?php echo format_currency($job['budget_max'], $user['currency'] ?? 'NGN'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Deadline</p>
                                    <p class="text-lg font-semibold text-gray-900"><?php echo format_date($job['deadline']); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Proposals</p>
                                    <p class="text-lg font-semibold text-gray-900"><?php echo $job['proposal_count'] ?? 0; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex space-x-4">
                        <?php if ($has_submitted_proposal): ?>
                            <button class="flex-1 bg-gray-400 text-white px-6 py-3 rounded-lg cursor-not-allowed" disabled>
                                Proposal Submitted
                            </button>
                        <?php elseif ($job['allow_proposals']): ?>
                            <a href="submit_proposal.php?job_id=<?php echo $job['id']; ?>" class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-lg hover:from-blue-600 hover:to-purple-700 transition-all duration-200 text-center font-medium">
                                Submit Proposal
                            </a>
                        <?php else: ?>
                            <button class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                Apply Now
                            </button>
                        <?php endif; ?>
                        
                        <button id="saveJobBtn" data-job-id="<?php echo $job['id']; ?>" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium <?php echo $is_saved ? 'bg-blue-50 border-blue-300 text-blue-700' : ''; ?>">
                            <span id="saveJobText"><?php echo $is_saved ? 'Saved' : 'Save Job'; ?></span>
                            <svg id="saveJobIcon" class="w-4 h-4 ml-1 inline <?php echo $is_saved ? 'text-blue-600' : 'text-gray-500'; ?>" fill="<?php echo $is_saved ? 'currentColor' : 'none'; ?>" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Job Description -->
                <div class="bg-white rounded-lg card-shadow p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Project Description</h2>
                    <div class="prose max-w-none">
                        <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                    </div>
                </div>

                <!-- Required Skills -->
                <?php if ($job['skills_required']): ?>
                    <div class="bg-white rounded-lg card-shadow p-6 mb-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Required Skills</h2>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach (explode(',', $job['skills_required']) as $skill): ?>
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">
                                    <?php echo htmlspecialchars(trim($skill)); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Additional Information -->
                <div class="bg-white rounded-lg card-shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Additional Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-medium text-gray-900 mb-2">Project Type</h3>
                            <p class="text-gray-600"><?php echo ucfirst($job['project_type'] ?? 'General'); ?></p>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900 mb-2">Experience Level</h3>
                            <p class="text-gray-600"><?php echo ucfirst($job['experience_level'] ?? 'Any'); ?></p>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900 mb-2">Proposal Deadline</h3>
                            <p class="text-gray-600"><?php echo $job['proposal_deadline'] ? format_date($job['proposal_deadline']) : 'No deadline'; ?></p>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900 mb-2">Client Location</h3>
                            <p class="text-gray-600"><?php echo htmlspecialchars($job['client_location'] ?? 'Not specified'); ?></p>
                        </div>
                    </div>
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

        // Save Job functionality
        const saveJobBtn = document.getElementById('saveJobBtn');
        const saveJobText = document.getElementById('saveJobText');
        const saveJobIcon = document.getElementById('saveJobIcon');

        if (saveJobBtn) {
            saveJobBtn.addEventListener('click', function() {
                const jobId = this.getAttribute('data-job-id');
                const isCurrentlySaved = saveJobText.textContent === 'Saved';
                const action = isCurrentlySaved ? 'unsave' : 'save';

                // Disable button during request
                this.disabled = true;
                this.style.opacity = '0.6';

                // Send AJAX request
                fetch('ajax_save_job.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=${action}&job_id=${jobId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (action === 'save') {
                            // Job was saved
                            saveJobText.textContent = 'Saved';
                            saveJobIcon.style.fill = 'currentColor';
                            saveJobIcon.classList.add('text-blue-600');
                            saveJobIcon.classList.remove('text-gray-500');
                            this.classList.add('bg-blue-50', 'border-blue-300', 'text-blue-700');
                            this.classList.remove('hover:bg-gray-50');
                        } else {
                            // Job was unsaved
                            saveJobText.textContent = 'Save Job';
                            saveJobIcon.style.fill = 'none';
                            saveJobIcon.classList.remove('text-blue-600');
                            saveJobIcon.classList.add('text-gray-500');
                            this.classList.remove('bg-blue-50', 'border-blue-300', 'text-blue-700');
                            this.classList.add('hover:bg-gray-50');
                        }
                    } else {
                        // Show error message
                        alert(data.message || 'An error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while saving the job');
                })
                .finally(() => {
                    // Re-enable button
                    this.disabled = false;
                    this.style.opacity = '1';
                });
            });
        }
    </script>
</body>
</html>
