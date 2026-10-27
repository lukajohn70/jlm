<?php
session_start();
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/config/config.php';
$admin_user = require_admin_auth();

$admin_id = $admin_user['id'];
$toast = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $booking_id = (int)$_POST['booking_id'];
        
        try {
            switch ($_POST['action']) {
                case 'mark_confirmed':
                    $stmt = $pdo->prepare('UPDATE booking_submissions SET status = "confirmed", updated_at = NOW() WHERE id = ?');
                    $stmt->execute([$booking_id]);
                    $toast = 'Booking marked as confirmed.';
                    break;
                    
                case 'mark_completed':
                    $stmt = $pdo->prepare('UPDATE booking_submissions SET status = "completed", updated_at = NOW() WHERE id = ?');
                    $stmt->execute([$booking_id]);
                    $toast = 'Booking marked as completed.';
                    break;
                    
                case 'mark_cancelled':
                    $stmt = $pdo->prepare('UPDATE booking_submissions SET status = "cancelled", updated_at = NOW() WHERE id = ?');
                    $stmt->execute([$booking_id]);
                    $toast = 'Booking marked as cancelled.';
                    break;
                    
                case 'reply':
                    $subject = $_POST['reply_subject'];
                    $message = $_POST['reply_message'];
                    
                    // Get booking details
                    $stmt = $pdo->prepare('SELECT * FROM booking_submissions WHERE id = ?');
                    $stmt->execute([$booking_id]);
                    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($booking) {
                        // Send email reply
                        require_once __DIR__ . '/config/email_utils.php';
                        $emailResult = sendBookingReply($booking['email'], $booking['name'], $subject, $message, $booking['ticket_number'] ?? '');
                        
                        if ($emailResult) {
                            // Update booking status
                            $stmt = $pdo->prepare('UPDATE booking_submissions SET status = "replied", updated_at = NOW() WHERE id = ?');
                            $stmt->execute([$booking_id]);
                            
                            $toast = 'Reply sent successfully.';
                        } else {
                            $toast = 'Failed to send reply.';
                        }
                    }
                    break;
                    
                case 'archive':
                    $stmt = $pdo->prepare('UPDATE booking_submissions SET status = "archived", updated_at = NOW() WHERE id = ?');
                    $stmt->execute([$booking_id]);
                    $toast = 'Booking archived successfully.';
                    break;
                    
                case 'unarchive':
                    $stmt = $pdo->prepare('UPDATE booking_submissions SET status = "pending", updated_at = NOW() WHERE id = ?');
                    $stmt->execute([$booking_id]);
                    $toast = 'Booking unarchived successfully.';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare('DELETE FROM booking_submissions WHERE id = ?');
                    $stmt->execute([$booking_id]);
                    $toast = 'Booking deleted successfully.';
                    break;
                    
                default:
                    $toast = 'Invalid action.';
                    break;
            }
            
            $_SESSION['toast'] = $toast;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            
        } catch (Exception $e) {
            $toast = 'Error: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR service LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get bookings
$sql = "SELECT * FROM booking_submissions $where_clause ORDER BY submitted_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_bookings = $pdo->query('SELECT COUNT(*) FROM booking_submissions')->fetchColumn();
$pending_bookings = $pdo->query('SELECT COUNT(*) FROM booking_submissions WHERE status = "pending"')->fetchColumn();
$confirmed_bookings = $pdo->query('SELECT COUNT(*) FROM booking_submissions WHERE status = "confirmed"')->fetchColumn();
$completed_bookings = $pdo->query('SELECT COUNT(*) FROM booking_submissions WHERE status = "completed"')->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - JLM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-lg">JLM</span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-2xl font-bold text-gray-900">Booking Management</h1>
                        <p class="text-sm text-gray-600">Manage all booking requests</p>
                    </div>
            </div>
            <div class="flex items-center space-x-4">
                    <a href="admin_dashboard.php" class="text-gray-600 hover:text-gray-900 flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Toast Message -->
        <?php if (isset($_SESSION['toast'])): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            <?php echo htmlspecialchars($_SESSION['toast']); unset($_SESSION['toast']); ?>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar text-blue-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Bookings</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $total_bookings; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pending</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $pending_bookings; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Confirmed</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $confirmed_bookings; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-flag-checkered text-purple-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Completed</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $completed_bookings; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-64">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search bookings..." 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="min-w-48">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="replied" <?php echo $status_filter === 'replied' ? 'selected' : ''; ?>>Replied</option>
                        <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
                
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
                
                <?php if ($search || $status_filter !== 'all'): ?>
                <a href="booking_manage.php" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors flex items-center">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Bookings List -->
        <div class="space-y-6">
            <?php if (!empty($bookings)): ?>
                        <?php foreach ($bookings as $booking): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <!-- Header Row -->
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($booking['name'] ?? ''); ?></h3>
                            <div class="text-sm text-gray-600 mb-1"><?php echo htmlspecialchars($booking['email'] ?? ''); ?></div>
                            <?php if (!empty($booking['phone'] ?? '')): ?>
                            <div class="text-sm text-gray-600"><?php echo htmlspecialchars($booking['phone'] ?? ''); ?></div>
                                    <?php endif; ?>
                                </div>
                        <div class="flex items-center space-x-3">
                                <?php
                                $status_colors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'confirmed' => 'bg-green-100 text-green-800',
                                    'completed' => 'bg-blue-100 text-blue-800',
                                'cancelled' => 'bg-red-100 text-red-800',
                                'replied' => 'bg-purple-100 text-purple-800',
                                'archived' => 'bg-gray-100 text-gray-800'
                                ];
                            $status_color = $status_colors[$booking['status'] ?? 'pending'] ?? 'bg-gray-100 text-gray-800';
                                ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $status_color; ?>">
                                <?php echo ucfirst($booking['status'] ?? 'pending'); ?>
                                </span>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-1">Service</h4>
                            <p class="text-sm text-gray-900"><?php echo htmlspecialchars($booking['service'] ?? ''); ?></p>
                            <?php if (!empty($booking['message'] ?? '')): ?>
                            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(mb_strimwidth($booking['message'] ?? '', 0, 150, '...')); ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-1">Ticket & Date</h4>
                            <?php if (!empty($booking['ticket_number'] ?? '')): ?>
                            <p class="text-sm font-mono text-blue-600 font-medium mb-1"><?php echo htmlspecialchars($booking['ticket_number'] ?? ''); ?></p>
                            <?php endif; ?>
                            <p class="text-sm text-gray-600"><?php echo date('M j, Y g:i A', strtotime($booking['submitted_at'] ?? 'now')); ?></p>
                        </div>
                    </div>

                    <!-- Actions Row -->
                    <div class="border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Actions</h4>
                        <div class="flex flex-wrap gap-2">
                            <!-- Primary Actions -->
                            <button onclick="viewBookingDetails(<?php echo $booking['id']; ?>)" 
                                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium flex items-center shadow-sm">
                                <i class="fas fa-eye mr-2"></i>View Details
                            </button>
                            
                            <button onclick="replyToBooking(<?php echo $booking['id']; ?>)" 
                                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium flex items-center shadow-sm">
                                <i class="fas fa-reply mr-2"></i>Reply
                                    </button>
                            
                            <!-- Status Actions -->
                            <?php if (($booking['status'] ?? 'pending') === 'pending'): ?>
                                    <button onclick="confirmBooking(<?php echo $booking['id']; ?>)" 
                                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm font-medium flex items-center shadow-sm">
                                <i class="fas fa-check mr-2"></i>Confirm
                                    </button>
                                    <?php endif; ?>
                            
                            <?php if (($booking['status'] ?? 'pending') === 'confirmed'): ?>
                                    <button onclick="completeBooking(<?php echo $booking['id']; ?>)" 
                                    class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium flex items-center shadow-sm">
                                <i class="fas fa-flag-checkered mr-2"></i>Complete
                                    </button>
                                    <?php endif; ?>
                            
                            <!-- Archive Actions -->
                            <?php if (($booking['status'] ?? 'pending') !== 'archived'): ?>
                            <button onclick="archiveBooking(<?php echo $booking['id']; ?>)" 
                                    class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors text-sm font-medium flex items-center shadow-sm">
                                <i class="fas fa-archive mr-2"></i>Archive
                                    </button>
                            <?php else: ?>
                            <button onclick="unarchiveBooking(<?php echo $booking['id']; ?>)" 
                                    class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors text-sm font-medium flex items-center shadow-sm">
                                <i class="fas fa-archive mr-2"></i>Unarchive
                                        </button>
                                    <?php endif; ?>
                            
                            <!-- Danger Actions -->
                            <button onclick="deleteBooking(<?php echo $booking['id']; ?>)" 
                                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors text-sm font-medium flex items-center shadow-sm">
                                <i class="fas fa-trash mr-2"></i>Delete
                            </button>
                        </div>
                    </div>
                                </div>
                        <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-calendar-times text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No bookings found</h3>
                    <p class="text-gray-600 mb-4">
                        <?php echo $search || $status_filter !== 'all' 
                            ? 'Try adjusting your filters to see more results.' 
                            : 'No booking requests have been submitted yet.'; ?>
                    </p>
                    <?php if ($search || $status_filter !== 'all'): ?>
                    <a href="booking_manage.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Clear Filters
                    </a>
                    <?php endif; ?>
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
                    <!-- Booking details will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div id="replyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Send Reply</h3>
                    <button onclick="closeReplyModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form id="replyForm" method="POST">
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="booking_id" id="replyBookingId">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                        <input type="text" name="reply_subject" id="replySubject" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Re: Your Booking Request">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                        <textarea name="reply_message" id="replyMessage" rows="6"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Enter your reply message here..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeReplyModal()" 
                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-paper-plane mr-2"></i>Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // View booking details
        function viewBookingDetails(bookingId) {
            // Fetch booking details via AJAX
            fetch(`get_booking_details.php?id=${bookingId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayBookingDetails(data.booking);
                    } else {
                        alert('Error loading booking details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Fallback: show basic info
                    document.getElementById('bookingDetails').innerHTML = `
                        <div class="space-y-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h4 class="font-medium text-blue-900 mb-2">Booking ID: ${bookingId}</h4>
                                <p class="text-blue-700">Loading full details...</p>
                            </div>
                        </div>
                    `;
                    document.getElementById('bookingModal').classList.remove('hidden');
                });
        }

        // Display booking details in modal
        function displayBookingDetails(booking) {
            const details = `
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Client Information</h4>
                            <div class="space-y-1 text-sm">
                                <p><strong>Name:</strong> ${booking.name || 'N/A'}</p>
                                <p><strong>Email:</strong> ${booking.email || 'N/A'}</p>
                                <p><strong>Phone:</strong> ${booking.phone || 'N/A'}</p>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Booking Information</h4>
                            <div class="space-y-1 text-sm">
                                <p><strong>Service:</strong> ${booking.service || 'N/A'}</p>
                                <p><strong>Status:</strong> <span class="px-2 py-1 rounded-full text-xs ${getStatusColor(booking.status)}">${booking.status || 'pending'}</span></p>
                                <p><strong>Submitted:</strong> ${booking.submitted_at ? new Date(booking.submitted_at).toLocaleString() : 'N/A'}</p>
                            </div>
                        </div>
                    </div>

                    ${booking.message ? `
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">Message</h4>
                        <div class="bg-gray-50 p-3 rounded-lg text-sm">
                            ${booking.message}
                        </div>
                </div>
                ` : ''}

                    ${booking.ticket_number ? `
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">Ticket Number</h4>
                        <p class="font-mono text-blue-600">${booking.ticket_number}</p>
                    </div>
                    ` : ''}
                </div>
            `;
            
            document.getElementById('bookingDetails').innerHTML = details;
            document.getElementById('bookingModal').classList.remove('hidden');
        }

        // Get status color class
        function getStatusColor(status) {
            const colors = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'confirmed': 'bg-green-100 text-green-800',
                'completed': 'bg-blue-100 text-blue-800',
                'cancelled': 'bg-red-100 text-red-800',
                'replied': 'bg-purple-100 text-purple-800',
                'archived': 'bg-gray-100 text-gray-800'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        }

        // Reply to booking
        function replyToBooking(bookingId) {
            document.getElementById('replyBookingId').value = bookingId;
            document.getElementById('replySubject').value = 'Re: Your Booking Request';
            document.getElementById('replyMessage').value = '';
            document.getElementById('replyModal').classList.remove('hidden');
        }

        // Archive booking
        function archiveBooking(bookingId) {
            if (confirm('Are you sure you want to archive this booking? It will be moved to archived status.')) {
                submitBookingAction('archive', bookingId);
            }
        }

        // Unarchive booking
        function unarchiveBooking(bookingId) {
            if (confirm('Are you sure you want to unarchive this booking? It will be moved back to pending status.')) {
                submitBookingAction('unarchive', bookingId);
            }
        }

        // Confirm booking
        function confirmBooking(bookingId) {
            if (confirm('Are you sure you want to confirm this booking? This will notify the customer.')) {
                submitBookingAction('mark_confirmed', bookingId);
            }
        }

        // Complete booking
        function completeBooking(bookingId) {
            if (confirm('Are you sure you want to mark this booking as completed? This indicates the project is finished.')) {
                submitBookingAction('mark_completed', bookingId);
            }
        }

        // Delete booking
        function deleteBooking(bookingId) {
            if (confirm('⚠️ WARNING: Are you sure you want to permanently delete this booking?\n\nThis action cannot be undone and will remove all booking data.')) {
                if (confirm('This is your final warning. Click OK to permanently delete this booking.')) {
                submitBookingAction('delete', bookingId);
                }
            }
        }

        // Submit booking action
        function submitBookingAction(action, bookingId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="${action}">
                <input type="hidden" name="booking_id" value="${bookingId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Close modals
        function closeBookingModal() {
            document.getElementById('bookingModal').classList.add('hidden');
        }

        function closeReplyModal() {
            document.getElementById('replyModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const bookingModal = document.getElementById('bookingModal');
            const replyModal = document.getElementById('replyModal');
            
            if (event.target === bookingModal) {
                closeBookingModal();
            }
            if (event.target === replyModal) {
                closeReplyModal();
            }
        }
    </script>
</body>
</html> 
