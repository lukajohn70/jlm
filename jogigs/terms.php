<?php
// JLM Freelancer Web App - Terms of Service
session_start();

// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = 'Terms of Service - JoGigs';
$page_description = 'Terms of Service for JoGigs freelancer platform.';
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
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .card-shadow {
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }
            
            .max-w-4xl.mx-auto.px-4.sm\\:px-6.lg\\:px-8 {
                padding: 1rem;
            }
            
            .prose {
                font-size: 0.875rem;
            }
            
            .text-3xl.font-bold {
                font-size: 1.875rem;
            }
        }
        
        @media (max-width: 480px) {
            .prose {
                font-size: 0.8rem;
            }
            
            .text-3xl.font-bold {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="landing.php" class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white text-sm font-bold">JLM</span>
                        </div>
                        <span class="text-xl font-bold text-gray-900">JoGigs</span>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-700 hover:text-blue-600 transition-colors duration-200">Sign In</a>
                    <a href="register.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                        Join Now
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg card-shadow p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Terms of Service</h1>
                <p class="text-gray-600">Last updated: <?php echo date('F j, Y'); ?></p>
            </div>

            <div class="prose max-w-none">
                <h2 class="text-2xl font-semibold text-gray-900 mb-4">1. Acceptance of Terms</h2>
                <p class="text-gray-700 mb-6">
                    By accessing and using JoGigs ("the Platform"), you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">2. Description of Service</h2>
                <p class="text-gray-700 mb-6">
                    JoGigs is a freelancer platform that connects creative professionals with clients seeking services in video production, music production, livestreaming, motion graphics, branding, web design, social media, photography, and consultation.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">3. User Accounts</h2>
                <div class="text-gray-700 mb-6 space-y-4">
                    <p>3.1. You must register for an account to use certain features of the Platform.</p>
                    <p>3.2. You are responsible for maintaining the confidentiality of your account credentials.</p>
                    <p>3.3. You must provide accurate, current, and complete information during registration.</p>
                    <p>3.4. You are responsible for all activities that occur under your account.</p>
                </div>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">4. User Responsibilities</h2>
                <div class="text-gray-700 mb-6 space-y-4">
                    <p>4.1. <strong>Freelancers:</strong></p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Provide accurate and truthful information about your skills and experience</li>
                        <li>Deliver work that meets the agreed-upon specifications and quality standards</li>
                        <li>Maintain professional communication with clients</li>
                        <li>Respect intellectual property rights</li>
                        <li>Complete projects within agreed timelines</li>
                    </ul>
                    
                    <p>4.2. <strong>Clients:</strong></p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Provide clear project requirements and specifications</li>
                        <li>Pay for services as agreed upon</li>
                        <li>Provide timely feedback and communication</li>
                        <li>Respect freelancer's time and expertise</li>
                    </ul>
                </div>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">5. Payment Terms</h2>
                <div class="text-gray-700 mb-6 space-y-4">
                    <p>5.1. All payments are processed through secure payment gateways.</p>
                    <p>5.2. Platform fees may apply to transactions.</p>
                    <p>5.3. Payment disputes must be resolved through the Platform's dispute resolution process.</p>
                    <p>5.4. Refunds are subject to Platform policies and the specific terms of each project.</p>
                </div>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">6. Intellectual Property</h2>
                <div class="text-gray-700 mb-6 space-y-4">
                    <p>6.1. Freelancers retain ownership of their work until full payment is received.</p>
                    <p>6.2. Clients receive rights to commissioned work upon full payment.</p>
                    <p>6.3. Portfolio work may be displayed on the Platform with proper attribution.</p>
                    <p>6.4. Users must respect copyright and intellectual property laws.</p>
                </div>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">7. Prohibited Activities</h2>
                <div class="text-gray-700 mb-6 space-y-4">
                    <p>Users are prohibited from:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Violating any applicable laws or regulations</li>
                        <li>Infringing on intellectual property rights</li>
                        <li>Engaging in fraudulent or deceptive practices</li>
                        <li>Harassing or discriminating against other users</li>
                        <li>Attempting to circumvent payment systems</li>
                        <li>Sharing account credentials with others</li>
                        <li>Using the Platform for illegal purposes</li>
                    </ul>
                </div>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">8. Privacy and Data Protection</h2>
                <p class="text-gray-700 mb-6">
                    Your privacy is important to us. Please review our <a href="privacy.php" class="text-blue-600 hover:text-blue-800">Privacy Policy</a>, which also governs your use of the Platform.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">9. Termination</h2>
                <div class="text-gray-700 mb-6 space-y-4">
                    <p>9.1. We may terminate or suspend your account at any time for violations of these terms.</p>
                    <p>9.2. You may terminate your account at any time by contacting support.</p>
                    <p>9.3. Upon termination, your right to use the Platform ceases immediately.</p>
                </div>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">10. Limitation of Liability</h2>
                <p class="text-gray-700 mb-6">
                    JoGigs shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">11. Dispute Resolution</h2>
                <div class="text-gray-700 mb-6 space-y-4">
                    <p>11.1. Disputes between users should first be resolved through direct communication.</p>
                    <p>11.2. Platform disputes will be resolved through our internal dispute resolution process.</p>
                    <p>11.3. Legal disputes shall be resolved in accordance with Nigerian law.</p>
                </div>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">12. Changes to Terms</h2>
                <p class="text-gray-700 mb-6">
                    We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting. Your continued use of the Platform constitutes acceptance of the modified terms.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">13. Contact Information</h2>
                <p class="text-gray-700 mb-6">
                    If you have any questions about these Terms of Service, please contact us at:
                </p>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-gray-700"><strong>Email:</strong> support@jogigs.com</p>
                    <p class="text-gray-700"><strong>Phone:</strong> +234 XXX XXX XXXX</p>
                    <p class="text-gray-700"><strong>Address:</strong> JLM Creative Media, Nigeria</p>
                </div>
            </div>

            <!-- Back to Home -->
            <div class="mt-8 text-center">
                <a href="landing.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                    Back to Home
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-sm opacity-80">
                    &copy; <?php echo date('Y'); ?> JLM Creative Media - JoGigs. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
