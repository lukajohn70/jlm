<?php
// JLM Freelancer Web App - Reset Password Page

// Prevent any output before session configuration
ob_start();

// Session Configuration (must be at the very beginning)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Lax');

// Start session
session_start();

// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is already logged in
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit();
}

// Get reset token from URL
$token = $_GET['token'] ?? '';

// Handle password reset form submission
$error_message = '';
$success_message = '';
$token_valid = false;

// Verify token first
if (!empty($token)) {
    $user = verify_reset_token($token);
    if ($user) {
        $token_valid = true;
    } else {
        $error_message = 'Invalid or expired reset token. Please request a new password reset.';
    }
} else {
    $error_message = 'No reset token provided. Please use the link from your email.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $error_message = 'Please enter both password fields.';
    } elseif (strlen($new_password) < 8) {
        $error_message = 'Password must be at least 8 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else {
        $result = complete_password_reset($token, $new_password);
        
        if ($result['success']) {
            $success_message = $result['message'];
        } else {
            $error_message = $result['message'];
        }
    }
}

$page_title = 'JoGigs - Reset Password';
$page_description = 'Set your new password for your JoGigs account.';
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
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .input-focus:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .card-shadow {
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            }
            
            .gradient-bg {
                padding: 1rem;
            }
            
            .text-2xl {
                font-size: 1.5rem;
            }
            
            .text-lg {
                font-size: 1.125rem;
            }
        }
    </style>
</head>
<body class="min-h-screen gradient-bg flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center">
            <!-- Logo -->
            <div class="flex justify-center mb-6">
                <a href="landing.php" class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-lg">
                        <span class="text-blue-600 text-lg font-bold">JLM</span>
                    </div>
                    <span class="text-2xl font-bold text-white">JoGigs</span>
                </a>
            </div>
            
            <h2 class="text-3xl font-bold text-white mb-2">
                Reset Password
            </h2>
            <p class="text-white/80 text-lg">
                Enter your new password
            </p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-xl card-shadow p-8">
            <?php if ($error_message): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <?php echo htmlspecialchars($error_message); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">
                                <?php echo htmlspecialchars($success_message); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Success Action -->
                <div class="text-center">
                    <a href="login.php" class="btn-primary text-white font-semibold py-3 px-6 rounded-lg inline-block">
                        Go to Login
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($token_valid && !$success_message): ?>
                <!-- Reset Password Form -->
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <!-- New Password Field -->
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                            New Password
                        </label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg input-focus focus:outline-none transition-colors"
                            placeholder="Enter your new password"
                            required
                            minlength="8"
                        >
                        <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long</p>
                    </div>

                    <!-- Confirm Password Field -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirm New Password
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg input-focus focus:outline-none transition-colors"
                            placeholder="Confirm your new password"
                            required
                            minlength="8"
                        >
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full btn-primary text-white font-semibold py-3 px-4 rounded-lg"
                    >
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>

            <?php if (!$token_valid && !$success_message): ?>
                <!-- Invalid Token Action -->
                <div class="text-center space-y-4">
                    <p class="text-gray-600">
                        Your reset link is invalid or has expired.
                    </p>
                    <a href="forgot-password.php" class="btn-primary text-white font-semibold py-3 px-6 rounded-lg inline-block">
                        Request New Reset Link
                    </a>
                </div>
            <?php endif; ?>

            <!-- Back to Login -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Remember your password? 
                    <a href="login.php" class="text-blue-600 hover:text-blue-500 font-medium">
                        Sign in here
                    </a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center">
            <p class="text-white text-sm opacity-80">
                &copy; <?php echo date('Y'); ?> JLM Creative Media - JoGigs. All rights reserved. | 
                <a href="terms.php" class="text-white opacity-80 hover:opacity-100 transition-opacity duration-200">Terms of Service</a> | 
                <a href="privacy.php" class="text-white opacity-80 hover:opacity-100 transition-opacity duration-200">Privacy Policy</a>
            </p>
        </div>
    </div>

    <!-- JavaScript for enhanced UX -->
    <script>
        // Add loading state to form submission
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const button = this.querySelector('button[type="submit"]');
                button.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Resetting...';
                button.disabled = true;
            });
        }

        // Password confirmation validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        if (newPassword && confirmPassword) {
            function validatePasswords() {
                if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
            
            newPassword.addEventListener('input', validatePasswords);
            confirmPassword.addEventListener('input', validatePasswords);
        }

        // Auto-focus new password field
        if (newPassword) {
            newPassword.focus();
        }
    </script>
</body>
</html>
