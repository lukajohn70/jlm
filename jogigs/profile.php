<?php
// JLM Freelancer Web App - Profile Page
session_start();

// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require authentication
require_auth();

// Get current user
$user = get_current_jogigs_user();

// Define JLM services
$jlm_services = [
    'video-production' => 'Video Production',
    'music-production' => 'Music Production',
    'livestreaming' => 'Livestreaming',
    'motion-graphics' => 'Motion Graphics',
    'branding' => 'Branding',
    'web-design' => 'Web Design',
    'social-media' => 'Social Media',
    'photography' => 'Photography',
    'consultation' => 'Consultation'
];

// Define currencies
$currencies = [
    'NGN' => 'Naira (₦)',
    'USD' => 'Dollar ($)',
    'GBP' => 'Pounds (£)',
    'GHS' => 'Cedis (₵)'
];

// Handle profile update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $hourly_rate = floatval($_POST['hourly_rate'] ?? 0);
    $currency = trim($_POST['currency'] ?? 'NGN');
    $bio = trim($_POST['bio'] ?? '');
    $skills = isset($_POST['skills']) ? implode(',', $_POST['skills']) : '';
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE jogigs_users 
                SET first_name = ?, last_name = ?, email = ?, phone = ?, hourly_rate = ?, currency = ?, bio = ?, skills = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$first_name, $last_name, $email, $phone, $hourly_rate, $currency, $bio, $skills, $user['id']]);
            
            if ($result) {
                $success_message = 'Profile updated successfully!';
                // Refresh user data
                $user = get_current_jogigs_user();
            } else {
                $error_message = 'Failed to update profile.';
            }
        } catch (Exception $e) {
            $error_message = 'An error occurred while updating your profile.';
            error_log("Profile update error: " . $e->getMessage());
        }
    }
}

// Get user's current skills
$user_skills = [];
if (!empty($user['skills'])) {
    $user_skills = explode(',', $user['skills']);
}

