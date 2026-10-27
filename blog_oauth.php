<?php
/**
 * Blog OAuth Initiator
 * Redirects the user to the chosen social-login provider.
 *
 * Credentials are read from config/oauth_config.php (or env var overrides).
 * See that file for setup instructions.
 */

declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/blog_helpers.php';
require_once __DIR__ . '/includes/blog_auth_helpers.php';
require_once __DIR__ . '/includes/functions.php';

// Already logged in?
if (jlm_get_blog_user() !== null) {
    $r = $_GET['redirect'] ?? 'blog.php';
    header('Location: ' . htmlspecialchars_decode($r));
    exit;
}

$provider  = strtolower(trim($_GET['provider'] ?? ''));
$redirect  = trim($_GET['redirect'] ?? 'blog.php');

// Persist intended destination across the OAuth round-trip
$_SESSION['oauth_redirect'] = $redirect;

// Generate & store CSRF state
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Single callback URL — provider is a query param so one URL covers all providers
$callbackUrl = jlm_base_url() . '/blog_oauth_callback.php';
$errorBase   = 'blog_auth.php?redirect=' . urlencode($redirect) . '&oauth_err=';

if (!jlm_oauth_is_configured($provider)) {
    header('Location: ' . $errorBase . urlencode($provider . '_not_configured'));
    exit;
}

$cfg = jlm_oauth_cfg($provider);

switch ($provider) {

    // ── Google ──────────────────────────────────────────────────────────────
    case 'google': {
        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'     => $cfg['client_id'],
            'redirect_uri'  => $callbackUrl . '?provider=google',
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ]);
        header('Location: ' . $url);
        exit;
    }

    default:
        header('Location: blog_auth.php?redirect=' . urlencode($redirect));
        exit;
}
