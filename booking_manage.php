<?php
session_start();
require_once __DIR__ . '/config/config.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// Get admin user ID from email
$admin_email = $_SESSION['admin_email'] ?? '';
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND role = "admin" LIMIT 1');
$stmt->execute([$admin_email]);
$admin_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin_user) {
    header('Location: login.php');
    exit;
}

$admin_id = $admin_user['id'];
$toast = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $booking_id = (int)$_POST['booking_id'];
        
        try {
            switch ($_POST['action']) {
                case 'mark_confirmed':
                    $stmt = $pdo->prepare('UPDATE booking_submissions SET status = "confirmed", confirmed_at = NOW(), confirmed_by = ? WHERE id = ?');
                    $stmt->execute([$admin_id, $booking_id]);
                    $toast = 'Booking marked as confirmed.';
                    break;
                    
                case 'mark_completed':
                    $stmt = $pdo->prepare('UPDATE booking_submissions SET status = "completed", completed_at = NOW(), completed_by = ? WHERE id = ?');
                    $stmt->execute([$admin_id, $booking_id]);
                    $toast = 'Booking marked as completed.';
                    break;
                    
                case 'mark_cancelled':
                    $stmt = $pdo->prepare('UPDATE booking_submissions SET status = "cancelled", cancelled_at = NOW(), cancelled_by = ? WHERE id = ?');
                    $stmt->execute([$admin_id, $booking_id]);
                    $toast = 'Booking marked as cancelled.';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare('DELETE FROM booking_submissions WHERE id = ?');
                    $stmt->execute([$booking_id]);
                    $toast = 'Booking deleted.';
                    break;
                    
                case 'update_priority':
                    $priority = $_POST['priority'];
                    $stmt = $pdo->prepare('UPDATE booking_submissions SET priority = ? WHERE id = ?');
                    $stmt->execute([$priority, $booking_id]);
                    $toast = 'Priority updated.';
                    break;
                    
                case 'add_notes':
                    $notes = $_POST['notes'];
                    $stmt = $pdo->prepare('UPDATE booking_submissions SET admin_notes = ? WHERE id = ?');
                    $stmt->execute([$notes, $booking_id]);
                    $toast = 'Notes added.';
                    break;
                    
                default:
                    $toast = 'Invalid action.';
                    break;
            }
            
            $_SESSION['toast'] = $toast;
            
            // Redirect back to the same page with current filters
            $redirect_url = $_SERVER['REQUEST_URI'];
            if (strpos($redirect_url, '?') === false) {
                $redirect_url .= '?';
            } else {
                $redirect_url .= '&';
            }
            $redirect_url .= 'action=success';
            
            header('Location: ' . $redirect_url);
            exit;
            
        } catch (Exception $e) {
            // Log the error
            error_log('Booking action error: ' . $e->getMessage());
            $_SESSION['toast'] = 'Error: ' . $e->getMessage();
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

$toast = $_SESSION['toast'] ?? '';
unset($_SESSION['toast']);

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$priority_filter = $_GET['priority'] ?? 'all';
$date_filter = $_GET['date'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR service LIKE ? OR project_description LIKE ? OR ticket_number LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($priority_filter !== 'all') {
    $where_conditions[] = "priority = ?";
    $params[] = $priority_filter;
}

if ($date_filter !== 'all') {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(submitted_at) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_conditions[] = "submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get sorting
$order_by = $_GET['order'] ?? 'submitted_at DESC';

// Get bookings
$query = "SELECT * FROM booking_submissions $where_clause ORDER BY $order_by";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_bookings = $pdo->query('SELECT COUNT(*) FROM booking_submissions')->fetchColumn();
$pending_bookings = $pdo->query("SELECT COUNT(*) FROM booking_submissions WHERE status = 'pending'")->fetchColumn();
$confirmed_bookings = $pdo->query("SELECT COUNT(*) FROM booking_submissions WHERE status = 'confirmed'")->fetchColumn();
$completed_bookings = $pdo->query("SELECT COUNT(*) FROM booking_submissions WHERE status = 'completed'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Management | JLM Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center py-4">
            <div class="flex items-center space-x-3">
                <img src="assets/images/jlm.png" alt="JLM Logo" class="h-10 w-auto rounded-lg">
                <span class="text-xl font-bold text-gray-900">Booking Management</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="admin_dashboard.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Toast Message -->
        <?php if ($toast): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($toast); ?></span>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-calendar text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Bookings</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $total_bookings; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pending</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $pending_bookings; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-check text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Confirmed</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $confirmed_bookings; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-flag-checkered text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Completed</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $completed_bookings; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="get" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search bookings..." 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                    <select name="priority" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all" <?php echo $priority_filter === 'all' ? 'selected' : ''; ?>>All Priority</option>
                        <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="urgent" <?php echo $priority_filter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                    <select name="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                        <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>This Month</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Bookings Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Booking Requests (<?php echo count($bookings); ?>)</h3>
                    <a href="?export=bookings" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-download mr-2"></i>Export CSV
                    </a>
                </div>
            </div>

            <?php if (!empty($bookings)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($bookings as $booking): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['email']); ?></div>
                                    <?php if ($booking['phone']): ?>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['phone']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($booking['ticket_number']): ?>
                                <div class="text-sm font-mono text-blue-600 font-medium"><?php echo htmlspecialchars($booking['ticket_number']); ?></div>
                                <?php else: ?>
                                <div class="text-sm text-gray-400">No ticket</div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($booking['service']); ?></div>
                                <?php if (isset($booking['project_description']) && $booking['project_description']): ?>
                                <div class="text-sm text-gray-500 max-w-xs truncate" title="<?php echo htmlspecialchars($booking['project_description']); ?>">
                                    <?php echo htmlspecialchars(mb_strimwidth($booking['project_description'], 0, 50, '...')); ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_colors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'confirmed' => 'bg-green-100 text-green-800',
                                    'completed' => 'bg-blue-100 text-blue-800',
                                    'cancelled' => 'bg-red-100 text-red-800'
                                ];
                                $status_color = $status_colors[$booking['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_color; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $priority_colors = [
                                    'low' => 'bg-gray-100 text-gray-800',
                                    'medium' => 'bg-blue-100 text-blue-800',
                                    'high' => 'bg-orange-100 text-orange-800',
                                    'urgent' => 'bg-red-100 text-red-800'
                                ];
                                $priority = $booking['priority'] ?? 'medium';
                                $priority_color = $priority_colors[$priority] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $priority_color; ?>">
                                    <?php echo ucfirst($priority); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y g:i A', strtotime($booking['submitted_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button onclick="viewBooking(<?php echo $booking['id']; ?>)" 
                                            class="bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition-colors text-xs font-medium" title="View Details">
                                        <i class="fas fa-eye mr-1"></i>View Details
                                    </button>
                                    <?php if ($booking['status'] === 'pending'): ?>
                                    <button onclick="confirmBooking(<?php echo $booking['id']; ?>)" 
                                            class="bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 transition-colors text-xs font-medium" title="Confirm Booking">
                                        <i class="fas fa-check mr-1"></i>Confirm
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($booking['status'] === 'confirmed'): ?>
                                    <button onclick="completeBooking(<?php echo $booking['id']; ?>)" 
                                            class="bg-purple-600 text-white px-3 py-1.5 rounded-lg hover:bg-purple-700 transition-colors text-xs font-medium" title="Mark as Completed">
                                        <i class="fas fa-flag-checkered mr-1"></i>Complete
                                    </button>
                                    <?php endif; ?>
                                    <button onclick="deleteBooking(<?php echo $booking['id']; ?>)" 
                                            class="bg-red-600 text-white px-3 py-1.5 rounded-lg hover:bg-red-700 transition-colors text-xs font-medium" title="Delete Booking">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                    <?php if (!$booking['job_created'] && $booking['auto_job_enabled']): ?>
                                        <button onclick="createJobFromBooking(<?php echo $booking['id']; ?>)" 
                                                class="bg-orange-600 text-white px-3 py-1.5 rounded-lg hover:bg-orange-700 transition-colors text-xs font-medium" title="Create Job">
                                            <i class="fas fa-briefcase mr-1"></i>Create Job
                                        </button>
                                    <?php elseif ($booking['job_created']): ?>
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium" title="Job Created">
                                            <i class="fas fa-check mr-1"></i>Job Created
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-16">
                <div class="max-w-md mx-auto">
                    <div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-calendar text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No Bookings Found</h3>
                    <p class="text-gray-600">
                        <?php echo $search || $status_filter !== 'all' || $priority_filter !== 'all' || $date_filter !== 'all' 
                            ? 'Try adjusting your filters to see more results.' 
                            : 'No booking requests have been submitted yet.'; ?>
                    </p>
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

    <script>
        function viewBooking(bookingId) {
            // Show loading state
            document.getElementById('bookingDetails').innerHTML = `
                <div class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-3 text-gray-600">Loading booking details...</span>
                </div>
            `;
            document.getElementById('bookingModal').classList.remove('hidden');

            // Fetch booking details via AJAX
            fetch(`ajax/get_booking_details.php?id=${bookingId}`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
                .then(response => response.json())
                .then(data => {
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
                    console.error('Error:', error);
                    document.getElementById('bookingDetails').innerHTML = `
                        <div class="text-center py-8">
                            <div class="text-red-600 mb-4">
                                <i class="fas fa-exclamation-triangle text-2xl"></i>
                            </div>
                            <p class="text-gray-600">Failed to load booking details. Please try again.</p>
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
                            <div><span class="font-medium">Phone:</span> <a href="tel:${booking.phone}" class="text-blue-600 hover:underline">${booking.phone}</a></div>
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
                            <div><span class="font-medium">Submitted:</span> ${booking.submitted_at_formatted}</div>
                        </div>
                    </div>
                </div>

                <!-- Project Description -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3">Project Description</h4>
                    <p class="text-gray-700 whitespace-pre-wrap">${booking.project_description || 'No description provided'}</p>
                </div>

                <!-- Admin Notes -->
                ${booking.admin_notes ? `
                <div class="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-400">
                    <h4 class="font-semibold text-gray-900 mb-3">Admin Notes</h4>
                    <p class="text-gray-700 whitespace-pre-wrap">${booking.admin_notes}</p>
                </div>
                ` : ''}

                <!-- Status History -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3">Status History</h4>
                    <div class="space-y-2">
                        <div><span class="font-medium">Submitted:</span> ${booking.submitted_at_formatted}</div>
                        ${booking.confirmed_at ? `<div><span class="font-medium">Confirmed:</span> ${booking.confirmed_at_formatted} by ${booking.confirmed_by_name || 'Unknown'}</div>` : ''}
                        ${booking.completed_at ? `<div><span class="font-medium">Completed:</span> ${booking.completed_at_formatted} by ${booking.completed_by_name || 'Unknown'}</div>` : ''}
                        ${booking.cancelled_at ? `<div><span class="font-medium">Cancelled:</span> ${booking.cancelled_at_formatted} by ${booking.cancelled_by_name || 'Unknown'}</div>` : ''}
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-2 pt-4 border-t">
                    ${booking.status === 'pending' ? `
                        <button onclick="confirmBooking(${booking.id})" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm font-medium">
                            <i class="fas fa-check mr-1"></i> Confirm Booking
                        </button>
                    ` : ''}
                    ${booking.status === 'confirmed' ? `
                        <button onclick="completeBooking(${booking.id})" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                            <i class="fas fa-check-double mr-1"></i> Mark Complete
                        </button>
                    ` : ''}
                    ${booking.status !== 'cancelled' ? `
                        <button onclick="cancelBooking(${booking.id})" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded text-sm font-medium">
                            <i class="fas fa-times mr-1"></i> Cancel Booking
                        </button>
                    ` : ''}
                    <button onclick="deleteBooking(${booking.id})" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm font-medium">
                        <i class="fas fa-trash mr-1"></i> Delete
                    </button>
                </div>
            `;
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').classList.add('hidden');
        }

        function confirmBooking(bookingId) {
            if (confirm('Are you sure you want to confirm this booking?')) {
                submitBookingAction('mark_confirmed', bookingId);
            }
        }

        function completeBooking(bookingId) {
            if (confirm('Are you sure you want to mark this booking as completed?')) {
                submitBookingAction('mark_completed', bookingId);
            }
        }

        function cancelBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this booking?')) {
                submitBookingAction('mark_cancelled', bookingId);
            }
        }

        function deleteBooking(bookingId) {
            if (confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
                submitBookingAction('delete', bookingId);
            }
        }

        function submitBookingAction(action, bookingId) {
            // Create form data
            const formData = new FormData();
            formData.append('action', action);
            formData.append('booking_id', bookingId);

            // Submit using fetch
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                if (response.ok) {
                    // Reload the page to show updated data
                    window.location.reload();
                } else {
                    throw new Error('Network response was not ok');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        function createJobFromBooking(bookingId) {
            if (confirm('Create a job from this booking? This will automatically generate a job posting for freelancers.')) {
                fetch('jogigs/integration/create_job_from_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        booking_id: bookingId,
                        admin_id: <?php echo $_SESSION['admin_id'] ?? 1; ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Job created successfully! Job ID: ' + data.job_id);
                        location.reload();
                    } else {
                        alert('Error creating job: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error creating job. Please try again.');
                });
            }
        }

        // Close modal when clicking outside
        document.getElementById('bookingModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeBookingModal();
            }
        });
    </script>
</body>
</html> 