<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/email_utils.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$response = ['success' => false, 'message' => '', 'status' => 'error', 'title' => 'Subscription Update'];

try {
    $email = trim($_POST['email'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    
    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address.';
        $response['title'] = 'Invalid Email';
        $response['status'] = 'invalid_email';
        echo json_encode($response);
        exit;
    }
    
    // Check if already subscribed
    $stmt = $pdo->prepare('SELECT * FROM email_subscribers WHERE email = ?');
    $stmt->execute([$email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        if ($existing['status'] === 'active') {
            $response['success'] = true;
            $response['status'] = 'already_subscribed';
            $response['title'] = 'Already Subscribed';
            $response['message'] = 'This email is already subscribed to JLM updates.';
        } else {
            // Reactivate subscription
            $stmt = $pdo->prepare('UPDATE email_subscribers SET status = "active", unsubscribed_at = NULL WHERE email = ?');
            $stmt->execute([$email]);
            $response['success'] = true;
            $response['status'] = 'reactivated';
            $response['title'] = 'Subscription Restored';
            $response['message'] = 'Welcome back! Your subscription has been reactivated.';
        }
    } else {
        // Add new subscriber
        $stmt = $pdo->prepare('INSERT INTO email_subscribers (email, first_name, last_name) VALUES (?, ?, ?)');
        $stmt->execute([$email, $first_name, $last_name]);
        
        // Send welcome email
        $firstName = $first_name ?: explode('@', $email)[0];
        $emailResult = sendSubscriptionConfirmation($email, $firstName);
        
        $response['success'] = true;
        $response['status'] = 'subscribed';
        $response['title'] = 'Subscription Confirmed';
        if ($emailResult) {
            $response['message'] = 'Thank you for subscribing! Please check your email for confirmation.';
        } else {
            $response['message'] = 'Thank you for subscribing! (Email confirmation may be delayed)';
            error_log("Failed to send subscription confirmation email to: " . $email);
        }

        sendAdminNotification('Newsletter Subscription', [
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
        ]);
    }
    
} catch (Exception $e) {
    error_log("Subscription error: " . $e->getMessage());
    $response['title'] = 'Unable To Subscribe';
    $response['message'] = 'An error occurred. Please try again later.';
}

echo json_encode($response); 
