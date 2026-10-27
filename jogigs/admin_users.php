<?php
// JLM Freelancer Web App - Admin Users Management
session_start();

// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require authentication
require_auth();

// Get current user
$user = get_current_jogigs_user();

// Check if user is admin
if ($user['email'] !== 'admin@jogigs.com') {
    header('Location: dashboard.php');
    exit;
}

// Handle user status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status' && isset($_POST['user_id']) && isset($_POST['status'])) {
        $user_id = (int)$_POST['user_id'];
        $status = $_POST['status'];
        
        if (update_user_status($user_id, $status)) {
            $success_message = "User status updated successfully.";
            log_activity($user['id'], 'admin_update_user_status', "Updated user $user_id status to $status");
        } else {
            $error_message = "Failed to update user status.";
        }
    }
}

// Get all users
$users = get_all_users_for_admin(100);

$page_title = 'Manage Users - JoGigs Admin';
$page_description = 'Administrative user management for JoGigs platform.';
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
        .admin-gradient {
            background: linear-gradient(135deg, #1e40af 0%, #3730a3 100%);
        }
        .admin-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
        }
        .admin-sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        }
        .card-shadow {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Admin Header -->
    <header class="admin-gradient text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-3">
                        <h1 class="text-xl font-bold">JoGigs Admin</h1>
                        <p class="text-sm text-blue-100">User Management</p>
                    </div>
                </div>
                
                <!-- Admin User Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <div class="flex items-center space-x-2 bg-white/10 rounded-lg px-3 py-2">
                        <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                            <span class="text-white text-sm font-bold">A</span>
                        </div>
                        <span class="text-sm font-medium"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                        <span class="text-xs bg-red-500 text-white px-2 py-1 rounded-full">ADMIN</span>
                    </div>
                    <a href="logout.php" class="text-sm text-blue-100 hover:text-white transition-colors">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- Admin Sidebar -->
        <div class="w-64 admin-sidebar min-h-screen hidden md:block">
            <div class="p-4">
                <nav class="space-y-2">
                    <a href="admin_dashboard.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Dashboard
                    </a>
                    <a href="admin_users.php" class="flex items-center px-4 py-3 text-white bg-red-600 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        Manage Users
                    </a>
                    <a href="admin/job_management.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                        </svg>
                        Manage Jobs
                    </a>
                    <a href="admin/jobs.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Job Assignments
                    </a>
                    <a href="admin/earnings.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                        Platform Earnings
                    </a>
                    <a href="admin/booking_management.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Booking Management
                    </a>
                    <a href="admin/proposal_management.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Proposal Management
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header -->
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Manage Users</h2>
                    <p class="text-gray-600">Manage all registered users on the JoGigs platform.</p>
                </div>

                <!-- Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex">
                            <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-green-800"><?php echo htmlspecialchars($success_message); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-red-800"><?php echo htmlspecialchars($error_message); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Users Table -->
                <div class="admin-card rounded-xl card-shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">All Users (<?php echo count($users); ?>)</h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignments</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No users found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user_data): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                                        <span class="text-gray-600 text-sm font-medium">
                                                            <?php echo strtoupper(substr($user_data['first_name'], 0, 1) . substr($user_data['last_name'], 0, 1)); ?>
                                                        </span>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            <?php echo htmlspecialchars($user_data['email']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($user_data['phone'] ?? 'N/A'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                    <?php echo $user_data['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                        ($user_data['status'] === 'suspended' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                                    <?php echo ucfirst($user_data['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="text-sm">
                                                    <span class="font-medium"><?php echo $user_data['total_assignments']; ?></span> total
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <span class="font-medium"><?php echo $user_data['completed_assignments']; ?></span> completed
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('M j, Y', strtotime($user_data['created_at'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <form method="POST" class="inline-block">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="user_id" value="<?php echo $user_data['id']; ?>">
                                                    <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded px-2 py-1">
                                                        <option value="active" <?php echo $user_data['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="inactive" <?php echo $user_data['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                        <option value="suspended" <?php echo $user_data['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                                    </select>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
