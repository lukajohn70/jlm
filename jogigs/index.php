<?php
// JLM Freelancer Web App - Main Entry Point

// Prevent any output before session configuration
ob_start();

// Session Configuration (must be at the very beginning)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Lax');

// Start session
session_start();

// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is already logged in
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit();
}

// Redirect to landing page for non-logged-in users
header('Location: landing.php');
            exit();
?> 