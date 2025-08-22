<?php
// JLM Freelancer Web App - Admin Job Details Helper
session_start();

// Include configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is admin
if (!is_logged_in()) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

$user = get_current_jogigs_user();
if ($user['email'] !== 'admin@jogigs.com') {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

// Get job ID
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

if (!$job_id) {
    echo '<p class="text-red-600">Invalid job ID.</p>';
    exit;
}

// Get job details
$job = get_job_details($job_id);
$assignments = get_job_assignments($job_id);
$progress = get_job_progress($job_id);

if (!$job) {
    echo '<p class="text-red-600">Job not found.</p>';
    exit;
}
?>

<div class="space-y-6">
    <!-- Job Information -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h4 class="text-lg font-semibold text-gray-900 mb-4">Job Information</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-500">Job Title</p>
                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($job['title']); ?></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Status</p>
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo get_job_status_color($job['status']); ?>">
                    <?php echo ucfirst($job['status']); ?>
                </span>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Budget</p>
                <p class="text-sm text-gray-900"><?php echo format_currency($job['budget'], $job['currency']); ?></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Created</p>
                <p class="text-sm text-gray-900"><?php echo format_date($job['created_at']); ?></p>
            </div>
        </div>
        <div class="mt-4">
            <p class="text-sm font-medium text-gray-500">Description</p>
            <p class="text-sm text-gray-900 mt-1"><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
        </div>
    </div>

    <!-- Client Information -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h4 class="text-lg font-semibold text-gray-900 mb-4">Client Information</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-500">Client Name</p>
                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($job['client_name']); ?></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Client Email</p>
                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($job['client_email']); ?></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Service Type</p>
                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($job['service_type']); ?></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Quote Number</p>
                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($job['quote_number']); ?></p>
            </div>
        </div>
    </div>

    <!-- Job Assignments -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h4 class="text-lg font-semibold text-gray-900 mb-4">Job Assignments</h4>
        <?php if (empty($assignments)): ?>
            <p class="text-gray-500 text-center py-4">No assignments yet.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($assignments as $assignment): ?>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold">
                                    <?php echo strtoupper(substr($assignment['first_name'], 0, 1) . substr($assignment['last_name'], 0, 1)); ?>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($assignment['email']); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo get_assignment_status_color($assignment['status']); ?>">
                                    <?php echo ucfirst($assignment['status']); ?>
                                </span>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?php echo format_date($assignment['created_at']); ?>
                                </p>
                            </div>
                        </div>
                        <?php if ($assignment['status'] === 'in_progress' || $assignment['status'] === 'completed'): ?>
                            <div class="mt-3 pt-3 border-t border-gray-200">
                                <p class="text-xs text-gray-500">Progress: <?php echo $assignment['progress_percentage'] ?? 0; ?>%</p>
                                <?php if (!empty($assignment['milestone_description'])): ?>
                                    <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars($assignment['milestone_description']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Job Progress -->
    <?php if (!empty($progress)): ?>
        <div class="bg-gray-50 rounded-lg p-6">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Progress Updates</h4>
            <div class="space-y-3">
                <?php foreach ($progress as $update): ?>
                    <div class="border-l-4 border-indigo-500 pl-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900">
                                <?php echo $update['progress_percentage']; ?>% Complete
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php echo format_date($update['created_at']); ?>
                            </p>
                        </div>
                        <?php if (!empty($update['milestone_description'])): ?>
                            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($update['milestone_description']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Helper functions
function get_job_details($job_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                j.*,
                q.client_name,
                q.client_email,
                q.service_type,
                q.quote_number
            FROM jogigs_jobs j
            JOIN jogigs_quotes q ON j.quote_id = q.id
            WHERE j.id = ?
        ");
        $stmt->execute([$job_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to get job details: " . $e->getMessage());
        return null;
    }
}

function get_job_assignments($job_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ja.*,
                u.first_name,
                u.last_name,
                u.email,
                jp.progress_percentage,
                jp.milestone_description
            FROM jogigs_job_assignments ja
            JOIN jogigs_users u ON ja.freelancer_id = u.id
            LEFT JOIN jogigs_job_progress jp ON ja.id = jp.assignment_id
            WHERE ja.job_id = ?
            ORDER BY ja.created_at DESC
        ");
        $stmt->execute([$job_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to get job assignments: " . $e->getMessage());
        return [];
    }
}

function get_job_progress($job_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                jp.*
            FROM jogigs_job_progress jp
            JOIN jogigs_job_assignments ja ON jp.assignment_id = ja.id
            WHERE ja.job_id = ?
            ORDER BY jp.created_at DESC
        ");
        $stmt->execute([$job_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to get job progress: " . $e->getMessage());
        return [];
    }
}

function get_job_status_color($status) {
    switch ($status) {
        case 'created':
            return 'bg-blue-100 text-blue-800';
        case 'in_progress':
            return 'bg-yellow-100 text-yellow-800';
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

function get_assignment_status_color($status) {
    switch ($status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'active':
            return 'bg-blue-100 text-blue-800';
        case 'in_progress':
            return 'bg-purple-100 text-purple-800';
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'declined':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>


