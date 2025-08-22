<?php
// JLM Freelancer Web App - Admin Quotes Management
session_start();

// Include configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is admin
if (!is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

$user = get_current_jogigs_user();
if ($user['email'] !== 'admin@jogigs.com') {
    header('Location: ../dashboard.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_quote':
                $booking_data = [
                    'booking_id' => $_POST['booking_id'],
                    'client_name' => $_POST['client_name'],
                    'client_email' => $_POST['client_email'],
                    'client_phone' => $_POST['client_phone'] ?? '',
                    'services' => $_POST['services'],
                    'project_description' => $_POST['project_description']
                ];
                
                $result = create_quote_from_booking($booking_data);
                if ($result['success']) {
                    set_flash_message('Quote created successfully!', 'success');
                } else {
                    set_flash_message('Failed to create quote: ' . $result['message'], 'error');
                }
                break;
                
            case 'accept_quote':
                $quote_id = $_POST['quote_id'];
                $result = accept_quote($quote_id);
                if ($result['success']) {
                    set_flash_message('Quote accepted! Job created and ready for assignment.', 'success');
                } else {
                    set_flash_message('Failed to accept quote: ' . $result['message'], 'error');
                }
                break;
                
            case 'assign_job':
                $job_id = $_POST['job_id'];
                $quote_id = $_POST['quote_id'];
                $assignment_type = $_POST['assignment_type'];
                
                if ($assignment_type === 'open_market') {
                    $result = assign_job_to_freelancers($job_id, $quote_id);
                } else {
                    $freelancer_id = $_POST['freelancer_id'];
                    $result = assign_job_directly($job_id, $quote_id, $freelancer_id);
                }
                
                if ($result['success']) {
                    set_flash_message('Job assigned successfully!', 'success');
                } else {
                    set_flash_message('Failed to assign job: ' . $result['message'], 'error');
                }
                break;
        }
        
        header('Location: quotes.php');
        exit;
    }
}

// Get quotes with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$quotes = get_quotes_with_pagination($limit, $offset);
$total_quotes = get_total_quotes_count();
$total_pages = ceil($total_quotes / $limit);

// Get available freelancers for direct assignment
$freelancers = get_all_freelancers();

