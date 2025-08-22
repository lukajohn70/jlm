<?php
// JLM Freelancer Web App - Job Actions Handler
session_start();

// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require authentication
require_auth();

// Get current user
$user = get_current_jogigs_user();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['assignment_id'])) {
        $assignment_id = (int)$_POST['assignment_id'];
        $action = $_POST['action'];
        
        try {
            switch ($action) {
                case 'accept_assignment':
                    $result = accept_job_assignment($assignment_id, $user['id']);
                    if ($result['success']) {
                        set_flash_message('Job assignment accepted successfully!', 'success');
                    } else {
                        set_flash_message('Failed to accept job: ' . $result['message'], 'error');
                    }
                    break;
                    
                case 'decline_assignment':
                    $result = decline_job_assignment($assignment_id, $user['id']);
                    if ($result['success']) {
                        set_flash_message('Job assignment declined successfully!', 'success');
                    } else {
                        set_flash_message('Failed to decline job: ' . $result['message'], 'error');
                    }
                    break;
                    
                case 'start_job':
                    $result = start_job($assignment_id, $user['id']);
                    if ($result['success']) {
                        set_flash_message('Job started successfully! You can now begin working.', 'success');
                    } else {
                        set_flash_message('Failed to start job: ' . $result['message'], 'error');
                    }
                    break;
                    
                case 'complete_job':
                    $result = complete_job($assignment_id, $user['id']);
                    if ($result['success']) {
                        set_flash_message('Job completed successfully! Payment will be processed soon.', 'success');
                    } else {
                        set_flash_message('Failed to complete job: ' . $result['message'], 'error');
                    }
                    break;
                    
                case 'backout_job':
                    $reason = $_POST['reason'] ?? 'No reason provided';
                    $result = apply_backout_penalty($assignment_id, $user['id'], $reason);
                    if ($result['success']) {
                        set_flash_message('Job backed out. A penalty of ' . format_currency($result['penalty_amount'], 'NGN') . ' has been applied.', 'warning');
                    } else {
                        set_flash_message('Failed to process backout: ' . $result['message'], 'error');
                    }
                    break;
                    
                default:
                    set_flash_message('Invalid action.', 'error');
                    break;
            }
            
        } catch (Exception $e) {
            error_log("Job action error: " . $e->getMessage());
            set_flash_message('An error occurred while processing your request.', 'error');
        }
        
        // Redirect back to dashboard
        header('Location: dashboard.php');
        exit;
    }
}

// If no valid action, redirect to dashboard
header('Location: dashboard.php');
exit;
?>