$page_title = 'Profile - JoGigs';
$page_description = 'Manage your freelancer profile and account settings.';
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
        .checkbox-custom {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            width: 1.2rem;
            height: 1.2rem;
            border: 2px solid #d1d5db;
            border-radius: 0.25rem;
            background-color: white;
            cursor: pointer;
            position: relative;
            transition: all 0.2s;
        }
        .checkbox-custom:checked {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }
        .checkbox-custom:checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 0.75rem;
            font-weight: bold;
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
            
            .grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.gap-4 {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .flex.items-center.space-x-3.cursor-pointer.p-3.border.border-gray-200.rounded-lg {
                padding: 0.75rem;
                margin-bottom: 0.5rem;
            }
            
            .max-w-7xl.mx-auto.px-4.sm\\:px-6.lg\\:px-8 {
                padding: 1rem;
            }
            
            .p-6 {
                padding: 1rem;
            }
            
            .text-lg.font-medium {
                font-size: 1.125rem;
            }
        }
        
        @media (max-width: 480px) {
            .p-6 {
                padding: 0.75rem;
            }
            
            .text-lg.font-medium {
                font-size: 1rem;
            }
            
            .w-8.h-8 {
                width: 2rem;
                height: 2rem;
            }
            
            .text-xl.font-semibold {
                font-size: 1.125rem;
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
                    <a href="jobs.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
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
                    <a href="profile.php" class="flex items-center px-4 py-2 text-white bg-blue-600 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Profile
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Flash Messages -->
            <?php if ($success_message): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Profile Settings</h2>
                <p class="text-gray-600">Manage your account information and preferences.</p>
            </div>

            <!-- Profile Form -->
            <div class="bg-white rounded-lg card-shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Personal Information</h3>
                </div>
                
                <form method="POST" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="first_name" 
                                name="first_name" 
                                value="<?php echo htmlspecialchars($user['first_name']); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="last_name" 
                                name="last_name" 
                                value="<?php echo htmlspecialchars($user['last_name']); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($user['email']); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number
                            </label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="hourly_rate" class="block text-sm font-medium text-gray-700 mb-2">
                                Hourly Rate
                            </label>
                            <input 
                                type="number" 
                                id="hourly_rate" 
                                name="hourly_rate" 
                                value="<?php echo htmlspecialchars($user['hourly_rate'] ?? 0); ?>"
                                min="0" 
                                step="0.01"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>
                        
                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                                Currency
                            </label>
                            <select 
                                id="currency" 
                                name="currency" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <?php foreach ($currencies as $code => $name): ?>
                                    <option value="<?php echo $code; ?>" <?php echo ($user['currency'] ?? 'NGN') === $code ? 'selected' : ''; ?>>
                                        <?php echo $name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">
                            Bio
                        </label>
                        <textarea 
                            id="bio" 
                            name="bio" 
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Tell clients about your experience and expertise..."
                        ><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-4">
                            Skills (Select all that apply)
                        </label>
                        <p class="text-sm text-gray-500 mb-4">
                            These skills are based on JLM's service offerings. Select the ones you specialize in.
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($jlm_services as $service_key => $service_name): ?>
                                <label class="flex items-center space-x-3 cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                    <input 
                                        type="checkbox" 
                                        name="skills[]" 
                                        value="<?php echo $service_key; ?>"
                                        class="checkbox-custom"
                                        <?php echo in_array($service_key, $user_skills) ? 'checked' : ''; ?>
                                    >
                                    <span class="text-sm text-gray-700 font-medium"><?php echo $service_name; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button 
                            type="submit" 
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Account Information -->
            <div class="mt-8 bg-white rounded-lg card-shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Account Information</h3>
                </div>
                
                <div class="p-6 space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700">Member Since</span>
                        <span class="text-sm text-gray-900"><?php echo format_date($user['created_at']); ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700">Last Updated</span>
                        <span class="text-sm text-gray-900"><?php echo format_date($user['updated_at'] ?? $user['created_at']); ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700">Account Status</span>
                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($user_skills)): ?>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Selected Services</span>
                            <div class="flex flex-wrap gap-1">
                                <?php foreach ($user_skills as $skill_key): ?>
                                    <?php if (isset($jlm_services[$skill_key])): ?>
                                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                            <?php echo $jlm_services[$skill_key]; ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Portfolio Section -->
                    <?php
                    // Get user's portfolio uploads
                    $portfolio_uploads = [];
                    if (!empty($user_skills)) {
                        $skill_keys = implode("','", $user_skills);
                        $stmt = $pdo->prepare("
                            SELECT * FROM jogigs_portfolio_uploads 
                            WHERE user_id = ? AND skill_key IN ('$skill_keys') AND status = 'active'
                            ORDER BY skill_key, upload_date DESC
                        ");
                        $stmt->execute([$user['id']]);
                        $portfolio_uploads = $stmt->fetchAll();
                    }
                    ?>
                    
                    <?php if (!empty($portfolio_uploads)): ?>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Portfolio Files</span>
                            <div class="flex flex-wrap gap-1">
                                <?php 
                                $skill_files = [];
                                foreach ($portfolio_uploads as $upload) {
                                    if (!isset($skill_files[$upload['skill_key']])) {
                                        $skill_files[$upload['skill_key']] = [];
                                    }
                                    $skill_files[$upload['skill_key']][] = $upload;
                                }
                                ?>
                                <?php foreach ($skill_files as $skill_key => $files): ?>
                                    <?php if (isset($jlm_services[$skill_key])): ?>
                                        <div class="flex flex-col">
                                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full mb-1">
                                                <?php echo $jlm_services[$skill_key]; ?> (<?php echo count($files); ?> files)
                                            </span>
                                            <?php foreach ($files as $file): ?>
                                                <a href="<?php echo htmlspecialchars($file['file_path']); ?>" 
                                                   target="_blank" 
                                                   class="text-xs text-blue-600 hover:text-blue-800 ml-2">
                                                    <?php echo htmlspecialchars($file['file_name']); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const mobileMenuIconOpen = document.getElementById('mobile-menu-icon-open');
            const mobileMenuIconClose = document.getElementById('mobile-menu-icon-close');
            const sidebar = document.getElementById('sidebar');

            function toggleMobileMenu() {
                mobileMenuIconOpen.classList.toggle('hidden');
                mobileMenuIconClose.classList.toggle('hidden');
                
                // Toggle sidebar on mobile only
                if (window.innerWidth < 768) {
                    sidebar.classList.toggle('hidden');
                }
            }

            mobileMenuToggle.addEventListener('click', toggleMobileMenu);

            // Close sidebar when a link is clicked (mobile only)
            document.querySelectorAll('#sidebar nav a').forEach(link => {
                link.addEventListener('click', () => {
                    // Hide sidebar on mobile when navigating
                    if (window.innerWidth < 768) {
                        sidebar.classList.add('hidden');
                        mobileMenuIconOpen.classList.remove('hidden');
                        mobileMenuIconClose.classList.add('hidden');
                    }
                });
            });
        });
    </script>
</body>
</html>
