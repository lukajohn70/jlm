<?php
session_start();
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/config/config.php';
require_admin_auth();

// Get user ID from URL parameter
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$user_id) {
    header('Location: admin_dashboard.php');
    exit;
}

// Initialize variables
$user = null;
$user_activities = [];
$user_stats = [];
$error_message = '';
$success_message = '';

try {
    // Fetch user details from main users table
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Try jogigs_users table if not found in main users table
        $stmt = $pdo->prepare('SELECT * FROM jogigs_users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$user) {
        $error_message = 'User not found.';
    } else {
        // Get user activities and statistics
        $user_activities = getUserActivities($pdo, $user_id);
        $user_stats = getUserStats($pdo, $user_id);
    }
    
} catch (Exception $e) {
    $error_message = 'Error loading user details: ' . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    try {
        if (isset($_POST['update_user'])) {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = trim($_POST['role'] ?? '');
            $status = trim($_POST['status'] ?? '');
            
            if ($name && $email && $role) {
                // Determine which table to update based on where user was found
                $table = isset($user['first_name']) ? 'jogigs_users' : 'users';
                
                if ($table === 'jogigs_users') {
                    $stmt = $pdo->prepare('UPDATE jogigs_users SET first_name = ?, last_name = ?, email = ?, status = ?, updated_at = NOW() WHERE id = ?');
                    $name_parts = explode(' ', $name, 2);
                    $first_name = $name_parts[0];
                    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
                    $stmt->execute([$first_name, $last_name, $email, $status, $user_id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ?, updated_at = NOW() WHERE id = ?');
                    $stmt->execute([$name, $email, $role, $user_id]);
                }
                
                $success_message = 'User updated successfully!';
                // Refresh user data
                $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    $stmt = $pdo->prepare('SELECT * FROM jogigs_users WHERE id = ?');
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            } else {
                $error_message = 'Please fill in all required fields.';
            }
        }
        
        if (isset($_POST['reset_password'])) {
            $new_password = trim($_POST['new_password'] ?? '');
            if ($new_password && strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $table = isset($user['first_name']) ? 'jogigs_users' : 'users';
                $stmt = $pdo->prepare("UPDATE $table SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                $success_message = 'Password reset successfully!';
            } else {
                $error_message = 'Password must be at least 6 characters long.';
            }
        }
        
    } catch (Exception $e) {
        $error_message = 'Error updating user: ' . $e->getMessage();
    }
}

// Helper function to get user activities
function getUserActivities($pdo, $user_id) {
    $activities = [];
    
    try {
        // Get contact submissions by this user
        $stmt = $pdo->prepare('SELECT "contact" as type, name, email, subject, submitted_at as created_at FROM contact_submissions WHERE email = (SELECT email FROM users WHERE id = ?) ORDER BY submitted_at DESC LIMIT 5');
        $stmt->execute([$user_id]);
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $activities = array_merge($activities, $contacts);
        
        // Get booking submissions by this user
        $stmt = $pdo->prepare('SELECT "booking" as type, name, email, service as subject, submitted_at as created_at FROM booking_submissions WHERE email = (SELECT email FROM users WHERE id = ?) ORDER BY submitted_at DESC LIMIT 5');
        $stmt->execute([$user_id]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $activities = array_merge($activities, $bookings);
        
        // Get testimonials by this user
        $stmt = $pdo->prepare('SELECT "testimonial" as type, name, email, text as subject, created_at FROM testimonials WHERE email = (SELECT email FROM users WHERE id = ?) ORDER BY created_at DESC LIMIT 5');
        $stmt->execute([$user_id]);
        $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $activities = array_merge($activities, $testimonials);
        
        // Sort by date
        usort($activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
    } catch (Exception $e) {
        // Handle error silently
    }
    
    return array_slice($activities, 0, 10); // Return top 10 activities
}

// Helper function to get user statistics
function getUserStats($pdo, $user_id) {
    $stats = [];
    
    try {
        // Get user email first
        $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user_email = $stmt->fetchColumn();
        
        if (!$user_email) {
            $stmt = $pdo->prepare('SELECT email FROM jogigs_users WHERE id = ?');
            $stmt->execute([$user_id]);
            $user_email = $stmt->fetchColumn();
        }
        
        if ($user_email) {
            // Count contact submissions
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM contact_submissions WHERE email = ?');
            $stmt->execute([$user_email]);
            $stats['contacts'] = $stmt->fetchColumn();
            
            // Count booking submissions
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM booking_submissions WHERE email = ?');
            $stmt->execute([$user_email]);
            $stats['bookings'] = $stmt->fetchColumn();
            
            // Count testimonials
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM testimonials WHERE email = ?');
            $stmt->execute([$user_email]);
            $stats['testimonials'] = $stmt->fetchColumn();
            
            // Count email subscriptions
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM email_subscribers WHERE email = ?');
            $stmt->execute([$user_email]);
            $stats['subscriptions'] = $stmt->fetchColumn();
        }
        
    } catch (Exception $e) {
        // Handle error silently
    }
    
    return $stats;
}

$admin_email = $_SESSION['admin_email'] ?? 'support@jlm.com.ng';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Details | JLM Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center py-4">
            <div class="flex items-center space-x-3">
                <img src="assets/images/jlm.png" alt="JLM Logo" class="h-10 w-auto rounded-lg">
                <span class="text-xl font-bold text-gray-900">User Details</span>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600 hidden sm:inline">Welcome, <?php echo htmlspecialchars($admin_email); ?></span>
                <a href="admin_dashboard.php" class="px-4 py-2 border border-blue-500 text-blue-600 rounded-lg font-semibold hover:bg-blue-50 transition">Back to Dashboard</a>
                <a href="logout.php" class="px-4 py-2 border border-pink-500 text-pink-600 rounded-lg font-semibold hover:bg-pink-50 transition">Sign Out</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($error_message): ?>
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($user): ?>
        <!-- User Information Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                        <?php 
                        $name = isset($user['first_name']) ? $user['first_name'] . ' ' . $user['last_name'] : $user['name'];
                        echo strtoupper(substr($name, 0, 2)); 
                        ?>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($name); ?></h1>
                        <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                        <div class="flex items-center space-x-2 mt-1">
                            <?php if (isset($user['role'])): ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                            <?php endif; ?>
                            <?php if (isset($user['status'])): ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="text-right text-sm text-gray-500">
                    <p>Member since: <?php echo date('M j, Y', strtotime($user['created_at'] ?? $user['created_at'])); ?></p>
                    <p>Last updated: <?php echo date('M j, Y g:i A', strtotime($user['updated_at'] ?? $user['created_at'])); ?></p>
                </div>
            </div>

            <!-- User Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-blue-600">Contact Submissions</p>
                            <p class="text-2xl font-bold text-blue-900"><?php echo $user_stats['contacts'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-green-600">Booking Requests</p>
                            <p class="text-2xl font-bold text-green-900"><?php echo $user_stats['bookings'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-yellow-600 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-yellow-600">Testimonials</p>
                            <p class="text-2xl font-bold text-yellow-900"><?php echo $user_stats['testimonials'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-purple-600 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-purple-600">Email Subscriptions</p>
                            <p class="text-2xl font-bold text-purple-900"><?php echo $user_stats['subscriptions'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Details and Edit Form -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- User Details -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">User Information</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($name); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email Address</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <?php if (isset($user['phone'])): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['phone']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($user['role'])): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo ucfirst($user['role']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($user['status'])): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo ucfirst($user['status']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($user['skills'])): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Skills</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['skills']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($user['bio'])): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bio</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['bio']); ?></p>
                    </div>
                    <?php endif; ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Account Created</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Last Updated</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($user['updated_at'] ?? $user['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Edit User Form -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Edit User</h2>
                <form method="POST" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    
                    <?php if (isset($user['role'])): ?>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                        <select id="role" name="role" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="freelancer" <?php echo $user['role'] === 'freelancer' ? 'selected' : ''; ?>>Freelancer</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($user['status'])): ?>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="status" name="status" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" name="update_user" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                        Update User
                    </button>
                </form>

                <!-- Password Reset Form -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Reset Password</h3>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" 
                                   placeholder="Enter new password" required>
                        </div>
                        
                        <button type="submit" name="reset_password" class="w-full bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition">
                            Reset Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- User Activities -->
        <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Recent Activities</h2>
            <?php if (!empty($user_activities)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-gray-600 font-semibold">
                            <th class="py-2 px-2 text-left">Type</th>
                            <th class="py-2 px-2 text-left">Subject</th>
                            <th class="py-2 px-2 text-left">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_activities as $activity): ?>
                        <tr class="border-b last:border-0">
                            <td class="py-2 px-2">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    <?php echo $activity['type'] === 'contact' ? 'bg-blue-100 text-blue-800' : 
                                              ($activity['type'] === 'booking' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                    <?php echo ucfirst($activity['type']); ?>
                                </span>
                            </td>
                            <td class="py-2 px-2"><?php echo htmlspecialchars($activity['subject'] ?? 'N/A'); ?></td>
                            <td class="py-2 px-2"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p>No recent activities found for this user.</p>
            </div>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <!-- User Not Found -->
        <div class="bg-white rounded-xl shadow-lg p-8 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">User Not Found</h2>
            <p class="text-gray-600 mb-6">The user you're looking for doesn't exist or has been removed.</p>
            <a href="admin_dashboard.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Dashboard
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Back to Top Button -->
    <button id="backToTopBtn" class="fixed bottom-8 right-8 z-50 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white p-3 rounded-full shadow-lg hover:scale-110 transition-all duration-300 hidden" aria-label="Back to Top">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
        </svg>
    </button>

    <script>
        const backToTopBtn = document.getElementById('backToTopBtn');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTopBtn.classList.remove('hidden');
            } else {
                backToTopBtn.classList.add('hidden');
            }
        });
        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>
</body>
</html>
