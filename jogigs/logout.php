<?php
// JLM Freelancer Web App - Logout
session_start();

// Include configuration, functions, and authentication functions
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Logout user
logout_user();

// Redirect to login page with success message
redirect_with_message('index.php', 'You have been logged out successfully', 'success');
?> 