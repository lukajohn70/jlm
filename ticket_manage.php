<?php
/**
 * Admin Ticket Management Page
 * Comprehensive ticket tracking and management system
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/ticket_utils.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$error = null;
$success = null;

// Handle ticket actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $ticketNumber = $_POST['ticket_number'];
        $newStatus = $_POST['new_status'];
        
        if (updateTicketStatus($ticketNumber, $newStatus, $userId)) {
            $success = "Ticket status updated successfully.";
        } else {
            $error = "Failed to update ticket status.";
        }
    } elseif (isset($_POST['assign_ticket'])) {
        $ticketNumber = $_POST['ticket_number'];
        $assignTo = $_POST['assign_to'];
        
        if (assignTicket($ticketNumber, $assignTo, $userId)) {
            $success = "Ticket assigned successfully.";
        } else {
            $error = "Failed to assign ticket.";
        }
    } elseif (isset($_POST['add_note'])) {
        $ticketNumber = $_POST['ticket_number'];
        $note = trim($_POST['note']);
        
        if (!empty($note)) {
            if (addTicketNote($ticketNumber, $note, $userId)) {
                $success = "Note added successfully.";
            } else {
                $error = "Failed to add note.";
            }
        } else {
            $error = "Note cannot be empty.";
        }
    }
}

// Get search filters
$filters = [];
if (isset($_GET['ticket_number']) && !empty($_GET['ticket_number'])) {
    $filters['ticket_number'] = $_GET['ticket_number'];
}
if (isset($_GET['form_type']) && !empty($_GET['form_type'])) {
    $filters['form_type'] = $_GET['form_type'];
}
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['priority']) && !empty($_GET['priority'])) {
    $filters['priority'] = $_GET['priority'];
}
if (isset($_GET['submitter_name']) && !empty($_GET['submitter_name'])) {
    $filters['submitter_name'] = $_GET['submitter_name'];
}

// Get tickets
$tickets = searchTickets($filters);
$stats = getTicketStats();

// Get users for assignment
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $userStmt = $pdo->query("SELECT id, name FROM users WHERE role = 'admin' ORDER BY name");
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    error_log("Error getting users: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management - JLM Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-open { background: #e3f2fd; color: #1976d2; }
        .status-in_progress { background: #fff3e0; color: #f57c00; }
        .status-resolved { background: #e8f5e8; color: #388e3c; }
        .status-closed { background: #f5f5f5; color: #616161; }
        .priority-badge {
            padding: 3px 6px;
            border-radius: 8px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .priority-low { background: #e8f5e8; color: #388e3c; }
        .priority-medium { background: #fff3e0; color: #f57c00; }
        .priority-high { background: #ffebee; color: #d32f2f; }
        .priority-urgent { background: #fce4ec; color: #c2185b; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-gray-900">Ticket Management</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="admin_dashboard.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                    <a href="?logout=1" class="px-4 py-2 border border-pink-500 text-pink-600 rounded-lg font-semibold hover:bg-pink-50 transition">Sign Out</a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Messages -->
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <p class="text-green-700"><?php echo htmlspecialchars($success); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <i class="fas fa-ticket-alt text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Tickets</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo array_sum(array_column($stats['by_status'] ?? [], 'count')); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <i class="fas fa-clock text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Open</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php 
                            $openCount = 0;
                            foreach ($stats['by_status'] ?? [] as $status) {
                                if ($status['status'] === 'open') {
                                    $openCount = $status['count'];
                                    break;
                                }
                            }
                            echo $openCount;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="flex items-center">
                    <div class="p-3 bg-orange-100 rounded-lg">
                        <i class="fas fa-spinner text-orange-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">In Progress</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php 
                            $inProgressCount = 0;
                            foreach ($stats['by_status'] ?? [] as $status) {
                                if ($status['status'] === 'in_progress') {
                                    $inProgressCount = $status['count'];
                                    break;
                                }
                            }
                            echo $inProgressCount;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i class="fas fa-check text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Resolved</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php 
                            $resolvedCount = 0;
                            foreach ($stats['by_status'] ?? [] as $status) {
                                if ($status['status'] === 'resolved') {
                                    $resolvedCount = $status['count'];
                                    break;
                                }
                            }
                            echo $resolvedCount;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white p-6 rounded-lg shadow-sm border mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Search & Filters</h2>
            <form method="get" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ticket Number</label>
                    <input type="text" name="ticket_number" value="<?php echo htmlspecialchars($_GET['ticket_number'] ?? ''); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Form Type</label>
                    <select name="form_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Types</option>
                        <option value="contact" <?php echo (isset($_GET['form_type']) && $_GET['form_type'] === 'contact') ? 'selected' : ''; ?>>Contact</option>
                        <option value="booking" <?php echo (isset($_GET['form_type']) && $_GET['form_type'] === 'booking') ? 'selected' : ''; ?>>Booking</option>
                        <option value="testimonial" <?php echo (isset($_GET['form_type']) && $_GET['form_type'] === 'testimonial') ? 'selected' : ''; ?>>Testimonial</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Status</option>
                        <option value="open" <?php echo (isset($_GET['status']) && $_GET['status'] === 'open') ? 'selected' : ''; ?>>Open</option>
                        <option value="in_progress" <?php echo (isset($_GET['status']) && $_GET['status'] === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo (isset($_GET['status']) && $_GET['status'] === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo (isset($_GET['status']) && $_GET['status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Priorities</option>
                        <option value="low" <?php echo (isset($_GET['priority']) && $_GET['priority'] === 'low') ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo (isset($_GET['priority']) && $_GET['priority'] === 'medium') ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo (isset($_GET['priority']) && $_GET['priority'] === 'high') ? 'selected' : ''; ?>>High</option>
                        <option value="urgent" <?php echo (isset($_GET['priority']) && $_GET['priority'] === 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Tickets Table -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Tickets (<?php echo count($tickets); ?>)</h2>
            </div>
            
            <?php if (empty($tickets)): ?>
                <div class="p-8 text-center">
                    <i class="fas fa-ticket-alt text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500">No tickets found matching your criteria.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitter</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($tickets as $ticket): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-mono font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900"><?php echo ucfirst($ticket['form_type']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($ticket['submitter_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($ticket['submitter_email']); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="status-badge status-<?php echo $ticket['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="priority-badge priority-<?php echo $ticket['priority']; ?>">
                                            <?php echo ucfirst($ticket['priority']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $ticket['assigned_to_name'] ? htmlspecialchars($ticket['assigned_to_name']) : 'Unassigned'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($ticket['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="openTicketModal('<?php echo $ticket['ticket_number']; ?>')" 
                                                class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="ticket_lookup.php?ticket=<?php echo urlencode($ticket['ticket_number']); ?>" 
                                           target="_blank" class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ticket Modal -->
    <div id="ticketModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Ticket Details</h3>
                    <button onclick="closeTicketModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div id="ticketModalContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function openTicketModal(ticketNumber) {
            // Load ticket details via AJAX
            fetch(`ticket_details.php?ticket_number=${encodeURIComponent(ticketNumber)}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('ticketModalContent').innerHTML = html;
                    document.getElementById('ticketModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error loading ticket details:', error);
                    alert('Error loading ticket details');
                });
        }

        function closeTicketModal() {
            document.getElementById('ticketModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('ticketModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeTicketModal();
            }
        });
    </script>
</body>
</html> 