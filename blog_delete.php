<?php
session_start();
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/config/config.php';
$admin_user = require_admin_auth();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare('DELETE FROM blog_posts WHERE id = ? AND author_id = ?');
$stmt->execute([$id, $admin_user['id']]);
header('Location: blog_manage.php');
exit; 
