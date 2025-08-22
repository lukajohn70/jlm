<?php
/**
 * Ticket Details AJAX Handler
 * Returns ticket details for the modal
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/ticket_utils.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied');
}

if (!isset($_GET['ticket_number'])) {
    http_response_code(400);
    exit('Ticket number required');
}

$ticketNumber = $_GET['ticket_number'];
$ticketInfo = getTicketInfo($ticketNumber);
$ticketActivities = getTicketActivities($ticketNumber);

if (!$ticketInfo) {
    http_response_code(404);
    exit('Ticket not found');
}

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

<div class="space-y-6">
    <!-- Ticket Header -->
    <div class="flex items-center justify-between">
        <div>
            <h4 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($ticketInfo['ticket_number']); ?></h4>
            <p class="text-sm text-gray-500"><?php echo ucfirst($ticketInfo['form_type']); ?> Submission</p>
        </div>
        <div class="flex items-center space-x-2">
            <span class="status-badge status-<?php echo $ticketInfo['status']; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $ticketInfo['status'])); ?>
            </span>
            <span class="priority-badge priority-<?php echo $ticketInfo['priority']; ?>">
                <?php echo ucfirst($ticketInfo['priority']); ?>
            </span>
        </div>
    </div>

    <!-- Ticket Information -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h5 class="font-medium text-gray-900 mb-3">Ticket Information</h5>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500">Submitter:</span>
                <span class="font-medium"><?php echo htmlspecialchars($ticketInfo['submitter_name']); ?></span>
            </div>
            <div>
                <span class="text-gray-500">Email:</span>
                <span class="font-medium"><?php echo htmlspecialchars($ticketInfo['submitter_email']); ?></span>
            </div>
            <div>
                <span class="text-gray-500">Created:</span>
                <span class="font-medium"><?php echo date('M j, Y g:i A', strtotime($ticketInfo['created_at'])); ?></span>
            </div>
            <div>
                <span class="text-gray-500">Updated:</span>
                <span class="font-medium"><?php echo date('M j, Y g:i A', strtotime($ticketInfo['updated_at'])); ?></span>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="space-y-4">
        <!-- Update Status -->
        <div>
            <h5 class="font-medium text-gray-900 mb-2">Update Status</h5>
            <form method="post" action="ticket_manage.php" class="flex space-x-2">
                <input type="hidden" name="ticket_number" value="<?php echo htmlspecialchars($ticketInfo['ticket_number']); ?>">
                <select name="new_status" class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm">
                    <option value="open" <?php echo $ticketInfo['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                    <option value="in_progress" <?php echo $ticketInfo['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="resolved" <?php echo $ticketInfo['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    <option value="closed" <?php echo $ticketInfo['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
                <button type="submit" name="update_status" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">
                    Update
                </button>
            </form>
        </div>

        <!-- Assign Ticket -->
        <div>
            <h5 class="font-medium text-gray-900 mb-2">Assign Ticket</h5>
            <form method="post" action="ticket_manage.php" class="flex space-x-2">
                <input type="hidden" name="ticket_number" value="<?php echo htmlspecialchars($ticketInfo['ticket_number']); ?>">
                <select name="assign_to" class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm">
                    <option value="">Unassigned</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $ticketInfo['assigned_to'] == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="assign_ticket" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                    Assign
                </button>
            </form>
        </div>

        <!-- Add Note -->
        <div>
            <h5 class="font-medium text-gray-900 mb-2">Add Note</h5>
            <form method="post" action="ticket_manage.php" class="space-y-2">
                <input type="hidden" name="ticket_number" value="<?php echo htmlspecialchars($ticketInfo['ticket_number']); ?>">
                <textarea name="note" rows="3" placeholder="Enter your note..." 
                          class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm resize-none"></textarea>
                <button type="submit" name="add_note" class="px-4 py-2 bg-purple-600 text-white rounded-md text-sm hover:bg-purple-700">
                    Add Note
                </button>
            </form>
        </div>
    </div>

    <!-- Current Notes -->
    <?php if ($ticketInfo['notes']): ?>
        <div>
            <h5 class="font-medium text-gray-900 mb-2">Notes</h5>
            <div class="bg-gray-50 p-3 rounded-md">
                <pre class="text-sm text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($ticketInfo['notes']); ?></pre>
            </div>
        </div>
    <?php endif; ?>

    <!-- Activity History -->
    <?php if ($ticketActivities): ?>
        <div>
            <h5 class="font-medium text-gray-900 mb-3">Recent Activity</h5>
            <div class="space-y-2 max-h-40 overflow-y-auto">
                <?php foreach (array_slice($ticketActivities, 0, 5) as $activity): ?>
                    <div class="flex items-start space-x-3 p-2 bg-gray-50 rounded">
                        <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                            <?php
                            $icon = 'fa-info-circle';
                            switch ($activity['activity_type']) {
                                case 'created': $icon = 'fa-plus-circle'; break;
                                case 'status_changed': $icon = 'fa-exchange-alt'; break;
                                case 'assigned': $icon = 'fa-user-plus'; break;
                                case 'note_added': $icon = 'fa-sticky-note'; break;
                                case 'email_sent': $icon = 'fa-envelope'; break;
                                case 'resolved': $icon = 'fa-check-circle'; break;
                            }
                            ?>
                            <i class="fas <?php echo $icon; ?> text-blue-600 text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-900"><?php echo htmlspecialchars($activity['description']); ?></p>
                            <p class="text-xs text-gray-500">
                                <?php echo date('M j, g:i A', strtotime($activity['performed_at'])); ?>
                                <?php if ($activity['performed_by_name']): ?>
                                    by <?php echo htmlspecialchars($activity['performed_by_name']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($ticketActivities) > 5): ?>
                <p class="text-xs text-gray-500 mt-2">Showing 5 of <?php echo count($ticketActivities); ?> activities</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- External Links -->
    <div class="flex space-x-2 pt-4 border-t">
        <a href="ticket_lookup.php?ticket=<?php echo urlencode($ticketInfo['ticket_number']); ?>" 
           target="_blank" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm text-center hover:bg-gray-200">
            <i class="fas fa-external-link-alt mr-2"></i>View Public Page
        </a>
        <button onclick="closeTicketModal()" class="px-4 py-2 bg-gray-500 text-white rounded-md text-sm hover:bg-gray-600">
            Close
        </button>
    </div>
</div>

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