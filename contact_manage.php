<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/email_utils.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

$admin_id = $_SESSION['admin_id'] ?? 1; // Default admin ID
$toast = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $contact_id = (int)$_POST['contact_id'];
        
        switch ($_POST['action']) {
            case 'mark_read':
                $stmt = $pdo->prepare('UPDATE contact_submissions SET status = "read" WHERE id = ?');
                $stmt->execute([$contact_id]);
                $toast = 'Message marked as read.';
                break;
                
            case 'mark_replied':
                $stmt = $pdo->prepare('UPDATE contact_submissions SET status = "replied", replied_at = NOW(), replied_by = ? WHERE id = ?');
                $stmt->execute([$admin_id, $contact_id]);
                $toast = 'Message marked as replied.';
                break;
                
            case 'archive':
                $stmt = $pdo->prepare('UPDATE contact_submissions SET status = "archived", archived_at = NOW(), archived_by = ? WHERE id = ?');
                $stmt->execute([$admin_id, $contact_id]);
                $toast = 'Message archived.';
                break;
                
            case 'delete':
                $stmt = $pdo->prepare('UPDATE contact_submissions SET status = "deleted", deleted_at = NOW(), deleted_by = ? WHERE id = ?');
                $stmt->execute([$admin_id, $contact_id]);
                $toast = 'Message deleted.';
                break;
                
            case 'update_priority':
                $priority = $_POST['priority'];
                $stmt = $pdo->prepare('UPDATE contact_submissions SET priority = ? WHERE id = ?');
                $stmt->execute([$priority, $contact_id]);
                $toast = 'Priority updated.';
                break;
                
            case 'add_notes':
                $notes = $_POST['notes'];
                $stmt = $pdo->prepare('UPDATE contact_submissions SET admin_notes = ? WHERE id = ?');
                $stmt->execute([$notes, $contact_id]);
                $toast = 'Notes added.';
                break;
                
            case 'send_reply':
                $subject = $_POST['reply_subject'];
                $message = $_POST['reply_message'];
                
                // Get contact details
                $stmt = $pdo->query("SELECT * FROM contact_submissions WHERE id = $contact_id");
                $contact = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($contact) {
                    // Send email reply with ticket number
                    $emailResult = sendContactReply($contact['email'], $contact['name'], $subject, $message, $contact['ticket_number']);
                    
                    if ($emailResult) {
                        // Save reply to database
                        $stmt = $pdo->prepare('INSERT INTO contact_replies (contact_id, admin_id, subject, message) VALUES (?, ?, ?, ?)');
                        $stmt->execute([$contact_id, $admin_id, $subject, $message]);
                        
                        // Update contact status
                        $stmt = $pdo->prepare('UPDATE contact_submissions SET status = "replied", replied_at = NOW(), replied_by = ? WHERE id = ?');
                        $stmt->execute([$admin_id, $contact_id]);
                        
                        $toast = 'Reply sent successfully.';
                    } else {
                        $toast = 'Failed to send reply.';
                    }
                }
                break;
        }
        
        $_SESSION['toast'] = $toast;
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
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
$where_conditions = ["status != 'deleted'"];
$params = [];

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

if ($search) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ? OR ticket_number LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_submissions WHERE $where_clause");
$count_stmt->execute($params);
$total_messages = $count_stmt->fetchColumn();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$total_pages = ceil($total_messages / $per_page);
$offset = ($page - 1) * $per_page;

