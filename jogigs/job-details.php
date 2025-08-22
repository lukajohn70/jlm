<?php
// JLM Freelancer Web App - Job Details Page
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
$job = null;
$user_proposal = null;
$can_submit_proposal = true;

// Get job details
if (isset($_GET['id'])) {
    $job_id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM jogigs_jobs 
            WHERE id = ? AND status = 'open'
        ");
        $stmt->execute([$job_id]);
        $job = $stmt->fetch();
        
        if (!$job) {
            $error = 'Job not found or not available.';
        } else {
            // Check if user has already submitted a proposal
            $stmt = $pdo->prepare("
                SELECT * FROM jogigs_proposals 
                WHERE job_id = ? AND freelancer_id = ?
            ");
            $stmt->execute([$job_id, $user['id']]);
            $user_proposal = $stmt->fetch();
            
            if ($user_proposal) {
                $can_submit_proposal = false;
            }
        }
    } catch (Exception $e) {
        $error = 'Error loading job details.';
    }
} else {
    $error = 'No job specified.';
}

$page_title = 'Job Details - JoGigs';
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
                <a href="earnings.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600">Earnings</a>
                <a href="profile.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600">Profile</a>
                <a href="logout.php" class="block px-3 py-2 text-gray-700 hover:text-red-600">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                <div class="mt-4">
                    <a href="jobs.php" class="text-blue-600 hover:text-blue-500">← Back to Jobs</a>
                </div>
            </div>
        <?php elseif ($job): ?>
            <!-- Job Details -->
            <div class="bg-white rounded-lg card-shadow overflow-hidden">
                <!-- Job Header -->
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-8 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($job['title']); ?></h1>
                            <p class="text-blue-100 text-lg">Posted <?php echo time_ago($job['created_at']); ?></p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold">
                                <?php echo format_currency($job['budget_min'], 'NGN'); ?> - <?php echo format_currency($job['budget_max'], 'NGN'); ?>
                            </div>
                            <div class="text-blue-100">Budget Range</div>
                        </div>
                    </div>
                </div>

                <!-- Job Content -->
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Main Content -->
                        <div class="lg:col-span-2">
                            <!-- Job Description -->
                            <div class="mb-8">
                                <h2 class="text-xl font-semibold text-gray-900 mb-4">Job Description</h2>
                                <div class="prose max-w-none">
                                    <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                                </div>
                            </div>

                            <!-- Required Skills -->
                            <?php if (!empty($job['skills_required'])): ?>
                                <div class="mb-8">
                                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Required Skills</h2>
                                    <div class="flex flex-wrap gap-2">
                                        <?php 
                                        $skills = explode(',', $job['skills_required']);
                                        foreach ($skills as $skill): 
                                        ?>
                                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                                <?php echo htmlspecialchars(trim($skill)); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Job Details -->
                            <div class="mb-8">
                                <h2 class="text-xl font-semibold text-gray-900 mb-4">Job Details</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span class="text-gray-700">Posted: <?php echo format_date($job['created_at']); ?></span>
                                    </div>
                                    <?php if (!empty($job['deadline'])): ?>
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <span class="text-gray-700">Deadline: <?php echo format_date($job['deadline']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        <span class="text-gray-700">Job Type: <?php echo $job['allow_proposals'] ? 'Proposal-based' : 'Direct Assignment'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="lg:col-span-1">
                            <!-- Action Buttons -->
                            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Apply for this Job</h3>
                                
                                <?php if ($user_proposal): ?>
                                    <div class="text-center">
                                        <div class="mb-4">
                                            <svg class="mx-auto h-12 w-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <h4 class="text-lg font-medium text-gray-900 mb-2">Proposal Submitted</h4>
                                        <p class="text-gray-600 mb-4">You've already submitted a proposal for this job.</p>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                            <?php echo ucfirst($user_proposal['status']); ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-3">
                                        <?php if ($job['allow_proposals']): ?>
                                            <a href="submit_proposal.php?job_id=<?php echo $job['id']; ?>" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-purple-700 transition-all duration-200 text-center block">
                                                Submit Proposal
                                            </a>
                                        <?php else: ?>
                                            <button class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                                                Apply Now
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button onclick="saveJob(<?php echo $job['id']; ?>)" class="w-full border border-gray-300 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-50 transition-colors">
                                            Save Job
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Job Summary -->
                            <div class="bg-white border border-gray-200 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Job Summary</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Budget:</span>
                                        <span class="font-medium"><?php echo format_currency($job['budget_min'], 'NGN'); ?> - <?php echo format_currency($job['budget_max'], 'NGN'); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Posted:</span>
                                        <span class="font-medium"><?php echo time_ago($job['created_at']); ?></span>
                                    </div>
                                    <?php if (!empty($job['deadline'])): ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Deadline:</span>
                                            <span class="font-medium"><?php echo format_date($job['deadline']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Type:</span>
                                        <span class="font-medium"><?php echo $job['allow_proposals'] ? 'Proposal' : 'Direct'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Back to Jobs -->
            <div class="mt-6">
                <a href="jobs.php" class="text-blue-600 hover:text-blue-500 font-medium">
                    ← Back to Jobs
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });

        // Save job function
        function saveJob(jobId) {
            // This would typically make an AJAX call to save the job
            alert('Job saved to your favorites!');
        }
    </script>
</body>
</html>
