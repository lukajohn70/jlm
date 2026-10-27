<?php
/**
 * Blog Authentication Helpers
 */

declare(strict_types=1);

/**
 * Returns the currently authenticated blog user (either reader or admin), or null if not logged in.
 *
 * @return array|null The user details or null
 */
function jlm_get_blog_user(): ?array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 1. Check if logged in as a public blog reader
    if (!empty($_SESSION['blog_user_id'])) {
        return [
            'id' => (int) $_SESSION['blog_user_id'],
            'name' => $_SESSION['blog_user_name'],
            'email' => $_SESSION['blog_user_email'],
            'avatar_url' => $_SESSION['blog_user_avatar'] ?? null,
            'type' => 'reader'
        ];
    }
    
    // 2. Check if logged in as a JLM Admin
    if (!empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_id'])) {
        global $pdo;
        if ($pdo) {
            try {
                $stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([(int) $_SESSION['admin_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    return [
                        'id' => (int) $_SESSION['admin_id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'type' => 'admin'
                    ];
                }
            } catch (Throwable $e) {
                error_log('Error loading admin details for blog: ' . $e->getMessage());
            }
        }
        return [
            'id' => (int) $_SESSION['admin_id'],
            'name' => 'Admin',
            'email' => $_SESSION['admin_email'] ?? '',
            'type' => 'admin'
        ];
    }
    
    return null;
}

/**
 * Gating function to require authentication, redirecting to login page if guest.
 *
 * @param string $redirectUrl URL to return to after successful login
 */
function jlm_require_blog_auth(string $redirectUrl): void {
    if (jlm_get_blog_user() === null) {
        header('Location: blog_auth.php?redirect=' . urlencode($redirectUrl));
        exit;
    }
}

/* ──────────────────────────────────────────────────────────────────────────
 * OAuth credential helpers
 * Reads from config/oauth_config.php; env vars override if set.
 * ──────────────────────────────────────────────────────────────────────────*/

/**
 * Returns the credential array for a given provider from config/oauth_config.php.
 * Merges env-var overrides so both approaches work.
 *
 * @param string $provider  'google' | 'facebook' | 'apple'
 * @return array            Credential key→value pairs (empty strings = not configured)
 */
function jlm_oauth_cfg(string $provider): array
{
    static $all = null;
    if ($all === null) {
        $cfgFile = dirname(__DIR__) . '/config/oauth_config.php';
        $all = file_exists($cfgFile) ? (require $cfgFile) : [];
    }

    $cfg = $all[$provider] ?? [];

    // Allow env-var overrides (server SetEnv / shell export)
    $envOverrides = [
        'google'   => ['client_id' => 'JLM_GOOGLE_CLIENT_ID',   'client_secret' => 'JLM_GOOGLE_CLIENT_SECRET'],
        'facebook' => ['app_id'    => 'JLM_FACEBOOK_APP_ID',    'app_secret'    => 'JLM_FACEBOOK_APP_SECRET'],
        'apple'    => ['client_id' => 'JLM_APPLE_CLIENT_ID'],
    ];
    foreach ($envOverrides[$provider] ?? [] as $key => $envVar) {
        $val = getenv($envVar);
        if ($val !== false && $val !== '') {
            $cfg[$key] = $val;
        }
    }

    return $cfg;
}

/**
 * Returns true if the given OAuth provider has credentials configured.
 */
function jlm_oauth_is_configured(string $provider): bool
{
    $cfg = jlm_oauth_cfg($provider);
    $keys = [
        'google'   => 'client_id',
        'facebook' => 'app_id',
        'apple'    => 'client_id',
    ];
    $primaryKey = $keys[$provider] ?? 'client_id';
    return !empty($cfg[$primaryKey]);
}

/**
 * Silently subscribes a blog user to the newsletter when they sign in or register.
 * - If already active → no change (idempotent).
 * - If previously unsubscribed → do NOT re-add (honour opt-out).
 * - If new → INSERT as active and send a subscription confirmation email.
 *
 * @param PDO    $pdo
 * @param string $email
 * @param string $name   Full name; first token used as first_name
 */
function jlm_blog_subscribe_to_newsletter(PDO $pdo, string $email, string $name): void {
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    try {
        $check = $pdo->prepare('SELECT status FROM email_subscribers WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        $row = $check->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Already in the list — do not alter status (respect unsubscribe)
            return;
        }

        // New subscriber — extract first name
        $firstName = trim((string) $name);
        if ($firstName !== '') {
            $parts = preg_split('/\s+/', $firstName);
            $firstName = (string) ($parts[0] ?? $firstName);
        }

        $insert = $pdo->prepare(
            "INSERT INTO email_subscribers (email, first_name, status) VALUES (?, ?, 'active')"
        );
        $insert->execute([$email, $firstName ?: null]);

        // Send welcome email — fire and forget (errors are logged, not thrown)
        try {
            if (function_exists('sendSubscriptionConfirmation')) {
                sendSubscriptionConfirmation($email, $firstName);
            }
        } catch (Throwable $e) {
            error_log('[JLM Blog Subscribe] Welcome email failed: ' . $e->getMessage());
        }
    } catch (Throwable $e) {
        error_log('[JLM Blog Subscribe] ' . $e->getMessage());
    }
}