$page_title = 'Quotes Management - JoGigs Admin';
$page_description = 'Manage quotes and job assignments.';
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
                    <a href="booking_management.php" class="flex items-center px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-700 rounded-lg">
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
                    <a href="quotes.php" class="flex items-center px-4 py-2 text-white bg-red-600 rounded-lg">
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
            <!-- Flash Messages -->
            <?php $flash = get_flash_message(); ?>
            <?php if ($flash): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Quotes Management</h2>
                        <p class="text-gray-600">Manage quotes and create job assignments.</p>
                    </div>
                    <button onclick="openCreateQuoteModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Create Quote
                    </button>
                </div>
            </div>

            <!-- Quotes Table -->
            <div class="bg-white rounded-lg card-shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">All Quotes</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quote #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($quotes)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No quotes found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($quotes as $quote): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($quote['quote_number']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($quote['client_name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($quote['client_email']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($quote['service_type']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo format_currency($quote['quote_amount'], $quote['currency']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo get_status_color($quote['status']); ?>">
                                                <?php echo ucfirst($quote['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo format_date($quote['created_at']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <?php if ($quote['status'] === 'pending'): ?>
                                                <button onclick="acceptQuote(<?php echo $quote['id']; ?>)" class="text-green-600 hover:text-green-900 mr-3">Accept</button>
                                                <button onclick="rejectQuote(<?php echo $quote['id']; ?>)" class="text-red-600 hover:text-red-900">Reject</button>
                                            <?php elseif ($quote['status'] === 'accepted'): ?>
                                                <button onclick="assignJob(<?php echo $quote['id']; ?>)" class="text-blue-600 hover:text-blue-900">Assign Job</button>
                                            <?php else: ?>
                                                <span class="text-gray-400">No actions</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="px-6 py-3 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_quotes); ?> of <?php echo $total_quotes; ?> results
                            </div>
                            <div class="flex space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-1 text-sm text-gray-500 hover:text-gray-700">Previous</a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?>" class="px-3 py-1 text-sm <?php echo $i === $page ? 'bg-blue-600 text-white' : 'text-gray-500 hover:text-gray-700'; ?> rounded">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-1 text-sm text-gray-500 hover:text-gray-700">Next</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Quote Modal -->
    <div id="createQuoteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Create Quote</h3>
                    <button onclick="closeCreateQuoteModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="create_quote">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Booking ID</label>
                            <input type="text" name="booking_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Client Name</label>
                            <input type="text" name="client_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Client Email</label>
                            <input type="email" name="client_email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Client Phone</label>
                            <input type="text" name="client_phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Services</label>
                            <input type="text" name="services" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., Web Development, Graphic Design">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Project Description</label>
                            <textarea name="project_description" required rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeCreateQuoteModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Create Quote
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Job Modal -->
    <div id="assignJobModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Assign Job</h3>
                    <button onclick="closeAssignJobModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="assign_job">
                    <input type="hidden" name="quote_id" id="assignQuoteId">
                    <input type="hidden" name="job_id" id="assignJobId">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assignment Type</label>
                            <select name="assignment_type" id="assignmentType" onchange="toggleFreelancerSelect()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="open_market">Open Market (All Freelancers)</option>
                                <option value="direct_assignment">Direct Assignment (Specific Freelancer)</option>
                            </select>
                        </div>
                        
                        <div id="freelancerSelect" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Freelancer</label>
                            <select name="freelancer_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <?php foreach ($freelancers as $freelancer): ?>
                                    <option value="<?php echo $freelancer['id']; ?>">
                                        <?php echo htmlspecialchars($freelancer['first_name'] . ' ' . $freelancer['last_name']); ?> (<?php echo htmlspecialchars($freelancer['skills']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeAssignJobModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Assign Job
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenuIconOpen = document.getElementById('mobile-menu-icon-open');
        const mobileMenuIconClose = document.getElementById('mobile-menu-icon-close');
        const sidebar = document.getElementById('sidebar');

        mobileMenuToggle.addEventListener('click', function() {
            mobileMenuIconOpen.classList.toggle('hidden');
            mobileMenuIconClose.classList.toggle('hidden');
            
            if (window.innerWidth < 768) {
                sidebar.classList.toggle('hidden');
            }
        });

        // Close sidebar when clicking on a link (mobile only)
        document.querySelectorAll('#sidebar nav a').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    sidebar.classList.add('hidden');
                    mobileMenuIconOpen.classList.remove('hidden');
                    mobileMenuIconClose.classList.add('hidden');
                }
            });
        });

        // Modal functions
        function openCreateQuoteModal() {
            document.getElementById('createQuoteModal').classList.remove('hidden');
        }

        function closeCreateQuoteModal() {
            document.getElementById('createQuoteModal').classList.add('hidden');
        }

        function closeAssignJobModal() {
            document.getElementById('assignJobModal').classList.add('hidden');
        }

        function acceptQuote(quoteId) {
            if (confirm('Are you sure you want to accept this quote? This will create a job.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="accept_quote">
                    <input type="hidden" name="quote_id" value="${quoteId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function rejectQuote(quoteId) {
            if (confirm('Are you sure you want to reject this quote?')) {
                // Implement reject functionality
                alert('Reject functionality to be implemented');
            }
        }

        function assignJob(quoteId) {
            document.getElementById('assignQuoteId').value = quoteId;
            document.getElementById('assignJobId').value = quoteId; // Assuming job ID is same as quote ID for now
            document.getElementById('assignJobModal').classList.remove('hidden');
        }

        function toggleFreelancerSelect() {
            const assignmentType = document.getElementById('assignmentType').value;
            const freelancerSelect = document.getElementById('freelancerSelect');
            
            if (assignmentType === 'direct_assignment') {
                freelancerSelect.classList.remove('hidden');
            } else {
                freelancerSelect.classList.add('hidden');
            }
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const createModal = document.getElementById('createQuoteModal');
            const assignModal = document.getElementById('assignJobModal');
            
            if (event.target === createModal) {
                closeCreateQuoteModal();
            }
            if (event.target === assignModal) {
                closeAssignJobModal();
            }
        });
    </script>
</body>
</html>

<?php
// Helper functions
function get_quotes_with_pagination($limit, $offset) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM jogigs_quotes ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function get_total_quotes_count() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jogigs_quotes");
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function get_all_freelancers() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, skills FROM jogigs_users WHERE status = 'active' ORDER BY first_name");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function get_status_color($status) {
    switch ($status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'accepted':
            return 'bg-green-100 text-green-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        case 'expired':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>


