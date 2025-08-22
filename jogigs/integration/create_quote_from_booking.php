<?php
// JLM Freelancer Web App - Integration Script
// This script can be called from the main JLM booking system to create quotes

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Set headers for API response
header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate required parameters
$required_fields = ['booking_id', 'client_name', 'client_email', 'services', 'project_description'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: {$field}"]);
        exit;
    }
}

try {
    // Prepare booking data
    $booking_data = [
        'booking_id' => $_POST['booking_id'],
        'client_name' => $_POST['client_name'],
        'client_email' => $_POST['client_email'],
        'client_phone' => $_POST['client_phone'] ?? '',
        'services' => $_POST['services'],
        'project_description' => $_POST['project_description']
    ];
    
    // Create quote
    $result = create_quote_from_booking($booking_data);
    
    if ($result['success']) {
        // Log the integration call
        log_activity(null, 'integration_quote_created', "Quote created via integration for booking {$booking_data['booking_id']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Quote created successfully',
            'quote_id' => $result['quote_id'],
            'quote_number' => $result['quote_number'],
            'amount' => $result['amount']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Integration quote creation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while creating the quote'
    ]);
}
?>


