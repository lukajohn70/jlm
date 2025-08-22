<?php
session_start();
require_once __DIR__ . '/config/config.php';
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// Get admin user ID from email
$admin_email = $_SESSION['admin_email'] ?? '';
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND role = "admin" LIMIT 1');
$stmt->execute([$admin_email]);
$admin_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin_user) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare('DELETE FROM blog_posts WHERE id = ? AND author_id = ?');
$stmt->execute([$id, $admin_user['id']]);
header('Location: blog_manage.php');
exit; 