<?php
// JLM Freelancer Web App - Integration API for Job Creation from Bookings
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['booking_id']) || !isset($input['admin_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$booking_id = (int)$input['booking_id'];
$admin_id = (int)$input['admin_id'];

try {
    // Create job from booking
    $result = create_job_from_booking($booking_id, $admin_id);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Job created successfully',
            'job_id' => $result['job_id'],
            'quote_id' => $result['quote_id']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Job creation from booking error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while creating the job'
    ]);
}
?>
