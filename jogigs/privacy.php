<?php
// JLM Freelancer Web App - Privacy Policy
session_start();

// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = 'Privacy Policy - JoGigs';
$page_description = 'Privacy Policy for JoGigs freelancer platform.';
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
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Privacy Policy</h1>
                <p class="text-gray-600">Last updated: <?php echo date('F j, Y'); ?></p>
            </div>

            <div class="prose max-w-none">
                <h2 class="text-2xl font-semibold text-gray-900 mb-4">1. Introduction</h2>
                <p class="text-gray-700 mb-6">
                    JoGigs ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our freelancer platform.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">2. Information We Collect</h2>
                <div class="text-gray-700 mb-6 space-y-4">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">2.1. Personal Information</h3>
                    <p>We may collect the following personal information:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Name, email address, and phone number</li>
                        <li>Profile information (skills, experience, portfolio)</li>
                        <li>Payment and billing information</li>
                        <li>Communication preferences</li>
                        <li>Profile pictures and portfolio files</li>
                    </ul>

                    <h3 class="text-xl font-semibold text-gray-900 mb-2">2.2. Usage Information</h3>
                    <p>We automatically collect certain information when you use our Platform:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>IP address and device information</li>
                        <li>Browser type and version</li>
                        <li>Pages visited and time spent</li>
                        <li>Search queries and interactions</li>
                        <li>Error logs and performance data</li>
                    </ul>

                    <h3 class="text-xl font-semibold text-gray-900 mb-2">2.3. Cookies and Tracking</h3>
                    <p>We use cookies and similar technologies to:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Remember your preferences and settings</li>
                        <li>Analyze platform usage and performance</li>
                        <li>Provide personalized content and recommendations</li>
                        <li>Improve user experience and security</li>
                    </ul>
                </div>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">3. How We Use Your Information</h2>
                <div class="text-gray-700 mb-6 space-y-4">
                    <p>We use the collected information for the following purposes:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Provide and maintain our Platform services</li>
                        <li>Process payments and transactions</li>
                        <li>Connect freelancers with clients</li>
                        <li>Send important updates and notifications</li>
                        <li>Improve our services and user experience</li>
                        <li>Ensure platform security and prevent fraud</li>
                        <li>Comply with legal obligations</li>
                        <li>Provide customer support</li>
                    </ul>
                </div>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">4. Information Sharing and Disclosure</h2>
                <div class="text-gray-700 mb-6 space-y-4">
                    <p>We may share your information in the following circumstances:</p>
                    
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">4.1. With Other Users</h3>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Profile information visible to other users</li>
                        <li>Portfolio work and samples</li>
                        <li>Public reviews and ratings</li>
                    </ul>

                    <h3 class="text-xl font-semibold text-gray-900 mb-2">4.2. With Service Providers</h3>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Payment processors for transactions</li>
                        <li>Cloud storage providers for file hosting</li>
                        <li>Analytics and monitoring services</li>
                        <li>Customer support platforms</li>
                    </ul>

                    <h3 class="text-xl font-semibold text-gray-900 mb-2">4.3. Legal Requirements</h3>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Comply with applicable laws and regulations</li>
                        <li>Respond to legal requests and court orders</li>
                        <li>Protect our rights and property</li>
                        <li>Prevent fraud and security threats</li>
                    </ul>
                </div>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">5. Data Security</h2>
                <div class="text-gray-700 mb-6 space-y-4">
                    <p>We implement appropriate security measures to protect your information:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Encryption of sensitive data in transit and at rest</li>
                        <li>Regular security assessments and updates</li>
                        <li>Access controls and authentication</li>
                        <li>Secure data centers and infrastructure</li>
                        <li>Employee training on data protection</li>
                    </ul>
                </div>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">6. Data Retention</h2>
                <p class="text-gray-700 mb-6">
                    We retain your information for as long as necessary to provide our services, comply with legal obligations, resolve disputes, and enforce our agreements. You may request deletion of your account and associated data at any time.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">7. Your Rights and Choices</h2>
                <div class="text-gray-700 mb-6 space-y-4">
                    <p>You have the following rights regarding your personal information:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li><strong>Access:</strong> Request a copy of your personal information</li>
                        <li><strong>Correction:</strong> Update or correct inaccurate information</li>
                        <li><strong>Deletion:</strong> Request deletion of your account and data</li>
                        <li><strong>Portability:</strong> Export your data in a machine-readable format</li>
                        <li><strong>Objection:</strong> Object to certain processing activities</li>
                        <li><strong>Withdrawal:</strong> Withdraw consent for data processing</li>
                    </ul>
                </div>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">8. Children's Privacy</h2>
                <p class="text-gray-700 mb-6">
                    Our Platform is not intended for children under the age of 18. We do not knowingly collect personal information from children under 18. If you believe we have collected information from a child under 18, please contact us immediately.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">9. International Data Transfers</h2>
                <p class="text-gray-700 mb-6">
                    Your information may be transferred to and processed in countries other than your own. We ensure appropriate safeguards are in place to protect your information in accordance with this Privacy Policy.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">10. Third-Party Links</h2>
                <p class="text-gray-700 mb-6">
                    Our Platform may contain links to third-party websites or services. We are not responsible for the privacy practices of these third parties. We encourage you to review their privacy policies before providing any personal information.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">11. Changes to This Privacy Policy</h2>
                <p class="text-gray-700 mb-6">
                    We may update this Privacy Policy from time to time. We will notify you of any material changes by posting the new Privacy Policy on this page and updating the "Last updated" date. Your continued use of the Platform after such changes constitutes acceptance of the updated Privacy Policy.
                </p>

                <h2 class="text-2xl font-semibold text-gray-900 mb-4">12. Contact Us</h2>
                <p class="text-gray-700 mb-6">
                    If you have any questions about this Privacy Policy or our data practices, please contact us at:
                </p>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-gray-700"><strong>Email:</strong> privacy@jogigs.com</p>
                    <p class="text-gray-700"><strong>Phone:</strong> +234 XXX XXX XXXX</p>
                    <p class="text-gray-700"><strong>Address:</strong> JLM Creative Media, Nigeria</p>
                    <p class="text-gray-700"><strong>Data Protection Officer:</strong> dpo@jogigs.com</p>
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
