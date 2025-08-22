<?php
// AJAX handler for save/unsave job functionality
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is authenticated
if (!is_authenticated()) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Get current user
$user = get_current_jogigs_user();

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get action and job ID
$action = $_POST['action'] ?? '';
$job_id = (int)($_POST['job_id'] ?? 0);

if (!$job_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid job ID']);
    exit;
}

// Process the action
switch ($action) {
    case 'save':
        $notes = $_POST['notes'] ?? '';
        $result = save_job($user['id'], $job_id, $notes);
        break;
        
    case 'unsave':
        $result = unsave_job($user['id'], $job_id);
        break;
        
    default:
        $result = ['success' => false, 'message' => 'Invalid action'];
        break;
}

// Return JSON response
echo json_encode($result);
?>
