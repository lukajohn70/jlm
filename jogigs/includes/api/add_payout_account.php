<?php
// API endpoint to add new payout account
session_start();
header('Content-Type: application/json');

require_once '../config.php';
require_once '../functions.php';
require_once '../auth.php';

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Validate input
$data = [
    'account_type' => $_POST['account_type'] ?? '',
    'account_name' => $_POST['account_name'] ?? '',
    'account_number' => $_POST['account_number'] ?? '',
    'bank_name' => $_POST['bank_name'] ?? '',
    'bank_code' => $_POST['bank_code'] ?? '',
    'paypal_email' => $_POST['paypal_email'] ?? '',
    'stripe_account_id' => $_POST['stripe_account_id'] ?? '',
    'mobile_number' => $_POST['mobile_number'] ?? '',
    'mobile_provider' => $_POST['mobile_provider'] ?? '',
    'is_default' => isset($_POST['is_default'])
];

// Validate the data
$errors = validate_payout_account($data);
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $account_id = add_payout_account($user_id, $data);
    
    if ($account_id) {
        log_activity($user_id, 'payout_account_added', "Added new {$data['account_type']} payout account");
        echo json_encode(['success' => true, 'message' => 'Payout account added successfully', 'account_id' => $account_id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to add payout account']);
    }
    
} catch (Exception $e) {
    error_log("Error adding payout account: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
