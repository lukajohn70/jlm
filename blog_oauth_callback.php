<?php
/**
 * Blog OAuth Callback Handler
 * Handles redirect-back from Google / Facebook / Apple.
 *
 * Credentials are read from config/oauth_config.php.
 */

declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/email_utils.php';
require_once __DIR__ . '/includes/blog_helpers.php';
require_once __DIR__ . '/includes/blog_auth_helpers.php';
require_once __DIR__ . '/includes/functions.php';

/* ─────────────────────────────  Helpers  ───────────────────────────────── */

/** Abort with a user-friendly error redirected back to the auth page. */
function jlm_oauth_fail(string $message, string $redirect): never
{
    $_SESSION['oauth_error'] = $message;
    header('Location: blog_auth.php?redirect=' . urlencode($redirect));
    exit;
}

/** Simple HTTP GET that returns decoded JSON or null. */
function jlm_oauth_get(string $url, array $extraHeaders = []): ?array
{
    $headers = array_merge(['User-Agent: JLMBlog/1.0', 'Accept: application/json'], $extraHeaders);

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            error_log('[JLM OAuth GET curl error] ' . $err);
            $_SESSION['oauth_detailed_error'] = 'Network GET error: ' . $err;
            return null;
        }
        return json_decode($body, true) ?: null;
    }

    $ctx = stream_context_create([
        'http' => [
            'method'        => 'GET',
            'header'        => implode("\r\n", $headers),
            'timeout'       => 10,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]
    ]);
    $body = @file_get_contents($url, false, $ctx);
    return ($body !== false) ? (json_decode($body, true) ?: null) : null;
}

/** Simple HTTP POST (form-encoded) that returns decoded JSON or null. */
function jlm_oauth_post(string $url, array $data, array $extraHeaders = []): ?array
{
    $headers = array_merge([
        'Content-Type: application/x-www-form-urlencoded',
        'User-Agent: JLMBlog/1.0',
        'Accept: application/json',
    ], $extraHeaders);

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            error_log('[JLM OAuth POST curl error] ' . $err);
            $_SESSION['oauth_detailed_error'] = 'Network POST error: ' . $err;
            return null;
        }
        return json_decode($body, true) ?: null;
    }

    $ctx = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => implode("\r\n", $headers),
            'content'       => http_build_query($data),
            'timeout'       => 10,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]
    ]);
    $body = @file_get_contents($url, false, $ctx);
    return ($body !== false) ? (json_decode($body, true) ?: null) : null;
}

/** Decode a base64url-encoded JWT segment. */
function jlm_b64url_decode(string $input): string
{
    $pad = strlen($input) % 4;
    if ($pad) {
        $input .= str_repeat('=', 4 - $pad);
    }
    return base64_decode(strtr($input, '-_', '+/'));
}


/**
 * Find an existing blog_user by OAuth ID, or by email, or create a new one.
 */
