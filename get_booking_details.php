<?php
session_start();
require_once __DIR__ . '/config/config.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get booking ID from request
$booking_id = (int)($_GET['id'] ?? 0);

if (!$booking_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit;
}

try {
    // Get booking details
    $stmt = $pdo->prepare('SELECT * FROM booking_submissions WHERE id = ?');
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    // Return booking data
    echo json_encode([
        'success' => true,
        'booking' => $booking
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
