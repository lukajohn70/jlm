<?php
// JLM Freelancer Web App - Registration
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

// Define JLM services (same as profile page)
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

// Handle registration form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $data = [
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'skills' => isset($_POST['skills']) ? implode(',', $_POST['skills']) : '',
        'hourly_rate' => floatval($_POST['hourly_rate'] ?? 0),
        'bio' => trim($_POST['bio'] ?? '')
    ];
    
    // Validate passwords match
    if ($data['password'] !== $data['confirm_password']) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($data['password']) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } else {
        $result = register_user($data);
        if ($result['success']) {
            // Handle portfolio uploads if user registration was successful
            if (isset($_POST['skills']) && is_array($_POST['skills'])) {
                $user_id = $result['user_id'];
                $uploaded_files = [];
                
                foreach ($_POST['skills'] as $skill_key) {
                    if (isset($_FILES["portfolio_{$skill_key}"]) && $_FILES["portfolio_{$skill_key}"]['error'] === UPLOAD_ERR_OK) {
                        $files = $_FILES["portfolio_{$skill_key}"];
                        
                        // Handle multiple files
                        $file_count = is_array($files['name']) ? count($files['name']) : 1;
                        
                        for ($i = 0; $i < $file_count; $i++) {
                            $file_name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                            $file_tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                            $file_size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
                            $file_type = is_array($files['type']) ? $files['type'][$i] : $files['type'];
                            
                            // Validate file size (10MB max)
                            if ($file_size > 10 * 1024 * 1024) {
                                continue; // Skip files larger than 10MB
                            }
                            
                            // Create upload directory if it doesn't exist
                            $upload_dir = "uploads/portfolio/{$user_id}/{$skill_key}";
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            // Generate unique filename
                            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                            $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
                            $file_path = $upload_dir . '/' . $unique_filename;
                            
                            // Move uploaded file
                            if (move_uploaded_file($file_tmp, $file_path)) {
                                // Save to database
                                try {
                                    $stmt = $pdo->prepare("
                                        INSERT INTO jogigs_portfolio_uploads (user_id, skill_key, file_name, file_path, file_size, file_type)
                                        VALUES (?, ?, ?, ?, ?, ?)
                                    ");
                                    $stmt->execute([$user_id, $skill_key, $file_name, $file_path, $file_size, $file_type]);
                                    $uploaded_files[] = $file_name;
                                } catch (Exception $e) {
                                    error_log("Failed to save portfolio upload: " . $e->getMessage());
                                }
                            }
                        }
                    }
                }
                
                if (!empty($uploaded_files)) {
                    $success_message = 'Registration successful! You can now log in. Portfolio files uploaded: ' . implode(', ', $uploaded_files);
                } else {
                    $success_message = 'Registration successful! You can now log in.';
                }
            } else {
                $success_message = 'Registration successful! You can now log in.';
            }
        } else {
            $error_message = $result['message'];
        }
    }
}