function jlm_oauth_find_or_create(PDO $pdo, string $provider, string $oauthId, string $name, ?string $email, ?string $avatarUrl): int
{
    // 1. Match by provider + oauth_id
    $stmt = $pdo->prepare('SELECT id FROM blog_users WHERE oauth_provider = ? AND oauth_id = ? LIMIT 1');
    $stmt->execute([$provider, $oauthId]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = (int) $row['id'];
        // Update avatar URL if it changed
        if ($avatarUrl !== null) {
            $pdo->prepare('UPDATE blog_users SET avatar_url = ? WHERE id = ?')->execute([$avatarUrl, $id]);
        }
        return $id;
    }

    // 2. Match by email (link existing email/password account to social)
    if ($email !== null && $email !== '') {
        $stmt = $pdo->prepare('SELECT id FROM blog_users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (int) $row['id'];
            $pdo->prepare('UPDATE blog_users SET oauth_provider = ?, oauth_id = ?, avatar_url = ? WHERE id = ?')
                ->execute([$provider, $oauthId, $avatarUrl, $id]);
            return $id;
        }
    }

    // 3. Create new user
    $finalName = ($name !== '') ? $name : (($email !== null && $email !== '') ? strstr($email, '@', true) : 'User');
    $stmt = $pdo->prepare('INSERT INTO blog_users (name, email, oauth_provider, oauth_id, avatar_url) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$finalName, $email, $provider, $oauthId, $avatarUrl]);
    return (int) $pdo->lastInsertId();
}

/* ─────────────────────────────  Main flow  ─────────────────────────────── */

$provider = strtolower(trim($_GET['provider'] ?? $_POST['provider'] ?? ''));
$redirect = $_SESSION['oauth_redirect'] ?? 'blog.php';

// ── CSRF state check ─────────────────────────────────────────────────────
$receivedState = trim($_GET['state'] ?? $_POST['state'] ?? '');
$expectedState = $_SESSION['oauth_state'] ?? '';

if ($expectedState === '' || !hash_equals($expectedState, $receivedState)) {
    jlm_oauth_fail('Security check failed. Please try signing in again.', $redirect);
}
unset($_SESSION['oauth_state']);

$cfg         = jlm_oauth_cfg($provider);
$callbackBase = jlm_base_url() . '/blog_oauth_callback.php';

$oauthId = '';
$name    = '';
$email   = null;

switch ($provider) {

    /* ── Google ──────────────────────────────────────────────────────────── */
    case 'google': {
        $code = trim($_GET['code'] ?? '');
        if ($code === '') {
            jlm_oauth_fail('Google sign-in was cancelled or failed.', $redirect);
        }

        $tokens = jlm_oauth_post('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'redirect_uri'  => $callbackBase . '?provider=google',
            'grant_type'    => 'authorization_code',
        ]);

        if (empty($tokens['access_token'])) {
            error_log('[JLM OAuth Google] Token response: ' . json_encode($tokens));
            $details = '';
            if (!empty($tokens['error_description'])) {
                $details = ' (' . $tokens['error_description'] . ')';
            } elseif (!empty($tokens['error'])) {
                $details = ' (' . $tokens['error'] . ')';
            } elseif (!empty($_SESSION['oauth_detailed_error'])) {
                $details = ' (' . $_SESSION['oauth_detailed_error'] . ')';
                unset($_SESSION['oauth_detailed_error']);
            }
            jlm_oauth_fail('Could not complete Google sign-in.' . $details . ' Please try again.', $redirect);
        }

        $profile = jlm_oauth_get(
            'https://www.googleapis.com/oauth2/v2/userinfo',
            ['Authorization: Bearer ' . $tokens['access_token']]
        );

        if (empty($profile['id'])) {
            jlm_oauth_fail('Could not retrieve your Google profile.', $redirect);
        }

        $oauthId   = (string) $profile['id'];
        $name      = trim((string) ($profile['name'] ?? ''));
        $email     = strtolower(trim((string) ($profile['email'] ?? ''))) ?: null;
        $avatarUrl = trim((string) ($profile['picture'] ?? '')) ?: null;
        break;
    }

    default:
        header('Location: blog_auth.php?redirect=' . urlencode($redirect));
        exit;
}

/* ─────────────────────────  Persist user & login  ──────────────────────── */

try {
    $userId = jlm_oauth_find_or_create($pdo, $provider, $oauthId, $name, $email, $avatarUrl);

    // Refresh stored name/email/avatar from DB
    $userRow = $pdo->prepare('SELECT name, email, avatar_url FROM blog_users WHERE id = ? LIMIT 1');
    $userRow->execute([$userId]);
    $user = $userRow->fetch(PDO::FETCH_ASSOC);

    $_SESSION['blog_user_id']     = $userId;
    $_SESSION['blog_user_name']   = $user['name']  ?? $name;
    $_SESSION['blog_user_email']  = $user['email'] ?? $email ?? '';
    $_SESSION['blog_user_avatar'] = $user['avatar_url'] ?? '';

    // Auto-subscribe to newsletter (respects previous unsubscribes)
    $sessionEmail = $_SESSION['blog_user_email'];
    $sessionName  = $_SESSION['blog_user_name'];
    if ($sessionEmail !== '') {
        jlm_blog_subscribe_to_newsletter($pdo, $sessionEmail, $sessionName);
    }

    unset($_SESSION['oauth_redirect']);

    header('Location: ' . $redirect);
    exit;

} catch (Throwable $e) {
    error_log('[JLM OAuth] ' . $e->getMessage());
    jlm_oauth_fail('An unexpected error occurred. Please try again.', $redirect);
}
