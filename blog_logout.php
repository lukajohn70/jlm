<?php
/**
 * Public Blog Logout Handler
 */

declare(strict_types=1);

session_start();

// Clear blog reader session
unset($_SESSION['blog_user_id']);
unset($_SESSION['blog_user_name']);
unset($_SESSION['blog_user_email']);

// Also clear admin session — so admins testing the blog
// are fully signed out and can test guest/reader experience.
// This is intentional: if you need the admin panel back, log in via admin_login.php.
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_2fa_verified']);
unset($_SESSION['pending_2fa_user_id']);

$redirect = $_GET['redirect'] ?? 'blog.php';

// Sanitize redirect to prevent open redirect — only allow relative paths or same-site blog URLs
$redirect = ltrim($redirect, '/');
if (!preg_match('/^(blog[_\w]*\.php|#)/', $redirect)) {
    $redirect = 'blog.php';
}

header('Location: ' . $redirect);
exit;
