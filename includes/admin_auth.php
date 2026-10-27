<?php

function jlm_admin_session_bootstrap() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $cookieParams = session_get_cookie_params();
    $domain = $cookieParams['domain'] ?? '';

    // Ensure the session cookie is valid across the entire site (fixes redirects back to login on some setups).
    $params = [
        'lifetime' => $cookieParams['lifetime'] ?? 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    if ($domain !== '') {
        $params['domain'] = $domain;
    }
    session_set_cookie_params($params);
}

function jlm_start_admin_session($user) {
    jlm_admin_session_bootstrap();
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = (int) $user['id'];
    $_SESSION['admin_email'] = $user['email'];
    $_SESSION['admin_2fa_verified'] = true;
    $_SESSION['admin_last_activity'] = time();
    unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_email'], $_SESSION['pending_2fa_name']);
    session_write_close();
}

function require_admin_auth() {
    jlm_admin_session_bootstrap();
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $hasSession = !empty($_SESSION['admin_logged_in'])
        && !empty($_SESSION['admin_id'])
        && !empty($_SESSION['admin_email'])
        && !empty($_SESSION['admin_2fa_verified']);

    if (!$hasSession) {
        session_unset();
        session_destroy();
        header('Location: login.php?reason=missing_session');
        exit;
    }

    $maxIdleSeconds = 7200;
    $lastSeen = (int) ($_SESSION['admin_last_activity'] ?? 0);
    if ($lastSeen > 0 && (time() - $lastSeen) > $maxIdleSeconds) {
        session_unset();
        session_destroy();
        header('Location: login.php?reason=session_expired&expired=1');
        exit;
    }

    require_once __DIR__ . '/../config/config.php';

    $pdo = $GLOBALS['pdo'] ?? null;
    if (!$pdo instanceof PDO) {
        session_unset();
        session_destroy();
        header('Location: login.php?reason=db_unavailable');
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, email, role, is_active FROM users WHERE id = ? AND email = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['admin_id'], $_SESSION['admin_email']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || $admin['role'] !== 'admin' || (isset($admin['is_active']) && !$admin['is_active'])) {
        session_unset();
        session_destroy();
        header('Location: login.php?reason=invalid_admin');
        exit;
    }

    $_SESSION['admin_last_activity'] = time();

    return $admin;
}