// Get messages
$order_by = $_GET['order'] ?? 'submitted_at DESC';
$query = "SELECT c.*, 
          u1.name as replied_by_name,
          u2.name as archived_by_name,
          u3.name as deleted_by_name
          FROM contact_submissions c
          LEFT JOIN users u1 ON c.replied_by = u1.id
          LEFT JOIN users u2 ON c.archived_by = u2.id
          LEFT JOIN users u3 ON c.deleted_by = u3.id
          WHERE $where_clause
          ORDER BY $order_by
          LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [
    'new' => $pdo->query("SELECT COUNT(*) FROM contact_submissions WHERE status = 'new'")->fetchColumn(),
    'read' => $pdo->query("SELECT COUNT(*) FROM contact_submissions WHERE status = 'read'")->fetchColumn(),
    'replied' => $pdo->query("SELECT COUNT(*) FROM contact_submissions WHERE status = 'replied'")->fetchColumn(),
    'archived' => $pdo->query("SELECT COUNT(*) FROM contact_submissions WHERE status = 'archived'")->fetchColumn(),
    'urgent' => $pdo->query("SELECT COUNT(*) FROM contact_submissions WHERE priority = 'urgent' AND status != 'deleted'")->fetchColumn(),
    'high' => $pdo->query("SELECT COUNT(*) FROM contact_submissions WHERE priority = 'high' AND status != 'deleted'")->fetchColumn(),
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Messages Management | JLM Admin</title>
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
                <span class="text-xl font-bold text-gray-900">Contact Messages</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="admin_dashboard.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
                <a href="?logout=1" class="px-4 py-2 border border-pink-500 text-pink-600 rounded-lg font-semibold hover:bg-pink-50 transition">Sign Out</a>
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
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-8">
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-2xl font-bold text-blue-600"><?php echo $stats['new']; ?></div>
                <div class="text-sm text-gray-600">New Messages</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['read']; ?></div>
                <div class="text-sm text-gray-600">Read</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-2xl font-bold text-green-600"><?php echo $stats['replied']; ?></div>
                <div class="text-sm text-gray-600">Replied</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-2xl font-bold text-gray-600"><?php echo $stats['archived']; ?></div>
                <div class="text-sm text-gray-600">Archived</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-2xl font-bold text-red-600"><?php echo $stats['urgent']; ?></div>
                <div class="text-sm text-gray-600">Urgent</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-2xl font-bold text-orange-600"><?php echo $stats['high']; ?></div>
                <div class="text-sm text-gray-600">High Priority</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New</option>
                        <option value="read" <?php echo $status_filter === 'read' ? 'selected' : ''; ?>>Read</option>
                        <option value="replied" <?php echo $status_filter === 'replied' ? 'selected' : ''; ?>>Replied</option>
                        <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                    <select name="priority" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="all" <?php echo $priority_filter === 'all' ? 'selected' : ''; ?>>All Priority</option>
                        <option value="urgent" <?php echo $priority_filter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                    <select name="date" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                        <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>This Month</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search messages..." class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Messages Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    Messages (<?php echo $total_messages; ?>)
                </h3>
            </div>
            
            <?php if (empty($messages)): ?>
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-inbox text-4xl mb-4"></i>
                <p>No messages found matching your criteria.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                                         <thead class="bg-gray-50">
                         <tr>
                             <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                             <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                             <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                             <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                             <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                             <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                             <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                         </tr>
                     </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($messages as $message): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-700">
                                                <?php echo strtoupper(substr($message['name'], 0, 1)); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($message['name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($message['email']); ?></div>
                                        <?php if ($message['phone']): ?>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($message['phone']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                                         </td>
                             <td class="px-6 py-4 whitespace-nowrap">
                                 <?php if ($message['ticket_number']): ?>
                                 <div class="text-sm font-mono text-blue-600 font-medium"><?php echo htmlspecialchars($message['ticket_number']); ?></div>
                                 <?php else: ?>
                                 <div class="text-sm text-gray-400">No ticket</div>
                                 <?php endif; ?>
                             </td>
                             <td class="px-6 py-4">
                                 <div class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($message['subject'] ?? 'No Subject'); ?></div>
                                 <div class="text-sm text-gray-500 truncate max-w-xs">
                                     <?php echo htmlspecialchars(substr($message['message'], 0, 100)) . (strlen($message['message']) > 100 ? '...' : ''); ?>
                                 </div>
                             </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_colors = [
                                    'new' => 'bg-blue-100 text-blue-800',
                                    'read' => 'bg-yellow-100 text-yellow-800',
                                    'replied' => 'bg-green-100 text-green-800',
                                    'archived' => 'bg-gray-100 text-gray-800'
                                ];
                                $status_color = $status_colors[$message['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_color; ?>">
                                    <?php echo ucfirst($message['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $priority_colors = [
                                    'urgent' => 'bg-red-100 text-red-800',
                                    'high' => 'bg-orange-100 text-orange-800',
                                    'medium' => 'bg-yellow-100 text-yellow-800',
                                    'low' => 'bg-green-100 text-green-800'
                                ];
                                $priority_color = $priority_colors[$message['priority']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $priority_color; ?>">
                                    <?php echo ucfirst($message['priority']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y g:i A', strtotime($message['submitted_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button onclick="viewMessage(<?php echo $message['id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($message['status'] === 'new'): ?>
                                    <button onclick="markAsRead(<?php echo $message['id']; ?>)" 
                                            class="text-yellow-600 hover:text-yellow-900">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button onclick="replyToMessage(<?php echo $message['id']; ?>)" 
                                            class="text-green-600 hover:text-green-900">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                    <button onclick="archiveMessage(<?php echo $message['id']; ?>)" 
                                            class="text-gray-600 hover:text-gray-900">
                                        <i class="fas fa-archive"></i>
                                    </button>
                                    <button onclick="deleteMessage(<?php echo $message['id']; ?>)" 
                                            class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $per_page, $total_messages); ?> of <?php echo $total_messages; ?> results
            </div>
            <div class="flex space-x-2">
                <?php if ($page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
                    Previous
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm <?php echo $i === $page ? 'bg-blue-600 text-white' : 'hover:bg-gray-50'; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
                    Next
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Message View Modal -->
    <div id="messageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3" id="messageContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div id="replyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Send Reply</h3>
                <form id="replyForm" method="POST">
                    <input type="hidden" name="action" value="send_reply">
                    <input type="hidden" name="contact_id" id="replyContactId">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                        <input type="text" name="reply_subject" id="replySubject" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                        <textarea name="reply_message" id="replyMessage" rows="6" 
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2" required></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeReplyModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function viewMessage(id) {
            // Load message content via AJAX
            fetch(`contact_view.php?id=${id}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('messageContent').innerHTML = html;
                    document.getElementById('messageModal').classList.remove('hidden');
                });
        }

        function replyToMessage(id) {
            document.getElementById('replyContactId').value = id;
            document.getElementById('replyModal').classList.remove('hidden');
        }

        function closeReplyModal() {
            document.getElementById('replyModal').classList.add('hidden');
        }

        function markAsRead(id) {
            if (confirm('Mark this message as read?')) {
                submitAction('mark_read', id);
            }
        }

        function archiveMessage(id) {
            if (confirm('Archive this message?')) {
                submitAction('archive', id);
            }
        }

        function deleteMessage(id) {
            if (confirm('Are you sure you want to delete this message? This action cannot be undone.')) {
                submitAction('delete', id);
            }
        }

        function submitAction(action, id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="${action}">
                <input type="hidden" name="contact_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Close modals when clicking outside
        document.getElementById('messageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });

        document.getElementById('replyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    </script>
</body>
</html> 