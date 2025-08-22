<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    error_log("AJAX Request - Unauthorized access attempt");
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Please log in again']);
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid booking ID']);
    exit;
}

$booking_id = (int)$_GET['id'];
error_log("AJAX Request - Fetching booking ID: " . $booking_id);

try {
    // First, get the basic booking details
    $stmt = $pdo->prepare('SELECT * FROM booking_submissions WHERE id = ?');
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        error_log("AJAX Request - Booking not found: " . $booking_id);
        http_response_code(404);
        echo json_encode(['error' => 'Booking not found']);
        exit;
    }

    // Get user names for the action fields if they exist
    if ($booking['confirmed_by']) {
        $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
        $stmt->execute([$booking['confirmed_by']]);
        $booking['confirmed_by_name'] = $stmt->fetchColumn() ?: 'Unknown';
    } else {
        $booking['confirmed_by_name'] = null;
    }

    if ($booking['completed_by']) {
        $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
        $stmt->execute([$booking['completed_by']]);
        $booking['completed_by_name'] = $stmt->fetchColumn() ?: 'Unknown';
    } else {
        $booking['completed_by_name'] = null;
    }

    if ($booking['cancelled_by']) {
        $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
        $stmt->execute([$booking['cancelled_by']]);
        $booking['cancelled_by_name'] = $stmt->fetchColumn() ?: 'Unknown';
    } else {
        $booking['cancelled_by_name'] = null;
    }

    // Format dates
    $booking['submitted_at_formatted'] = date('M j, Y g:i A', strtotime($booking['submitted_at']));
    $booking['confirmed_at_formatted'] = $booking['confirmed_at'] ? date('M j, Y g:i A', strtotime($booking['confirmed_at'])) : null;
    $booking['completed_at_formatted'] = $booking['completed_at'] ? date('M j, Y g:i A', strtotime($booking['completed_at'])) : null;
    $booking['cancelled_at_formatted'] = $booking['cancelled_at'] ? date('M j, Y g:i A', strtotime($booking['cancelled_at'])) : null;

    error_log("AJAX Request - Successfully fetched booking: " . $booking_id);

    // Return booking data
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'booking' => $booking]);

} catch (Exception $e) {
    error_log("AJAX Request - Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 