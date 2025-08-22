<?php
session_start();
require_once __DIR__ . '/config/config.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    exit('Unauthorized');
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    exit('Invalid message ID');
}

// Get message details
$stmt = $pdo->prepare("
    SELECT c.*, 
           u1.name as replied_by_name,
           u2.name as archived_by_name,
           u3.name as deleted_by_name
    FROM contact_submissions c
    LEFT JOIN users u1 ON c.replied_by = u1.id
    LEFT JOIN users u2 ON c.archived_by = u2.id
    LEFT JOIN users u3 ON c.deleted_by = u3.id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$message) {
    http_response_code(404);
    exit('Message not found');
}

// Get reply history
$stmt = $pdo->prepare("
    SELECT r.*, u.name as admin_name
    FROM contact_replies r
    LEFT JOIN users u ON r.admin_id = u.id
    WHERE r.contact_id = ?
    ORDER BY r.sent_at DESC
");
$stmt->execute([$id]);
$replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark as read if it's new
if ($message['status'] === 'new') {
    $stmt = $pdo->prepare('UPDATE contact_submissions SET status = "read" WHERE id = ?');
    $stmt->execute([$id]);
    $message['status'] = 'read';
}
?>

<div class="bg-white p-6 rounded-lg">
    <!-- Header -->
    <div class="flex justify-between items-start mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($message['subject'] ?? 'No Subject'); ?></h2>
            <div class="flex items-center space-x-4 text-sm text-gray-600">
                <span>From: <?php echo htmlspecialchars($message['name']); ?></span>
                <span>•</span>
                <span><?php echo htmlspecialchars($message['email']); ?></span>
                <?php if ($message['phone']): ?>
                <span>•</span>
                <span><?php echo htmlspecialchars($message['phone']); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex items-center space-x-2">
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
        </div>
    </div>

    <!-- Message Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-3">Message Details</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <p class="text-sm text-gray-900"><?php echo htmlspecialchars($message['name']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <p class="text-sm text-gray-900"><?php echo htmlspecialchars($message['email']); ?></p>
                </div>
                <?php if ($message['phone']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <p class="text-sm text-gray-900"><?php echo htmlspecialchars($message['phone']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($message['service']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Service</label>
                    <p class="text-sm text-gray-900"><?php echo htmlspecialchars($message['service']); ?></p>
                </div>
                <?php endif; ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Submitted</label>
                    <p class="text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($message['submitted_at'])); ?></p>
                </div>
            </div>
        </div>
        
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-3">Admin Actions</h3>
            <div class="space-y-3">
                <?php if ($message['replied_at']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Replied</label>
                    <p class="text-sm text-gray-900">
                        <?php echo date('M j, Y g:i A', strtotime($message['replied_at'])); ?>
                        <?php if ($message['replied_by_name']): ?>
                        by <?php echo htmlspecialchars($message['replied_by_name']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <?php if ($message['archived_at']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Archived</label>
                    <p class="text-sm text-gray-900">
                        <?php echo date('M j, Y g:i A', strtotime($message['archived_at'])); ?>
                        <?php if ($message['archived_by_name']): ?>
                        by <?php echo htmlspecialchars($message['archived_by_name']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <?php if ($message['admin_notes']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Admin Notes</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo nl2br(htmlspecialchars($message['admin_notes'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Message Content -->
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-3">Message</h3>
        <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($message['message']); ?></p>
        </div>
    </div>

    <!-- Reply History -->
    <?php if (!empty($replies)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-3">Reply History</h3>
        <div class="space-y-4">
            <?php foreach ($replies as $reply): ?>
            <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-400">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <span class="text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($reply['admin_name'] ?? 'Admin'); ?>
                        </span>
                        <span class="text-sm text-gray-600 ml-2">
                            <?php echo date('M j, Y g:i A', strtotime($reply['sent_at'])); ?>
                        </span>
                    </div>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <?php echo ucfirst($reply['status']); ?>
                    </span>
                </div>
                <?php if ($reply['subject']): ?>
                <div class="text-sm font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($reply['subject']); ?></div>
                <?php endif; ?>
                <div class="text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($reply['message'])); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200">
        <button onclick="replyToMessage(<?php echo $message['id']; ?>)" 
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-reply mr-2"></i>Reply
        </button>
        
        <button onclick="updatePriority(<?php echo $message['id']; ?>)" 
                class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition">
            <i class="fas fa-flag mr-2"></i>Update Priority
        </button>
        
        <button onclick="addNotes(<?php echo $message['id']; ?>)" 
                class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
            <i class="fas fa-sticky-note mr-2"></i>Add Notes
        </button>
        
        <?php if ($message['status'] !== 'archived'): ?>
        <button onclick="archiveMessage(<?php echo $message['id']; ?>)" 
                class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
            <i class="fas fa-archive mr-2"></i>Archive
        </button>
        <?php endif; ?>
        
        <button onclick="deleteMessage(<?php echo $message['id']; ?>)" 
                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
            <i class="fas fa-trash mr-2"></i>Delete
        </button>
        
        <button onclick="closeModal()" 
                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
            Close
        </button>
    </div>
</div>

<script>
function closeModal() {
    document.getElementById('messageModal').classList.add('hidden');
}

function replyToMessage(id) {
    document.getElementById('replyContactId').value = id;
    document.getElementById('replyModal').classList.remove('hidden');
    closeModal();
}

function updatePriority(id) {
    const priority = prompt('Enter new priority (urgent/high/medium/low):');
    if (priority && ['urgent', 'high', 'medium', 'low'].includes(priority.toLowerCase())) {
        submitAction('update_priority', id, { priority: priority.toLowerCase() });
    }
}

function addNotes(id) {
    const notes = prompt('Enter admin notes:');
    if (notes) {
        submitAction('add_notes', id, { notes: notes });
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

function submitAction(action, id, additionalData = {}) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'contact_manage.php';
    
    let formHTML = `
        <input type="hidden" name="action" value="${action}">
        <input type="hidden" name="contact_id" value="${id}">
    `;
    
    for (const [key, value] of Object.entries(additionalData)) {
        formHTML += `<input type="hidden" name="${key}" value="${value}">`;
    }
    
    form.innerHTML = formHTML;
    document.body.appendChild(form);
    form.submit();
}
</script> 