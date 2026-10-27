<?php
// Helper function to safely get count from table
function safeCount($pdo, $table, $where = '') {
    try {
        $sql = "SELECT COUNT(*) FROM $table";
        if ($where) {
            $sql .= " WHERE $where";
        }
        return $pdo->query($sql)->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

// Helper function to safely get data from table
function safeQuery($pdo, $sql) {
    try {
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// Helper function to safely execute prepared statement
function safeExecute($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

session_start();
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/email_utils.php';
$admin_user = require_admin_auth();
$admin_id = $admin_user['id'];
$toast = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_subscriber':
                $email = trim($_POST['email']);
                $first_name = trim($_POST['first_name'] ?? '');
                $last_name = trim($_POST['last_name'] ?? '');
                
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    try {
                        $stmt = $pdo->prepare('INSERT INTO email_subscribers (email, first_name, last_name) VALUES (?, ?, ?)');
                        $stmt->execute([$email, $first_name, $last_name]);
                        $toast = 'Subscriber added successfully.';
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000) { // Duplicate entry
                            $toast = 'Email already exists in the list.';
                        } else {
                            $toast = 'Error adding subscriber.';
                        }
                    }
                } else {
                    $toast = 'Invalid email address.';
                }
                break;
                
            case 'unsubscribe':
                $subscriber_id = (int)$_POST['subscriber_id'];
                $stmt = $pdo->prepare('UPDATE email_subscribers SET status = "unsubscribed", unsubscribed_at = NOW() WHERE id = ?');
                $stmt->execute([$subscriber_id]);
                $toast = 'Subscriber unsubscribed.';
                break;
                
            case 'delete_subscriber':
                $subscriber_id = (int)$_POST['subscriber_id'];
                $stmt = $pdo->prepare('DELETE FROM email_subscribers WHERE id = ?');
                $stmt->execute([$subscriber_id]);
                $toast = 'Subscriber deleted.';
                break;
                
            case 'create_campaign':
                $name = trim($_POST['campaign_name']);
                $subject = trim($_POST['campaign_subject']);
                $content = trim($_POST['campaign_content']);
                
                if ($name && $subject && $content) {
                    $stmt = $pdo->prepare('INSERT INTO email_campaigns (name, subject, content, created_by) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$name, $subject, $content, $admin_id]);
                    $toast = 'Campaign created successfully.';
                } else {
                    $toast = 'Please fill in all required fields.';
                }
                break;
                
            case 'send_campaign':
                $campaign_id = (int)$_POST['campaign_id'];
                
                // Get campaign details
                $stmt = $pdo->prepare('SELECT * FROM email_campaigns WHERE id = ?');
                $stmt->execute([$campaign_id]);
                $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($campaign) {
                    // Get active subscribers
                    $stmt = $pdo->prepare('SELECT * FROM email_subscribers WHERE status = "active"');
                    $stmt->execute();
                    $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $sent_count = 0;
                    $failed_count = 0;
                    
                    foreach ($subscribers as $subscriber) {
                        // Check if recipient already exists
                        $stmt = $pdo->prepare('SELECT id FROM email_campaign_recipients WHERE campaign_id = ? AND subscriber_id = ?');
                        $stmt->execute([$campaign_id, $subscriber['id']]);
                        $existing_recipient = $stmt->fetch();
                        
                        if (!$existing_recipient) {
                            // Add to campaign recipients only if not already added
                            $stmt = $pdo->prepare('INSERT INTO email_campaign_recipients (campaign_id, subscriber_id) VALUES (?, ?)');
                            $stmt->execute([$campaign_id, $subscriber['id']]);
                        }
                        
                        // Send email
                        $emailResult = sendNewsletterEmail($subscriber['email'], $subscriber['first_name'], $campaign['subject'], $campaign['content']);
                        
                        if ($emailResult) {
                            $stmt = $pdo->prepare('UPDATE email_campaign_recipients SET status = "sent", sent_at = NOW() WHERE campaign_id = ? AND subscriber_id = ?');
                            $stmt->execute([$campaign_id, $subscriber['id']]);
                            $sent_count++;
                        } else {
                            $stmt = $pdo->prepare('UPDATE email_campaign_recipients SET status = "failed" WHERE campaign_id = ? AND subscriber_id = ?');
                            $stmt->execute([$campaign_id, $subscriber['id']]);
                            $failed_count++;
                            error_log("Failed to send campaign email to: " . $subscriber['email'] . " for campaign: " . $campaign['name']);
                        }
                    }
                    
                    // Update campaign status
                    $stmt = $pdo->prepare('UPDATE email_campaigns SET status = "sent", sent_at = NOW() WHERE id = ?');
                    $stmt->execute([$campaign_id]);
                    
                    $toast = "Campaign sent! $sent_count emails sent, $failed_count failed.";
                }
                break;
                
            case 'delete_campaign':
                $campaign_id = (int)$_POST['campaign_id'];
                $stmt = $pdo->prepare('DELETE FROM email_campaigns WHERE id = ?');
                $stmt->execute([$campaign_id]);
                $toast = 'Campaign deleted.';
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
$search = $_GET['search'] ?? '';
$tab = $_GET['tab'] ?? 'subscribers';

// Get subscribers
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$subscribers = safeExecute($pdo, "SELECT * FROM email_subscribers $where_clause ORDER BY created_at DESC", $params);

// Get campaigns
$campaigns = safeQuery($pdo, 'SELECT * FROM email_campaigns ORDER BY created_at DESC');

// Get statistics
$stats = [
    'total' => safeCount($pdo, 'email_subscribers'),
    'active' => safeCount($pdo, 'email_subscribers', "status = 'active'"),
    'unsubscribed' => safeCount($pdo, 'email_subscribers', "status = 'unsubscribed'"),
    'bounced' => safeCount($pdo, 'email_subscribers', "status = 'bounced'"),
    'campaigns' => safeCount($pdo, 'email_campaigns'),
    'sent_campaigns' => safeCount($pdo, 'email_campaigns', "status = 'sent'"),
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subscription Management | JLM Admin</title>
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
                <span class="text-xl font-bold text-gray-900">Subscription Management</span>
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
                <div class="text-2xl font-bold text-blue-600"><?php echo $stats['total']; ?></div>
                <div class="text-sm text-gray-600">Total Subscribers</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-2xl font-bold text-green-600"><?php echo $stats['active']; ?></div>
                <div class="text-sm text-gray-600">Active</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['unsubscribed']; ?></div>
                <div class="text-sm text-gray-600">Unsubscribed</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-2xl font-bold text-red-600"><?php echo $stats['bounced']; ?></div>
                <div class="text-sm text-gray-600">Bounced</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-2xl font-bold text-purple-600"><?php echo $stats['campaigns']; ?></div>
                <div class="text-sm text-gray-600">Total Campaigns</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-2xl font-bold text-indigo-600"><?php echo $stats['sent_campaigns']; ?></div>
                <div class="text-sm text-gray-600">Sent Campaigns</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6">
                    <a href="?tab=subscribers" class="py-4 px-1 border-b-2 font-medium text-sm <?php echo $tab === 'subscribers' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                        <i class="fas fa-users mr-2"></i>Subscribers
                    </a>
                    <a href="?tab=campaigns" class="py-4 px-1 border-b-2 font-medium text-sm <?php echo $tab === 'campaigns' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                        <i class="fas fa-envelope mr-2"></i>Campaigns
                    </a>
                </nav>
            </div>
        </div>

        <?php if ($tab === 'subscribers'): ?>
        <!-- Subscribers Tab -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Email Subscribers</h3>
                    <button onclick="showAddSubscriberModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-plus mr-2"></i>Add Subscriber
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="px-6 py-4 border-b border-gray-200">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="hidden" name="tab" value="subscribers">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="unsubscribed" <?php echo $status_filter === 'unsubscribed' ? 'selected' : ''; ?>>Unsubscribed</option>
                            <option value="bounced" <?php echo $status_filter === 'bounced' ? 'selected' : ''; ?>>Bounced</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search subscribers..." class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                            <i class="fas fa-search mr-2"></i>Filter
                        </button>
                    </div>
                    <div class="flex items-end">
                        <a href="?tab=subscribers" class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition text-center">
                            <i class="fas fa-refresh mr-2"></i>Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Subscribers Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subscriber</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subscribed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($subscribers as $subscriber): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-700">
                                                <?php echo strtoupper(substr($subscriber['email'], 0, 1)); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($subscriber['email']); ?></div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($subscriber['first_name'] . ' ' . $subscriber['last_name']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_colors = [
                                    'active' => 'bg-green-100 text-green-800',
                                    'unsubscribed' => 'bg-yellow-100 text-yellow-800',
                                    'bounced' => 'bg-red-100 text-red-800'
                                ];
                                $status_color = $status_colors[$subscriber['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_color; ?>">
                                    <?php echo ucfirst($subscriber['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($subscriber['subscribed_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <?php if ($subscriber['status'] === 'active'): ?>
                                    <button onclick="unsubscribeSubscriber(<?php echo $subscriber['id']; ?>)" 
                                            class="text-yellow-600 hover:text-yellow-900"
                                            title="Unsubscribe This User">
                                        <i class="fas fa-user-times"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button onclick="deleteSubscriber(<?php echo $subscriber['id']; ?>)" 
                                            class="text-red-600 hover:text-red-900"
                                            title="Delete Subscriber">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($tab === 'campaigns'): ?>
        <!-- Campaigns Tab -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Email Campaigns</h3>
                    <button onclick="showCreateCampaignModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-plus mr-2"></i>Create Campaign
                    </button>
                </div>
            </div>

            <!-- Campaigns Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($campaigns as $campaign): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($campaign['name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($campaign['subject']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_colors = [
                                    'draft' => 'bg-gray-100 text-gray-800',
                                    'scheduled' => 'bg-blue-100 text-blue-800',
                                    'sending' => 'bg-yellow-100 text-yellow-800',
                                    'sent' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800'
                                ];
                                $status_color = $status_colors[$campaign['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_color; ?>">
                                    <?php echo ucfirst($campaign['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($campaign['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button onclick="viewCampaign(<?php echo $campaign['id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-900" 
                                            title="View Campaign Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($campaign['status'] === 'draft'): ?>
                                    <button onclick="sendCampaign(<?php echo $campaign['id']; ?>)" 
                                            class="text-green-600 hover:text-green-900"
                                            title="Send Campaign to All Active Subscribers">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button onclick="deleteCampaign(<?php echo $campaign['id']; ?>)" 
                                            class="text-red-600 hover:text-red-900"
                                            title="Delete Campaign">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add Subscriber Modal -->
    <div id="addSubscriberModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Subscriber</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add_subscriber">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                            <input type="text" name="first_name" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                            <input type="text" name="last_name" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                        <input type="email" name="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeAddSubscriberModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Add Subscriber
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Campaign Modal -->
    <div id="createCampaignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Campaign</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create_campaign">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Campaign Name *</label>
                        <input type="text" name="campaign_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subject *</label>
                        <input type="text" name="campaign_subject" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Content *</label>
                        <textarea name="campaign_content" rows="10" required 
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                        <p class="text-sm text-gray-500 mt-1">You can use HTML formatting. Use {{first_name}} to personalize the email.</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeCreateCampaignModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Create Campaign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Campaign Modal -->
    <div id="viewCampaignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Campaign Details</h3>
                    <button onclick="closeViewCampaignModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div id="campaignDetails" class="space-y-4">
                    <!-- Campaign details will be loaded here -->
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button onclick="closeViewCampaignModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Close
                    </button>
                    <button id="sendCampaignBtn" onclick="sendCampaignFromModal()" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 hidden">
                        <i class="fas fa-paper-plane mr-2"></i>Send Campaign
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showAddSubscriberModal() {
            document.getElementById('addSubscriberModal').classList.remove('hidden');
        }

        function closeAddSubscriberModal() {
            document.getElementById('addSubscriberModal').classList.add('hidden');
        }

        function showCreateCampaignModal() {
            document.getElementById('createCampaignModal').classList.remove('hidden');
        }

        function closeCreateCampaignModal() {
            document.getElementById('createCampaignModal').classList.add('hidden');
        }

        function unsubscribeSubscriber(id) {
            if (confirm('Unsubscribe this subscriber?')) {
                submitAction('unsubscribe', { subscriber_id: id });
            }
        }

        function deleteSubscriber(id) {
            if (confirm('Are you sure you want to delete this subscriber? This action cannot be undone.')) {
                submitAction('delete_subscriber', { subscriber_id: id });
            }
        }

        function sendCampaign(id) {
            if (confirm('Send this campaign to all active subscribers?')) {
                submitAction('send_campaign', { campaign_id: id });
            }
        }

        function deleteCampaign(id) {
            if (confirm('Are you sure you want to delete this campaign? This action cannot be undone.')) {
                submitAction('delete_campaign', { campaign_id: id });
            }
        }

        function viewCampaign(id) {
            // Fetch campaign details via AJAX
            fetch('get_campaign_details.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const campaign = data.campaign;
                        const detailsDiv = document.getElementById('campaignDetails');
                        
                        detailsDiv.innerHTML = `
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Campaign Name</label>
                                    <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">${campaign.name}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        ${campaign.status.charAt(0).toUpperCase() + campaign.status.slice(1)}
                                    </span>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                                    <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">${campaign.subject}</p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                                    <div class="text-sm text-gray-900 bg-gray-50 p-3 rounded max-h-64 overflow-y-auto border">
                                        ${campaign.content}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Created</label>
                                    <p class="text-sm text-gray-900">${new Date(campaign.created_at).toLocaleDateString()}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Subscribers</label>
                                    <p class="text-sm text-gray-900">${campaign.subscriber_count || 0} active</p>
                                </div>
                            </div>
                        `;
                        
                        // Show/hide send button based on status
                        const sendBtn = document.getElementById('sendCampaignBtn');
                        if (campaign.status === 'draft') {
                            sendBtn.classList.remove('hidden');
                            sendBtn.setAttribute('data-campaign-id', id);
                        } else {
                            sendBtn.classList.add('hidden');
                        }
                        
                        document.getElementById('viewCampaignModal').classList.remove('hidden');
                    } else {
                        alert('Error loading campaign details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading campaign details');
                });
        }

        function closeViewCampaignModal() {
            document.getElementById('viewCampaignModal').classList.add('hidden');
        }

        function sendCampaignFromModal() {
            const campaignId = document.getElementById('sendCampaignBtn').getAttribute('data-campaign-id');
            closeViewCampaignModal();
            sendCampaign(campaignId);
        }

        function submitAction(action, data = {}) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="action" value="${action}">`;
            
            for (const [key, value] of Object.entries(data)) {
                form.innerHTML += `<input type="hidden" name="${key}" value="${value}">`;
            }
            
            document.body.appendChild(form);
            form.submit();
        }

        // Close modals when clicking outside
        document.getElementById('addSubscriberModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });

        document.getElementById('createCampaignModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });

        document.getElementById('viewCampaignModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    </script>
</body>
</html> 
