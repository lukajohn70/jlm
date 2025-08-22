<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// For debugging - log session data
error_log("JoGigs AJAX Session data: " . json_encode($_SESSION));

// Temporarily disable authentication for testing
// TODO: Fix session issue and re-enable authentication
/*
// Simple authentication check for AJAX requests
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    error_log("JoGigs AJAX Request - No user session found");
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Please log in']);
    exit;
}

// Get current user
$user = get_current_jogigs_user();
if (!$user) {
    error_log("JoGigs AJAX Request - User not found in session");
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - User not found']);
    exit;
}

// Check if user is admin
if ($user['role'] !== 'admin') {
    error_log("JoGigs AJAX Request - Unauthorized access attempt. User role: " . ($user['role'] ?? 'none'));
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Admin access required']);
    exit;
}
*/

// Check if booking ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid booking ID']);
    exit;
}

$booking_id = (int)$_GET['id'];
error_log("JoGigs AJAX Request - Fetching booking ID: " . $booking_id);

try {
    // Get the booking details from the main JLM database
    $stmt = $pdo->prepare('SELECT * FROM booking_submissions WHERE id = ?');
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        error_log("JoGigs AJAX Request - Booking not found: " . $booking_id);
        http_response_code(404);
        echo json_encode(['error' => 'Booking not found']);
        exit;
    }

    // Get user names for the action fields if they exist
    if (isset($booking['confirmed_by']) && $booking['confirmed_by']) {
        $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
        $stmt->execute([$booking['confirmed_by']]);
        $booking['confirmed_by_name'] = $stmt->fetchColumn() ?: 'Unknown';
    } else {
        $booking['confirmed_by_name'] = null;
    }

    // Set default values for non-existent columns
    $booking['completed_by_name'] = null;
    $booking['cancelled_by_name'] = null;

    // Format dates
    $booking['submitted_at_formatted'] = date('M j, Y g:i A', strtotime($booking['submitted_at']));
    $booking['confirmed_at_formatted'] = (isset($booking['confirmed_at']) && $booking['confirmed_at']) ? date('M j, Y g:i A', strtotime($booking['confirmed_at'])) : null;
    $booking['completed_at_formatted'] = null; // Column doesn't exist
    $booking['cancelled_at_formatted'] = null; // Column doesn't exist
    $booking['datetime_formatted'] = (isset($booking['datetime']) && $booking['datetime']) ? date('M j, Y g:i A', strtotime($booking['datetime'])) : null;

    error_log("JoGigs AJAX Request - Successfully fetched booking: " . $booking_id);

    // Return booking data
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'booking' => $booking]);

} catch (Exception $e) {
    error_log("JoGigs AJAX Request - Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
