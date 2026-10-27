<?php
// JLM Freelancer Web App - Submit Proposal
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

// Get job details
if (isset($_GET['job_id'])) {
    $job_id = (int)$_GET['job_id'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM jogigs_jobs 
            WHERE id = ? AND status = 'open' AND allow_proposals = TRUE
        ");
        $stmt->execute([$job_id]);
        $job = $stmt->fetch();
        
        if (!$job) {
            $error = 'Job not found or not available for proposals.';
        }
    } catch (Exception $e) {
        $error = 'Error loading job details.';
    }
} else {
    $error = 'No job specified.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $job) {
    $proposal_data = [
        'title' => trim($_POST['proposal_title'] ?? ''),
        'text' => trim($_POST['proposal_text'] ?? ''),
        'bid_amount' => (float)($_POST['bid_amount'] ?? 0),
        'currency' => $_POST['currency'] ?? 'NGN',
        'estimated_days' => (int)($_POST['estimated_days'] ?? 0),
        'delivery_timeline' => trim($_POST['delivery_timeline'] ?? ''),
        'portfolio_samples' => $_POST['portfolio_samples'] ?? [],
        'additional_notes' => trim($_POST['additional_notes'] ?? '')
    ];
    
    // Validation
    if (empty($proposal_data['title'])) {
        $error = 'Proposal title is required.';
    } elseif (empty($proposal_data['text'])) {
        $error = 'Proposal description is required.';
    } elseif ($proposal_data['bid_amount'] <= 0) {
        $error = 'Please enter a valid bid amount.';
    } elseif ($proposal_data['estimated_days'] <= 0) {
        $error = 'Please enter a valid estimated timeline.';
    } else {
        $result = submit_proposal($job['id'], $user['id'], $proposal_data);
        
        if ($result['success']) {
            $success = 'Your proposal has been submitted successfully!';
        } else {
            $error = $result['message'];
        }
    }
}

$page_title = 'Submit Proposal';
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
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white text-sm font-bold">JLM</span>
                        </div>
                        <span class="text-xl font-semibold text-gray-900">JoGigs</span>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-700">Welcome, <?php echo htmlspecialchars($user['first_name']); ?></span>
                    <a href="logout.php" class="text-sm text-gray-500 hover:text-gray-700">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Submit Proposal</h1>
            <p class="text-gray-600 mt-2">Present your best offer for this project</p>
        </div>

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
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-lg font-medium text-green-800 mb-2">Proposal Submitted!</h3>
                <p class="text-green-700"><?php echo htmlspecialchars($success); ?></p>
                <div class="mt-4">
                    <a href="jobs.php" class="text-blue-600 hover:text-blue-800 font-medium">← Back to Jobs</a>
                </div>
            </div>
        <?php elseif ($job): ?>
            <!-- Job Details -->
            <div class="bg-white rounded-lg card-shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Project Details</h2>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($job['title']); ?></h3>
                        <p class="text-gray-600 mb-4"><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                        
                        <?php if ($job['skills_required']): ?>
                            <div class="mb-4">
                                <h4 class="font-medium text-gray-900 mb-2">Required Skills:</h4>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach (explode(',', $job['skills_required']) as $skill): ?>
                                        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">
                                            <?php echo htmlspecialchars(trim($skill)); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3">Project Information</h4>
                        <div class="space-y-2 text-sm">
                            <?php if ($job['budget_min'] && $job['budget_max']): ?>
                                <div><strong>Budget Range:</strong> ₦<?php echo number_format($job['budget_min']); ?> - ₦<?php echo number_format($job['budget_max']); ?></div>
                            <?php endif; ?>
                            <?php if ($job['deadline']): ?>
                                <div><strong>Deadline:</strong> <?php echo date('M j, Y', strtotime($job['deadline'])); ?></div>
                            <?php endif; ?>
                            <?php if ($job['proposal_deadline']): ?>
                                <div><strong>Proposal Deadline:</strong> <?php echo date('M j, Y', strtotime($job['proposal_deadline'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Proposal Form -->
            <div class="bg-white rounded-lg card-shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Your Proposal</h2>
                
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="proposal_title" class="block text-sm font-medium text-gray-700 mb-2">
                            Proposal Title *
                        </label>
                        <input 
                            type="text" 
                            id="proposal_title" 
                            name="proposal_title" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="e.g., Professional Video Production with 5+ Years Experience"
                            value="<?php echo htmlspecialchars($_POST['proposal_title'] ?? ''); ?>"
                        >
                    </div>
                    
                    <div>
                        <label for="proposal_text" class="block text-sm font-medium text-gray-700 mb-2">
                            Proposal Description *
                        </label>
                        <textarea 
                            id="proposal_text" 
                            name="proposal_text" 
                            rows="6" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Describe your approach, methodology, and why you're the best fit for this project..."
                        ><?php echo htmlspecialchars($_POST['proposal_text'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label for="bid_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                Your Bid Amount (₦) *
                            </label>
                            <input 
                                type="number" 
                                id="bid_amount" 
                                name="bid_amount" 
                                min="0" 
                                step="0.01" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="0.00"
                                value="<?php echo htmlspecialchars($_POST['bid_amount'] ?? ''); ?>"
                            >
                        </div>
                        
                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                                Currency
                            </label>
                            <select 
                                id="currency" 
                                name="currency" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="NGN" <?php echo ($_POST['currency'] ?? 'NGN') === 'NGN' ? 'selected' : ''; ?>>NGN (Naira)</option>
                                <option value="USD" <?php echo ($_POST['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD (Dollar)</option>
                                <option value="GBP" <?php echo ($_POST['currency'] ?? '') === 'GBP' ? 'selected' : ''; ?>>GBP (Pound)</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="estimated_days" class="block text-sm font-medium text-gray-700 mb-2">
                                Estimated Days *
                            </label>
                            <input 
                                type="number" 
                                id="estimated_days" 
                                name="estimated_days" 
                                min="1" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="14"
                                value="<?php echo htmlspecialchars($_POST['estimated_days'] ?? ''); ?>"
                            >
                        </div>
                    </div>
                    
                    <div>
                        <label for="delivery_timeline" class="block text-sm font-medium text-gray-700 mb-2">
                            Delivery Timeline
                        </label>
                        <textarea 
                            id="delivery_timeline" 
                            name="delivery_timeline" 
                            rows="3" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Describe your delivery timeline, milestones, and communication plan..."
                        ><?php echo htmlspecialchars($_POST['delivery_timeline'] ?? ''); ?></textarea>
                    </div>
                    
                    <div>
                        <label for="additional_notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Additional Notes
                        </label>
                        <textarea 
                            id="additional_notes" 
                            name="additional_notes" 
                            rows="3" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Any additional information, questions, or special considerations..."
                        ><?php echo htmlspecialchars($_POST['additional_notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-800">
                                    <strong>Tip:</strong> Make your proposal stand out by clearly explaining your approach, 
                                    relevant experience, and why you're the best choice for this project.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <a 
                            href="jobs.php" 
                            class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors duration-200"
                        >
                            Cancel
                        </a>
                        <button 
                            type="submit" 
                            class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:from-blue-600 hover:to-purple-700 transition-all duration-200"
                        >
                            Submit Proposal
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
