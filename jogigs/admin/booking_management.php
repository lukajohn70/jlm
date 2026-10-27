<?php
// JLM Freelancer Web App - Admin Booking Management
session_start();

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require admin authentication
require_auth();
$user = get_current_jogigs_user();

// Check if user is admin
if ($user['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
$success = '';
$action = $_GET['action'] ?? 'list';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_type = $_POST['action_type'] ?? '';
    
    switch ($action_type) {
        case 'create_job_from_booking':
            $booking_id = (int)$_POST['booking_id'];
            
            try {
                $result = create_job_from_booking($booking_id, $user['id']);
                if ($result['success']) {
                    $success = 'Job created successfully! Job ID: ' . $result['job_id'];
                } else {
                    $error = $result['message'];
                }
            } catch (Exception $e) {
                $error = 'Failed to create job: ' . $e->getMessage();
            }
            break;
            
        case 'update_booking_status':
            $booking_id = (int)$_POST['booking_id'];
            $new_status = $_POST['new_status'];
            $admin_notes = trim($_POST['admin_notes'] ?? '');
            
            try {
                $stmt = $pdo->prepare("UPDATE booking_submissions SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $booking_id]);
                
                // Log the action
                $stmt = $pdo->prepare("
                    INSERT INTO booking_approval_workflow (booking_id, admin_id, action, notes)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$booking_id, $user['id'], $new_status, $admin_notes]);
                
                $success = 'Booking status updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update booking status: ' . $e->getMessage();
            }
            break;
    }
}

// Get data based on action
switch ($action) {
    case 'list':
        $stmt = $pdo->prepare("
            SELECT bs.*, 
                   CASE 
                       WHEN bs.job_created = 1 THEN 'Job Created'
                       WHEN bs.status = 'confirmed' THEN 'Confirmed'
                       WHEN bs.status = 'cancelled' THEN 'Cancelled'
                       ELSE 'Pending'
                   END as display_status
            FROM booking_submissions bs
            ORDER BY bs.submitted_at DESC
        ");
        $stmt->execute();
        $bookings = $stmt->fetchAll();
        break;
        
    case 'workflow':
        $booking_id = (int)$_GET['booking_id'];
        $stmt = $pdo->prepare("
            SELECT baw.*, u.first_name, u.last_name
            FROM booking_approval_workflow baw
            JOIN users u ON baw.admin_id = u.id
            WHERE baw.booking_id = ?
            ORDER BY baw.created_at DESC
        ");
        $stmt->execute([$booking_id]);
        $workflow = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("SELECT * FROM booking_submissions WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();
        break;
}

$page_title = 'Booking Management - Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
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
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-gradient-to-r from-red-500 to-pink-600 rounded-lg flex items-center justify-center">
                            <span class="text-white text-sm font-bold">A</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <h1 class="text-xl font-semibold text-gray-900">JoGigs Admin</h1>
                    </div>
                </div>
                
                <!-- Desktop User Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-red-300 rounded-full flex items-center justify-center">
                            <span class="text-red-600 text-sm font-medium">A</span>
                        </div>
                        <span class="text-sm text-gray-700">Admin User</span>
                    </div>
                    <a href="../logout.php" class="text-sm text-gray-500 hover:text-gray-700">Logout</a>
                </div>

                <!-- Mobile Menu Button -->
                <button id="mobile-menu-toggle" class="md:hidden p-2 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors duration-200">
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
                    <a href="index.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                        </svg>
                        Dashboard
                    </a>
                    <a href="job_management.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                        </svg>
                        Job Management
                    </a>
                    <a href="booking_management.php" class="flex items-center px-4 py-2 text-white bg-red-600 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Booking Management
                    </a>
                    <a href="proposal_management.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Proposal Management
                    </a>
                    <a href="quotes.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Quotes
                    </a>
                    <a href="jobs.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                        </svg>
                        Jobs
                    </a>
                    <a href="freelancers.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Freelancers
                    </a>
                    <a href="earnings.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                        Earnings
                    </a>
                    <a href="../dashboard.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to JoGigs
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Booking Management</h1>
            <p class="text-gray-600 mt-2">Manage booking submissions and create jobs</p>
        </div>

        <!-- Navigation Tabs -->
        <div class="mb-6">
            <nav class="flex space-x-8">
                <a href="?action=list" class="<?php echo $action === 'list' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> py-2 px-1 border-b-2 font-medium text-sm">
                    All Bookings
                </a>
            </nav>
        </div>

        <!-- Flash Messages -->
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
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-800"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Content based on action -->
        <?php if ($action === 'list'): ?>
            <!-- Bookings List -->
            <div class="bg-white rounded-lg card-shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">All Bookings</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date/Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['email']); ?></div>
                                            <?php if ($booking['phone']): ?>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['phone']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($booking['service']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php if ($booking['datetime']): ?>
                                                <?php echo date('M j, Y g:i A', strtotime($booking['datetime'])); ?>
                                            <?php else: ?>
                                                <span class="text-gray-400">Not specified</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Submitted: <?php echo date('M j, Y', strtotime($booking['submitted_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $booking['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                                   ($booking['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 
                                                   'bg-yellow-100 text-yellow-800'); ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($booking['job_created']): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                ✓ Created
                                            </span>
                                            <?php if ($booking['job_id']): ?>
                                                <div class="text-xs text-gray-500">Job #<?php echo $booking['job_id']; ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="viewBookingDetails(<?php echo $booking['id']; ?>)" 
                                                    class="bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition-colors text-xs font-medium">
                                                <i class="fas fa-eye mr-1"></i>View Details
                                            </button>
                                            <?php if (!$booking['job_created'] && $booking['auto_job_enabled']): ?>
                                                <button onclick="createJobFromBooking(<?php echo $booking['id']; ?>)" 
                                                        class="bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 transition-colors text-xs font-medium">
                                                    <i class="fas fa-briefcase mr-1"></i>Create Job
                                                </button>
                                            <?php endif; ?>
                                            <button onclick="updateBookingStatus(<?php echo $booking['id']; ?>)" 
                                                    class="bg-purple-600 text-white px-3 py-1.5 rounded-lg hover:bg-purple-700 transition-colors text-xs font-medium">
                                                <i class="fas fa-check mr-1"></i>Update Status
                                            </button>
                                            <a href="?action=workflow&booking_id=<?php echo $booking['id']; ?>" 
                                               class="bg-orange-600 text-white px-3 py-1.5 rounded-lg hover:bg-orange-700 transition-colors text-xs font-medium inline-block">
                                                <i class="fas fa-flag-checkered mr-1"></i>Workflow
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($action === 'workflow' && isset($booking)): ?>
            <!-- Booking Workflow -->
            <div class="bg-white rounded-lg card-shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-medium text-gray-900">Workflow for Booking #<?php echo $booking['id']; ?></h2>
                        <a href="?action=list" class="text-blue-600 hover:text-blue-900">← Back to Bookings</a>
                    </div>
                </div>
                
                <!-- Booking Details -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-md font-medium text-gray-900 mb-3">Booking Details</h3>
                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <strong>Client:</strong> <?php echo htmlspecialchars($booking['name']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($booking['email']); ?><br>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone'] ?? 'Not provided'); ?>
                        </div>
                        <div>
                            <strong>Service:</strong> <?php echo htmlspecialchars($booking['service']); ?><br>
                            <strong>Date/Time:</strong> <?php echo $booking['datetime'] ? date('M j, Y g:i A', strtotime($booking['datetime'])) : 'Not specified'; ?><br>
                            <strong>Status:</strong> <?php echo ucfirst($booking['status']); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Workflow History -->
                <div class="px-6 py-4">
                    <h3 class="text-md font-medium text-gray-900 mb-3">Workflow History</h3>
                    <?php if (empty($workflow)): ?>
                        <p class="text-gray-500">No workflow actions recorded yet.</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($workflow as $action_item): ?>
                                <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($action_item['first_name'] . ' ' . $action_item['last_name']); ?>
                                            </span>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $action_item['action'] === 'approve' ? 'bg-green-100 text-green-800' : 
                                                       ($action_item['action'] === 'reject' ? 'bg-red-100 text-red-800' : 
                                                       'bg-blue-100 text-blue-800'); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $action_item['action'])); ?>
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo date('M j, Y g:i A', strtotime($action_item['created_at'])); ?>
                                        </div>
                                        <?php if ($action_item['notes']): ?>
                                            <div class="text-sm text-gray-700 mt-1">
                                                <strong>Notes:</strong> <?php echo htmlspecialchars($action_item['notes']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div id="bookingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Booking Details</h3>
                    <button onclick="closeBookingModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="bookingDetails" class="space-y-4">
                    <!-- Booking details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu JavaScript -->
    <script>
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const mobileMenuIconOpen = document.getElementById('mobile-menu-icon-open');
        const mobileMenuIconClose = document.getElementById('mobile-menu-icon-close');

        if (mobileMenuToggle && sidebar) {
            mobileMenuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('hidden');
                mobileMenuIconOpen.classList.toggle('hidden');
                mobileMenuIconClose.classList.toggle('hidden');
            });
        }

        // Close modal when clicking outside
        document.getElementById('bookingModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeBookingModal();
            }
        });
    </script>

    <!-- JavaScript -->
    <script>
        function createJobFromBooking(bookingId) {
            if (confirm('Create a job from this booking? This will automatically generate a job posting for freelancers.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action_type" value="create_job_from_booking">
                    <input type="hidden" name="booking_id" value="${bookingId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function updateBookingStatus(bookingId) {
            const newStatus = prompt('Enter new status (pending, confirmed, cancelled, completed):');
            if (newStatus) {
                const adminNotes = prompt('Enter admin notes (optional):');
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action_type" value="update_booking_status">
                    <input type="hidden" name="booking_id" value="${bookingId}">
                    <input type="hidden" name="new_status" value="${newStatus}">
                    <input type="hidden" name="admin_notes" value="${adminNotes || ''}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function viewBookingDetails(bookingId) {
            // Show loading state
            document.getElementById('bookingDetails').innerHTML = `
                <div class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-3 text-gray-600">Loading booking details...</span>
                </div>
            `;
            document.getElementById('bookingModal').classList.remove('hidden');

            // Fetch booking details via AJAX
            console.log('Fetching booking details for ID:', bookingId);
            console.log('Current URL:', window.location.href);
            console.log('Session cookies:', document.cookie);
            
            fetch(`../ajax/get_booking_details.php?id=${bookingId}`, {
                method: 'GET',
                credentials: 'include', // Changed from 'same-origin' to 'include'
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        displayBookingDetails(data.booking);
                    } else {
                        document.getElementById('bookingDetails').innerHTML = `
                            <div class="text-center py-8">
                                <div class="text-red-600 mb-4">
                                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                                </div>
                                <p class="text-gray-600">Error: ${data.error || 'Failed to load booking details'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    document.getElementById('bookingDetails').innerHTML = `
                        <div class="text-center py-8">
                            <div class="text-red-600 mb-4">
                                <i class="fas fa-exclamation-triangle text-2xl"></i>
                            </div>
                            <p class="text-gray-600">Failed to load booking details. Please try again.</p>
                            <p class="text-sm text-gray-500 mt-2">Error: ${error.message}</p>
                        </div>
                    `;
                });
        }

        function displayBookingDetails(booking) {
            const statusColors = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'confirmed': 'bg-green-100 text-green-800',
                'completed': 'bg-blue-100 text-blue-800',
                'cancelled': 'bg-red-100 text-red-800'
            };

            const priorityColors = {
                'low': 'bg-gray-100 text-gray-800',
                'medium': 'bg-blue-100 text-blue-800',
                'high': 'bg-orange-100 text-orange-800',
                'urgent': 'bg-red-100 text-red-800'
            };

            const statusColor = statusColors[booking.status || 'pending'] || 'bg-gray-100 text-gray-800';
            const priorityColor = priorityColors[booking.priority || 'medium'] || 'bg-gray-100 text-gray-800';

            document.getElementById('bookingDetails').innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Client Information -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-900 mb-3">Client Information</h4>
                        <div class="space-y-2">
                            <div><span class="font-medium">Name:</span> ${booking.name}</div>
                            <div><span class="font-medium">Email:</span> <a href="mailto:${booking.email}" class="text-blue-600 hover:underline">${booking.email}</a></div>
                            <div><span class="font-medium">Phone:</span> <a href="tel:${booking.phone}" class="text-blue-600 hover:underline">${booking.phone || 'Not provided'}</a></div>
                            <div><span class="font-medium">Ticket:</span> <span class="font-mono text-blue-600">${booking.ticket_number || 'No ticket'}</span></div>
                        </div>
                    </div>

                    <!-- Booking Information -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-900 mb-3">Booking Information</h4>
                        <div class="space-y-2">
                            <div><span class="font-medium">Service:</span> ${booking.service}</div>
                            <div><span class="font-medium">Status:</span> <span class="px-2 py-1 text-xs font-medium rounded-full ${statusColor}">${(booking.status || 'pending').charAt(0).toUpperCase() + (booking.status || 'pending').slice(1)}</span></div>
                            <div><span class="font-medium">Priority:</span> <span class="px-2 py-1 text-xs font-medium rounded-full ${priorityColor}">${(booking.priority || 'medium').charAt(0).toUpperCase() + (booking.priority || 'medium').slice(1)}</span></div>
                            <div><span class="font-medium">Submitted:</span> ${booking.submitted_at_formatted || 'Not available'}</div>
                            ${booking.datetime ? `<div><span class="font-medium">Consultation Date:</span> ${booking.datetime_formatted || booking.datetime}</div>` : ''}
                        </div>
                    </div>
                </div>

                <!-- Project Description -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3">Project Description</h4>
                    <p class="text-gray-700 whitespace-pre-wrap">${booking.project_description || 'No description provided'}</p>
                </div>

                <!-- Additional Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3">Additional Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        ${booking.consultation_method ? `<div><span class="font-medium">Consultation Method:</span> ${booking.consultation_method}</div>` : ''}
                        ${booking.budget_range ? `<div><span class="font-medium">Budget Range:</span> ${booking.budget_range}</div>` : ''}
                        ${booking.timeline ? `<div><span class="font-medium">Timeline:</span> ${booking.timeline}</div>` : ''}
                        ${booking.admin_notes ? `<div class="md:col-span-2"><span class="font-medium">Admin Notes:</span> ${booking.admin_notes}</div>` : ''}
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-2 pt-4 border-t">
                    ${booking.status === 'pending' ? `
                        <button onclick="updateBookingStatusFromModal(${booking.id}, 'confirmed')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm font-medium">
                            <i class="fas fa-check mr-1"></i> Confirm Booking
                        </button>
                    ` : ''}
                    ${booking.status === 'confirmed' ? `
                        <button onclick="updateBookingStatusFromModal(${booking.id}, 'completed')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                            <i class="fas fa-check-double mr-1"></i> Mark Complete
                        </button>
                    ` : ''}
                    ${booking.status !== 'cancelled' ? `
                        <button onclick="updateBookingStatusFromModal(${booking.id}, 'cancelled')" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded text-sm font-medium">
                            <i class="fas fa-times mr-1"></i> Cancel Booking
                        </button>
                    ` : ''}
                    ${!booking.job_created && booking.auto_job_enabled ? `
                        <button onclick="createJobFromBooking(${booking.id})" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm font-medium">
                            <i class="fas fa-briefcase mr-1"></i> Create Job
                        </button>
                    ` : ''}
                </div>
            `;
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').classList.add('hidden');
        }

        function updateBookingStatusFromModal(bookingId, newStatus) {
            const adminNotes = prompt('Enter admin notes (optional):');
            if (newStatus) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action_type" value="update_booking_status">
                    <input type="hidden" name="booking_id" value="${bookingId}">
                    <input type="hidden" name="new_status" value="${newStatus}">
                    <input type="hidden" name="admin_notes" value="${adminNotes || ''}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
