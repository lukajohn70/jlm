<?php
// API endpoint to get payout account details
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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid account ID']);
    exit;
}

$account_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM jogigs_payout_accounts 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$account_id, $user_id]);
    $account = $stmt->fetch();
    
    if (!$account) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Account not found']);
        exit;
    }
    
    echo json_encode(['success' => true, 'account' => $account]);
    
} catch (Exception $e) {
    error_log("Error getting payout account: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
