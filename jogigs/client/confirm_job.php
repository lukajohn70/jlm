<?php
// JLM Freelancer Web App - Client Job Confirmation
session_start();

require_once '../includes/config.php';
require_once '../includes/functions.php';

$error = '';
$success = '';
$job_details = null;
$notification = null;

// Validate token and get job details
if (isset($_GET['token']) && isset($_GET['id'])) {
    $token = $_GET['token'];
    $job_assignment_id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT cn.*, ja.*, j.title, j.description, q.client_name, q.client_email, q.quote_amount
            FROM jogigs_client_notifications cn
            JOIN jogigs_job_assignments ja ON cn.job_assignment_id = ja.id
            JOIN jogigs_jobs j ON ja.job_id = j.id
            JOIN jogigs_quotes q ON ja.quote_id = q.id
            WHERE cn.notification_token = ? AND cn.job_assignment_id = ?
        ");
        $stmt->execute([$token, $job_assignment_id]);
        $notification = $stmt->fetch();
        
        if ($notification) {
            $job_details = $notification;
        } else {
            $error = 'Invalid or expired confirmation link.';
        }
    } catch (Exception $e) {
        $error = 'Error loading job details.';
    }
} else {
    $error = 'Missing confirmation parameters.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $notification) {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
    $feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
    
    if ($rating && $rating >= 1 && $rating <= 5) {
        $result = client_confirm_job($job_assignment_id, $token, $rating, $feedback);
        
        if ($result['success']) {
            $success = 'Thank you! Your confirmation has been received and payment has been released to the freelancer.';
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Please provide a valid rating (1-5 stars).';
    }
}

$page_title = 'Confirm Job Completion';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JLM</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        .star-rating {
            display: inline-flex;
            flex-direction: row-reverse;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            cursor: pointer;
            font-size: 2rem;
            color: #d1d5db;
            transition: color 0.2s;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #fbbf24;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="flex items-center justify-center mb-4">
                <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                    <span class="text-white text-lg font-bold">JLM</span>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Job Completion Confirmation</h1>
            <p class="text-gray-600 mt-2">Review your completed project and confirm payment release</p>
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
                <h3 class="text-lg font-medium text-green-800 mb-2">Confirmation Successful!</h3>
                <p class="text-green-700"><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php elseif ($job_details): ?>
            <!-- Job Details -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Project Details</h2>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($job_details['title']); ?></h3>
                        <p class="text-gray-600 mb-4"><?php echo nl2br(htmlspecialchars($job_details['description'])); ?></p>
                        
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Project Value:</span>
                                <span class="font-medium">₦<?php echo number_format($job_details['quote_amount']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Completed Date:</span>
                                <span class="font-medium"><?php echo date('M j, Y', strtotime($job_details['completed_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3">Client Information</h4>
                        <div class="space-y-2 text-sm">
                            <div><strong>Name:</strong> <?php echo htmlspecialchars($job_details['client_name']); ?></div>
                            <div><strong>Email:</strong> <?php echo htmlspecialchars($job_details['client_email']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Confirmation Form -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Confirm Completion & Release Payment</h2>
                
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Rate your experience (1-5 stars)
                        </label>
                        <div class="star-rating">
                            <input type="radio" name="rating" value="5" id="star5" required>
                            <label for="star5">★</label>
                            <input type="radio" name="rating" value="4" id="star4">
                            <label for="star4">★</label>
                            <input type="radio" name="rating" value="3" id="star3">
                            <label for="star3">★</label>
                            <input type="radio" name="rating" value="2" id="star2">
                            <label for="star2">★</label>
                            <input type="radio" name="rating" value="1" id="star1">
                            <label for="star1">★</label>
                        </div>
                    </div>
                    
                    <div>
                        <label for="feedback" class="block text-sm font-medium text-gray-700 mb-2">
                            Additional Feedback (Optional)
                        </label>
                        <textarea 
                            id="feedback" 
                            name="feedback" 
                            rows="4" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Share your thoughts about the project quality, communication, and overall experience..."
                        ></textarea>
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
                                    <strong>Important:</strong> By confirming this job, you agree to release payment of ₦<?php echo number_format($job_details['quote_amount']); ?> to the freelancer. 
                                    This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <button 
                            type="submit" 
                            class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:from-blue-600 hover:to-purple-700 transition-all duration-200"
                        >
                            Confirm & Release Payment
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="text-center mt-8 text-gray-500">
            <p>&copy; <?php echo date('Y'); ?> JLM Creative Media. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
