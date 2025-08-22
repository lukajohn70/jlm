<?php
// JLM Freelancer Web App - Admin Login Page

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

// Check if user is already logged in as admin
if (is_logged_in()) {
    $user = get_current_jogigs_user();
    if ($user && $user['email'] === 'admin@jogigs.com') {
        header('Location: admin/index.php');
        exit();
    }
}

// Handle admin login form submission
$error_message = '';
$success_message = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error_message = 'Please enter both email and password.';
    } else {
        // Only allow admin login on this page
        if ($email === 'admin@jogigs.com') {
            $result = login_user($email, $password);
            
            if ($result['success']) {
                $user = get_current_jogigs_user();
                if ($user && $user['email'] === 'admin@jogigs.com') {
                    $success_message = 'Login successful! Redirecting to admin dashboard...';
                    echo "<script>setTimeout(function() { window.location.href = 'admin/index.php'; }, 1000);</script>";
                } else {
                    $error_message = 'Access denied: Admin privileges required.';
                    // Log out the user
                    session_destroy();
                    session_start();
                }
            } else {
                $error_message = $result['message'];
            }
        } else {
            $error_message = 'This page is for admin access only.';
        }
    }
}

$page_title = 'JoGigs - Admin Login';
$page_description = 'Admin access to JoGigs platform management.';
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
            
            .w-full.max-w-md {
                max-width: 100%;
                margin: 0;
            }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo and Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full mb-4 card-shadow">
                <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">JoGigs Admin</h1>
            <p class="text-white opacity-90">Admin access portal</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl card-shadow p-8">
            <!-- Error Message -->
            <?php if (!empty($error_message)): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php endif; ?>

            <!-- Success Message -->
            <?php if (!empty($success_message)): ?>
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-green-600 text-sm"><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="" class="space-y-6">
                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Admin Email
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg input-focus focus:outline-none transition-colors"
                        placeholder="Enter admin email"
                        required
                    >
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Password
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg input-focus focus:outline-none transition-colors"
                        placeholder="Enter admin password"
                        required
                    >
                </div>

                <!-- Admin Notice -->
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-blue-700 text-sm">
                        <strong>Admin Access Only:</strong> This page is restricted to authorized administrators only.
                    </p>
                </div>

                <!-- Login Button -->
                <button 
                    type="submit" 
                    name="login"
                    class="w-full btn-primary text-white font-semibold py-3 px-4 rounded-lg"
                >
                    Sign In as Admin
                </button>
            </form>

            <!-- Divider -->
            <div class="my-6 flex items-center">
                <div class="flex-1 border-t border-gray-300"></div>
                <span class="px-4 text-sm text-gray-500">or</span>
                <div class="flex-1 border-t border-gray-300"></div>
            </div>

            <!-- Back to Main Site -->
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    <a href="landing.php" class="text-blue-600 hover:text-blue-500 font-medium">
                        ← Back to JoGigs
                    </a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
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
        document.querySelector('form').addEventListener('submit', function(e) {
            const button = this.querySelector('button[type="submit"]');
            button.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Signing in...';
            button.disabled = true;
        });

        // Auto-focus email field
        document.getElementById('email').focus();
    </script>
</body>
</html>