$page_title = 'Register - JoGigs';
$page_description = 'Create your JoGigs account to start earning.';
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
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            }
            
            .gradient-bg {
                padding: 1rem;
            }
            
            .w-full.max-w-2xl {
                max-width: 100%;
                margin: 0;
            }
            
            .bg-white.rounded-2xl.card-shadow.p-8 {
                padding: 1.5rem;
                border-radius: 1rem;
            }
            
            .grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.gap-4 {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .flex.items-center.space-x-3.cursor-pointer.p-3.border.border-gray-200.rounded-lg {
                padding: 0.75rem;
                margin-bottom: 0.5rem;
            }
            
            .text-3xl.font-bold {
                font-size: 1.875rem;
            }
            
            .text-xl {
                font-size: 1.125rem;
            }
        }
        
        @media (max-width: 480px) {
            .bg-white.rounded-2xl.card-shadow.p-8 {
                padding: 1rem;
            }
            
            .text-3xl.font-bold {
                font-size: 1.5rem;
            }
            
            .w-16.h-16 {
                width: 3rem;
                height: 3rem;
            }
            
            .text-2xl.font-bold {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body class="min-h-screen gradient-bg flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
        <!-- Registration Card -->
        <div class="bg-white rounded-2xl card-shadow p-8">
            <!-- Logo and Title -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-white text-2xl font-bold">JLM</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Join JoGigs</h1>
                <p class="text-gray-600">Create your account to start earning</p>
            </div>

            <!-- Error/Success Messages -->
            <?php if (!empty($error_message)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <?php echo htmlspecialchars($success_message); ?>
                    <br><a href="index.php" class="text-green-800 underline">Click here to log in</a>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form method="POST" action="" class="space-y-6" enctype="multipart/form-data">
                <!-- Name Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                            First Name *
                        </label>
                        <input 
                            type="text" 
                            id="first_name" 
                            name="first_name" 
                            value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg input-focus focus:outline-none transition-colors"
                            placeholder="Enter your first name"
                            required
                        >
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Last Name *
                        </label>
                        <input 
                            type="text" 
                            id="last_name" 
                            name="last_name" 
                            value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg input-focus focus:outline-none transition-colors"
                            placeholder="Enter your last name"
                            required
                        >
                    </div>
                </div>

                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address *
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg input-focus focus:outline-none transition-colors"
                        placeholder="Enter your email"
                        required
                    >
                </div>

                <!-- Phone Field -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Phone Number
                    </label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg input-focus focus:outline-none transition-colors"
                        placeholder="Enter your phone number"
                    >
                </div>

                <!-- Skills Field - Updated to use checkboxes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Skills *
                    </label>
                    <p class="text-sm text-gray-500 mb-4">
                        Select the skills you have that match JLM's service offerings. You'll be able to upload portfolio samples for each selected skill.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php 
                        $selected_skills = [];
                        if (isset($_POST['skills']) && is_array($_POST['skills'])) {
                            $selected_skills = $_POST['skills'];
                        }
                        ?>
                        <?php foreach ($jlm_services as $service_key => $service_name): ?>
                            <label class="flex items-center space-x-3 cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                <input 
                                    type="checkbox" 
                                    name="skills[]" 
                                    value="<?php echo $service_key; ?>"
                                    class="checkbox-custom"
                                    <?php echo in_array($service_key, $selected_skills) ? 'checked' : ''; ?>
                                    onchange="togglePortfolioUpload('<?php echo $service_key; ?>')"
                                >
                                <span class="text-sm text-gray-700 font-medium"><?php echo $service_name; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Portfolio Upload Section -->
                <div id="portfolio-section" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Portfolio Samples
                    </label>
                    <p class="text-sm text-gray-500 mb-4">
                        Upload samples of your work for the selected skills (optional but recommended)
                    </p>
                    <div id="portfolio-uploads" class="space-y-4">
                        <!-- Portfolio upload fields will be dynamically added here -->
                    </div>
                </div>

                <!-- Hourly Rate Field -->
                <div>
                    <label for="hourly_rate" class="block text-sm font-medium text-gray-700 mb-2">
                        Hourly Rate (₦)
                    </label>
                    <input 
                        type="number" 
                        id="hourly_rate" 
                        name="hourly_rate" 
                        value="<?php echo htmlspecialchars($_POST['hourly_rate'] ?? ''); ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg input-focus focus:outline-none transition-colors"
                        placeholder="0.00"
                        min="0"
                        step="0.01"
                    >
                </div>

                <!-- Bio Field -->
                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">
                        Bio
                    </label>
                    <textarea 
                        id="bio" 
                        name="bio" 
                        rows="3"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg input-focus focus:outline-none transition-colors"
                        placeholder="Tell us about yourself and your experience..."
                    ><?php echo htmlspecialchars($_POST['bio'] ?? ''); ?></textarea>
                </div>

                <!-- Password Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password *
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg input-focus focus:outline-none transition-colors"
                            placeholder="Enter your password"
                            required
                        >
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirm Password *
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg input-focus focus:outline-none transition-colors"
                            placeholder="Confirm your password"
                            required
                        >
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="flex items-center">
                    <input type="checkbox" name="agree_terms" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" required>
                    <label class="ml-2 text-sm text-gray-600">
                        I agree to the 
                        <a href="terms.php" class="text-blue-600 hover:text-blue-500">Terms of Service</a> 
                        and 
                        <a href="privacy.php" class="text-blue-600 hover:text-blue-500">Privacy Policy</a>
                    </label>
                </div>

                <!-- Register Button -->
                <button 
                    type="submit" 
                    name="register"
                    class="w-full btn-primary text-white font-semibold py-3 px-4 rounded-lg"
                >
                    Create Account
                </button>
            </form>

            <!-- Divider -->
            <div class="my-6 flex items-center">
                <div class="flex-1 border-t border-gray-300"></div>
                <span class="px-4 text-sm text-gray-500">or</span>
                <div class="flex-1 border-t border-gray-300"></div>
            </div>

            <!-- Login Link -->
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="index.php" class="text-blue-600 hover:text-blue-500 font-medium">
                        Sign in here
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
            button.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Creating account...';
            button.disabled = true;
        });

        // Auto-focus first name field
        document.getElementById('first_name').focus();

        // Function to toggle portfolio upload section
        function togglePortfolioUpload(skillKey) {
            const portfolioSection = document.getElementById('portfolio-section');
            const portfolioUploads = document.getElementById('portfolio-uploads');
            const checkbox = document.querySelector(`input[name="skills[]"][value="${skillKey}"]`);
            const skillName = checkbox.closest('label').querySelector('span').textContent;

            if (checkbox.checked) {
                // Show portfolio section if not already visible
                portfolioSection.classList.remove('hidden');
                
                // Check if portfolio upload for this skill already exists
                const existingUpload = document.getElementById(`portfolio-div-${skillKey}`);
                if (!existingUpload) {
                    // Create new portfolio upload field
                    const portfolioDiv = document.createElement('div');
                    portfolioDiv.id = `portfolio-div-${skillKey}`;
                    portfolioDiv.className = 'border border-gray-200 rounded-lg p-4 bg-gray-50';
                    portfolioDiv.innerHTML = `
                        <div class="flex flex-col space-y-2">
                            <label for="portfolio-${skillKey}" class="block text-sm font-medium text-gray-700">
                                Portfolio for ${skillName}
                            </label>
                            <input 
                                type="file" 
                                name="portfolio_${skillKey}[]" 
                                id="portfolio-${skillKey}" 
                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-white focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" 
                                multiple
                                accept="image/*,.pdf,.doc,.docx,.mp4,.mov,.avi"
                            >
                            <p class="text-xs text-gray-500">Accepted formats: Images, PDF, Word docs, Videos (max 10MB each)</p>
                        </div>
                    `;
                    portfolioUploads.appendChild(portfolioDiv);
                }
            } else {
                // Remove portfolio upload for this skill
                const portfolioDiv = document.getElementById(`portfolio-div-${skillKey}`);
                if (portfolioDiv) {
                    portfolioDiv.remove();
                }
                
                // Hide portfolio section if no skills are selected
                const checkedSkills = document.querySelectorAll('input[name="skills[]"]:checked');
                if (checkedSkills.length === 0) {
                    portfolioSection.classList.add('hidden');
                }
            }
        }

        // Initialize portfolio section on page load
        document.addEventListener('DOMContentLoaded', function() {
            const checkedSkills = document.querySelectorAll('input[name="skills[]"]:checked');
            if (checkedSkills.length > 0) {
                document.getElementById('portfolio-section').classList.remove('hidden');
                checkedSkills.forEach(checkbox => {
                    togglePortfolioUpload(checkbox.value);
                });
            }
        });
    </script>
</body>
</html> 