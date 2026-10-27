<?php
session_start();
require_once __DIR__ . '/config/config.php';

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
$message = '';
$success = false;
$error_type = '';

if ($email && $token) {
    try {
        // Verify the unsubscribe token (you can implement token validation if needed)
        // For now, we'll just check if the email exists and unsubscribe them
        
        $stmt = $pdo->prepare('UPDATE email_subscribers SET status = "unsubscribed", unsubscribed_at = NOW() WHERE email = ? AND status = "active"');
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $success = true;
            $message = 'You have been successfully unsubscribed from our newsletter.';
            
            // Log the unsubscribe action
            error_log("User unsubscribed: $email at " . date('Y-m-d H:i:s'));
        } else {
            // Check if email exists but is already unsubscribed
            $stmt = $pdo->prepare('SELECT status FROM email_subscribers WHERE email = ?');
            $stmt->execute([$email]);
            $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($subscriber) {
                if ($subscriber['status'] === 'unsubscribed') {
                    $success = true;
                    $message = 'You are already unsubscribed from our newsletter.';
                } else {
                    $error_type = 'already_unsubscribed';
                    $message = 'Unable to process unsubscribe request. Please contact support.';
                }
            } else {
                $error_type = 'not_found';
                $message = 'Email address not found in our subscription list.';
            }
        }
    } catch (Exception $e) {
        error_log("Unsubscribe error: " . $e->getMessage());
        $error_type = 'error';
        $message = 'An error occurred while processing your request. Please try again later.';
    }
} else {
    $error_type = 'invalid_link';
    $message = 'Invalid unsubscribe link. Please check your email for the correct link.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unsubscribe | JLM Creative Media</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { 
            background: linear-gradient(135deg, #f7f8fa 0%, #e8eaf6 100%);
            font-family: 'Inter', Arial, sans-serif;
        }
        .gradient-text {
            background: linear-gradient(135deg, #1b2299 0%, #e4306d 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .card-shadow {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .btn-primary {
            background: linear-gradient(135deg, #1b2299 0%, #e4306d 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(27, 34, 153, 0.3);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background: linear-gradient(135deg, #f1f5f9 0%, #cbd5e1 100%);
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Main Card -->
        <div class="bg-white rounded-2xl card-shadow p-8">
            <!-- Logo and Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-r from-[#1b2299] to-[#e4306d] rounded-2xl mb-6">
                    <img src="assets/images/jlm.png" alt="JLM Logo" class="h-12 w-auto">
                </div>
                <h1 class="text-3xl font-bold gradient-text mb-2">Newsletter Unsubscribe</h1>
                <p class="text-gray-600">Manage your email preferences</p>
            </div>
            
            <!-- Status Message -->
            <div class="mb-8">
                <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 rounded-xl p-6 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-green-100 rounded-full mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-green-800 mb-2">Successfully Unsubscribed</h3>
                    <p class="text-green-700"><?php echo htmlspecialchars($message); ?></p>
                </div>
                <?php else: ?>
                <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-red-100 rounded-full mb-4">
                        <?php if ($error_type === 'not_found'): ?>
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <?php else: ?>
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-lg font-semibold text-red-800 mb-2">
                        <?php echo $error_type === 'not_found' ? 'Email Not Found' : 'Unable to Process'; ?>
                    </h3>
                    <p class="text-red-700"><?php echo htmlspecialchars($message); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Additional Information -->
            <div class="bg-gray-50 rounded-xl p-6 mb-8">
                <h4 class="font-semibold text-gray-900 mb-3">What happens next?</h4>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        You'll no longer receive our newsletter emails
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Your data will be kept secure and private
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        You can resubscribe anytime on our website
                    </li>
                </ul>
            </div>
            
            <!-- Action Buttons -->
            <div class="space-y-4">
                <a href="https://jlm.com.ng" 
                   class="w-full btn-primary text-white px-6 py-3 rounded-xl font-semibold text-center block">
                    Return to Website
                </a>
                
                <a href="https://jlm.com.ng#newsletter" 
                   class="w-full btn-secondary text-gray-700 px-6 py-3 rounded-xl font-semibold text-center block">
                    Resubscribe to Newsletter
                </a>
            </div>
            
            <!-- Footer -->
            <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                <p class="text-xs text-gray-500">
                    Questions? Contact us at 
                    <a href="mailto:support@jlm.com.ng" class="text-blue-600 hover:text-blue-800">support@jlm.com.ng</a>
                </p>
            </div>
        </div>
        
        <!-- Additional Info Card -->
        <div class="mt-6 bg-white rounded-xl card-shadow p-6">
            <div class="flex items-center justify-center space-x-4 text-sm text-gray-600">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Secure & Private
                </div>
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    GDPR Compliant
                </div>
            </div>
        </div>
    </div>
</body>
</html> 
