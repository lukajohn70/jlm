<?php
/**
 * Public Blog Authentication Page
 */

declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/email_utils.php';
require_once __DIR__ . '/includes/blog_auth_helpers.php';
require_once __DIR__ . '/includes/blog_schema_migrate.php';
require_once __DIR__ . '/includes/functions.php';

// Run schema migration (creates blog_user_otp if needed)
jlm_ensure_blog_posts_schema($pdo ?? null);

// Sanitize redirect — strip host prefix if it's a full URL, keep only the path+query
$rawRedirect = trim($_GET['redirect'] ?? $_POST['redirect'] ?? '');
if ($rawRedirect !== '' && filter_var($rawRedirect, FILTER_VALIDATE_URL)) {
    $parts = parse_url($rawRedirect);
    $rawRedirect = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
}
if ($rawRedirect === '' || $rawRedirect === '/') {
    $rawRedirect = 'blog.php';
}
$redirect = $rawRedirect;

if (jlm_get_blog_user() !== null) {
    header('Location: ' . $redirect);
    exit;
}

$error     = '';
$success   = '';
$activeTab = 'login'; // 'login' | 'register' | 'verify'
$otpPendingEmail = $_SESSION['blog_otp_pending_email'] ?? '';

// OAuth callback errors
if (isset($_SESSION['oauth_error'])) {
    $error = $_SESSION['oauth_error'];
    unset($_SESSION['oauth_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Step 1: Registration form → generate & send OTP ─────────────────────
    if ($action === 'register') {
        $activeTab = 'register';
        $name            = trim($_POST['name'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            // Check if email already exists in blog_users
            $stmt = $pdo->prepare('SELECT id FROM blog_users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'An account with this email already exists. Please sign in instead.';
            } else {
                // Generate OTP & store pending verification
                $otpCode   = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $otpHash   = password_hash($password, PASSWORD_DEFAULT);
                $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes

                // Upsert: replace any previous OTP for this email
                $del = $pdo->prepare('DELETE FROM blog_user_otp WHERE email = ?');
                $del->execute([$email]);
                $ins = $pdo->prepare('INSERT INTO blog_user_otp (email, name, password_hash, otp_code, expires_at) VALUES (?, ?, ?, ?, ?)');
                $ins->execute([$email, $name, $otpHash, $otpCode, $expiresAt]);

                // Send OTP email
                sendBlogOtpEmail($email, $name, $otpCode);

                $_SESSION['blog_otp_pending_email'] = $email;
                $otpPendingEmail = $email;
                $activeTab = 'verify';
                $success   = 'A 6-digit code was sent to <strong>' . htmlspecialchars($email) . '</strong>. Enter it below to create your account.';
            }
        }

    // ── Step 2: OTP verification → create account ────────────────────────────
    } elseif ($action === 'verify_otp') {
        $activeTab       = 'verify';
        $otpPendingEmail = trim($_SESSION['blog_otp_pending_email'] ?? '');
        $submittedCode   = trim(implode('', $_POST['otp'] ?? []));

        if ($otpPendingEmail === '' || strlen($submittedCode) !== 6) {
            $error = 'Invalid verification attempt. Please register again.';
            $activeTab = 'register';
        } else {
            $otpStmt = $pdo->prepare('SELECT * FROM blog_user_otp WHERE email = ? ORDER BY created_at DESC LIMIT 1');
            $otpStmt->execute([$otpPendingEmail]);
            $otpRow = $otpStmt->fetch(PDO::FETCH_ASSOC);

            if (!$otpRow) {
                $error = 'No pending verification found. Please register again.';
                $activeTab = 'register';
                unset($_SESSION['blog_otp_pending_email']);
            } elseif (strtotime($otpRow['expires_at']) < time()) {
                $error = 'Your verification code has expired. Please register again to get a new one.';
                $activeTab = 'register';
                $pdo->prepare('DELETE FROM blog_user_otp WHERE email = ?')->execute([$otpPendingEmail]);
                unset($_SESSION['blog_otp_pending_email']);
            } elseif ($submittedCode !== $otpRow['otp_code']) {
                $error = 'Incorrect code. Please try again.';
            } else {
                // OTP correct — create account
                $insertUser = $pdo->prepare('INSERT INTO blog_users (name, email, password_hash) VALUES (?, ?, ?)');
                if ($insertUser->execute([$otpRow['name'], $otpPendingEmail, $otpRow['password_hash']])) {
                    $userId = (int) $pdo->lastInsertId();

                    // Clean up OTP
                    $pdo->prepare('DELETE FROM blog_user_otp WHERE email = ?')->execute([$otpPendingEmail]);
                    unset($_SESSION['blog_otp_pending_email']);

                    // Log in
                    $_SESSION['blog_user_id']    = $userId;
                    $_SESSION['blog_user_name']  = $otpRow['name'];
                    $_SESSION['blog_user_email'] = $otpPendingEmail;
                    $_SESSION['blog_user_avatar'] = '';

                    // Auto-subscribe to newsletter
                    jlm_blog_subscribe_to_newsletter($pdo, $otpPendingEmail, $otpRow['name']);

                    header('Location: ' . $redirect);
                    exit;
                } else {
                    $error = 'Something went wrong creating your account. Please try again.';
                }
            }
        }

    // ── Resend OTP ───────────────────────────────────────────────────────────
    } elseif ($action === 'resend_otp') {
        $activeTab       = 'verify';
        $otpPendingEmail = trim($_SESSION['blog_otp_pending_email'] ?? '');

        if ($otpPendingEmail === '') {
            $error = 'Session expired. Please register again.';
            $activeTab = 'register';
        } else {
            $otpStmt = $pdo->prepare('SELECT * FROM blog_user_otp WHERE email = ? ORDER BY created_at DESC LIMIT 1');
            $otpStmt->execute([$otpPendingEmail]);
            $otpRow = $otpStmt->fetch(PDO::FETCH_ASSOC);

            if (!$otpRow) {
                $error = 'No pending registration found. Please register again.';
                $activeTab = 'register';
            } else {
                $newCode   = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expiresAt = date('Y-m-d H:i:s', time() + 600);
                $pdo->prepare('UPDATE blog_user_otp SET otp_code = ?, expires_at = ? WHERE email = ?')
                    ->execute([$newCode, $expiresAt, $otpPendingEmail]);

                sendBlogOtpEmail($otpPendingEmail, $otpRow['name'], $newCode);
                $success = 'A new code was sent to <strong>' . htmlspecialchars($otpPendingEmail) . '</strong>.';
            }
        }

    // ── Login ────────────────────────────────────────────────────────────────
    } elseif ($action === 'login') {
        $activeTab = 'login';
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'All fields are required.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM blog_users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, (string) ($user['password_hash'] ?? ''))) {
                $_SESSION['blog_user_id']     = (int) $user['id'];
                $_SESSION['blog_user_name']   = $user['name'];
                $_SESSION['blog_user_email']  = $user['email'];
                $_SESSION['blog_user_avatar'] = $user['avatar_url'] ?? '';

                // Auto-subscribe to newsletter
                jlm_blog_subscribe_to_newsletter($pdo, $user['email'], $user['name']);

                header('Location: ' . $redirect);
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Blog Engagement Portal | JLM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo asset_url('assets/images/jlm.png'); ?>" />
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        outfit: ['Outfit', 'sans-serif'],
                        sans: ['Plus Jakarta Sans', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at top right, rgba(228, 48, 109, 0.15), transparent 45%),
                        radial-gradient(circle at bottom left, rgba(27, 34, 153, 0.15), transparent 45%),
                        #0f172a;
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .form-input {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .form-input:focus {
            border-color: #e4306d;
            box-shadow: 0 0 0 2px rgba(228, 48, 109, 0.2);
            background: rgba(15, 23, 42, 0.8);
            outline: none;
        }
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 11px 16px;
            border-radius: 14px;
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
        }
        .social-btn:hover  { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,0,0,.35); }
        .social-btn:active { transform: translateY(0);     box-shadow: none; }
        .social-btn-google   { background: #fff;    color: #3c4043; border-color: rgba(255,255,255,.15); }
        .social-btn-facebook { background: #1877f2; color: #fff; }
        .social-btn-apple    { background: #000;    color: #fff; border-color: rgba(255,255,255,.12); }
        .divider {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(148,163,184,.5);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin: 20px 0;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,.08);
        }
        .social-btn-disabled {
            background: rgba(30,41,59,0.5);
            color: rgba(148,163,184,0.5);
            border-color: rgba(255,255,255,0.06);
            cursor: not-allowed;
            pointer-events: none;
        }
        .social-btn-disabled:hover { transform: none; box-shadow: none; }
        .soon-badge {
            margin-left: auto;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: 2px 7px;
            border-radius: 99px;
            background: rgba(255,255,255,.08);
            color: rgba(148,163,184,.6);
        }
        .otp-digit {
            width: 44px;
            height: 52px;
            padding: 0;
            font-size: 1.5rem;
            text-align: center;
            border-radius: 12px;
        }
        .otp-digit:focus {
            outline: none;
            border-color: #e4306d;
            box-shadow: 0 0 0 2px rgba(228,48,109,.35);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 text-slate-100 font-sans">

    <!-- Back link -->
    <div class="absolute top-6 left-6">
        <a href="<?php echo htmlspecialchars($redirect); ?>" 
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-800/60 border border-slate-700/50 hover:bg-slate-700/80 text-sm font-semibold text-slate-300 hover:text-white transition duration-200">
            <i class="fas fa-arrow-left"></i> Back to Blog
        </a>
    </div>

    <div class="w-full max-w-md my-12">
        <!-- Logo / Branding header -->
        <div class="flex flex-col items-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-br from-[#1b2299] to-[#e4306d] rounded-2xl flex items-center justify-center shadow-xl shadow-indigo-950/50 mb-4 hover:rotate-3 transition duration-300">
                <img src="<?php echo asset_url('assets/images/jlm.png'); ?>" alt="JLM logo" class="h-12 w-auto">
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight font-outfit text-white bg-clip-text bg-gradient-to-r from-white via-slate-100 to-slate-300">
                JLM Blog Portal
            </h1>
            <p class="text-slate-400 text-sm mt-2 text-center px-4">
                Join the conversation. Authenticate to like, comment, and connect with other readers.
            </p>
        </div>

        <!-- Glass Auth Container -->
        <div class="glass-card rounded-3xl shadow-2xl p-8 max-w-sm mx-auto">
            <!-- Tabs -->
            <div class="flex p-1 bg-slate-900/80 rounded-2xl mb-8 border border-slate-800">
                <button type="button" onclick="switchTab('login')" id="tab-login-btn"
                        class="flex-1 py-2.5 text-sm font-bold rounded-xl transition duration-200 <?php echo $activeTab === 'login' ? 'bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white shadow-md' : 'text-slate-400 hover:text-white'; ?>">
                    Sign In
                </button>
                <button type="button" onclick="switchTab('register')" id="tab-register-btn"
                        class="flex-1 py-2.5 text-sm font-bold rounded-xl transition duration-200 <?php echo $activeTab === 'register' ? 'bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white shadow-md' : 'text-slate-400 hover:text-white'; ?>">
                    Create Account
                </button>
            </div>

            <!-- Error Banner -->
            <?php if ($error): ?>
                <div class="mb-6 p-4 rounded-2xl border border-red-500/30 bg-red-950/40 text-red-200 text-sm flex items-start gap-3">
                    <i class="fas fa-exclamation-circle mt-0.5 flex-shrink-0 text-red-400"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Success Banner -->
            <?php if ($success): ?>
                <div class="mb-6 p-4 rounded-2xl border border-emerald-500/30 bg-emerald-950/40 text-emerald-200 text-sm flex items-start gap-3">
                    <i class="fas fa-check-circle mt-0.5 flex-shrink-0 text-emerald-400"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <!-- Social Login Buttons (hidden on verify tab) -->
            <?php
            // Checks config/oauth_config.php (and env var overrides)
            $googleOk   = jlm_oauth_is_configured('google');
            $oauthBase  = 'blog_oauth.php?redirect=' . urlencode($redirect) . '&provider=';
            ?>
            <div id="social-buttons" class="space-y-2.5 mb-2 <?php echo $activeTab === 'verify' ? 'hidden' : ''; ?>">

                <!-- Google -->
                <?php if ($googleOk): ?>
                <a href="<?php echo htmlspecialchars($oauthBase . 'google'); ?>" class="social-btn social-btn-google" id="btn-google-signin">
                <?php else: ?>
                <span class="social-btn social-btn-disabled" title="Google sign-in coming soon" id="btn-google-signin">
                <?php endif; ?>
                    <svg width="18" height="18" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path fill="<?php echo $googleOk ? '#EA4335' : '#64748b'; ?>" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                        <path fill="<?php echo $googleOk ? '#4285F4' : '#64748b'; ?>" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                        <path fill="<?php echo $googleOk ? '#FBBC05' : '#64748b'; ?>" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                        <path fill="<?php echo $googleOk ? '#34A853' : '#64748b'; ?>" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    </svg>
                    Continue with Google
                    <?php if (!$googleOk): ?><span class="soon-badge">Soon</span><?php endif; ?>
                <?php echo $googleOk ? '</a>' : '</span>'; ?>

            </div>

            <div id="social-divider" class="divider <?php echo $activeTab === 'verify' ? 'hidden' : ''; ?>">or</div>

            <!-- Login Form -->
            <form id="login-form" method="post" class="<?php echo $activeTab === 'login' ? '' : 'hidden'; ?> space-y-5">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

                <div>
                    <label for="login-email" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500"><i class="far fa-envelope"></i></span>
                        <input id="login-email" name="email" type="email" required autocomplete="email"
                               class="w-full form-input rounded-2xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-500"
                               placeholder="you@example.com">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="login-password" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Password</label>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500"><i class="fas fa-lock"></i></span>
                        <input id="login-password" name="password" type="password" required autocomplete="current-password"
                               class="w-full form-input rounded-2xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-500"
                               placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" 
                        class="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white font-bold text-sm tracking-wide hover:opacity-95 active:scale-[0.99] transition duration-150 shadow-lg shadow-pink-950/20">
                    Sign In
                </button>
            </form>

            <!-- Register Form -->
            <form id="register-form" method="post" class="<?php echo $activeTab === 'register' ? '' : 'hidden'; ?> space-y-5">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

                <div>
                    <label for="reg-name" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Full Name</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500"><i class="far fa-user"></i></span>
                        <input id="reg-name" name="name" type="text" required autocomplete="name"
                               class="w-full form-input rounded-2xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-500"
                               placeholder="John Doe">
                    </div>
                </div>

                <div>
                    <label for="reg-email" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500"><i class="far fa-envelope"></i></span>
                        <input id="reg-email" name="email" type="email" required autocomplete="email"
                               class="w-full form-input rounded-2xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-500"
                               placeholder="you@example.com">
                    </div>
                </div>

                <div>
                    <label for="reg-password" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500"><i class="fas fa-lock"></i></span>
                        <input id="reg-password" name="password" type="password" required autocomplete="new-password"
                               class="w-full form-input rounded-2xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-500"
                               placeholder="At least 6 characters">
                    </div>
                </div>

                <div>
                    <label for="reg-confirm" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Confirm Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500"><i class="fas fa-shield-alt"></i></span>
                        <input id="reg-confirm" name="confirm_password" type="password" required autocomplete="new-password"
                               class="w-full form-input rounded-2xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-500"
                               placeholder="Repeat password">
                    </div>
                </div>

                <button type="submit" 
                        class="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white font-bold text-sm tracking-wide hover:opacity-95 active:scale-[0.99] transition duration-150 shadow-lg shadow-pink-950/20">
                    Create Account
                </button>
            </form>

            <!-- OTP Verification Panel -->
            <div id="verify-panel" class="<?php echo $activeTab === 'verify' ? '' : 'hidden'; ?> space-y-6">
                <div class="text-center">
                    <div class="w-14 h-14 bg-gradient-to-br from-[#1b2299] to-[#e4306d] rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-indigo-950/50">
                        <i class="fas fa-envelope-open-text text-white text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-white font-outfit mb-1">Check your inbox</h2>
                    <p class="text-slate-400 text-sm">
                        Enter the 6-digit code sent to<br>
                        <strong class="text-slate-200"><?php echo htmlspecialchars($otpPendingEmail ?: '...'); ?></strong>
                    </p>
                </div>

                <!-- 6-digit OTP input form -->
                <form id="verify-form" method="post" class="space-y-5" onsubmit="collectOtp(this)">
                    <input type="hidden" name="action" value="verify_otp">
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                    <input type="hidden" name="otp[]" id="otp-combined" value="">

                    <div class="flex justify-center gap-2.5" id="otp-boxes">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"
                               id="otp-digit-<?php echo $i; ?>"
                               class="otp-digit w-11 h-13 text-center text-xl font-bold form-input rounded-xl text-white caret-pink-400"
                               autocomplete="one-time-code" <?php echo $i === 0 ? 'autofocus' : ''; ?>>
                        <?php endfor; ?>
                    </div>

                    <button type="submit"
                            class="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white font-bold text-sm tracking-wide hover:opacity-95 active:scale-[0.99] transition duration-150 shadow-lg shadow-pink-950/20">
                        <i class="fas fa-check-circle mr-2"></i>Verify & Create Account
                    </button>
                </form>

                <!-- Resend OTP -->
                <div class="text-center text-sm text-slate-400 space-y-2">
                    <p>Didn't receive a code?</p>
                    <form method="post" class="inline">
                        <input type="hidden" name="action" value="resend_otp">
                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                        <button type="submit" class="text-indigo-400 hover:text-indigo-300 font-semibold transition">
                            <i class="fas fa-redo-alt mr-1 text-xs"></i>Resend code
                        </button>
                    </form>
                    <span class="mx-2 text-slate-600">·</span>
                    <a href="blog_auth.php?redirect=<?php echo urlencode($redirect); ?>" class="text-slate-500 hover:text-slate-300 transition">Use a different email</a>
                </div>
            </div>

        </div>
    </div>

    <script>
        // ── Tab switching ────────────────────────────────────────────────────
        function switchTab(tab) {
            const loginForm    = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const verifyPanel  = document.getElementById('verify-panel');
            const loginBtn     = document.getElementById('tab-login-btn');
            const registerBtn  = document.getElementById('tab-register-btn');
            const socialBtns   = document.getElementById('social-buttons');
            const socialDiv    = document.getElementById('social-divider');

            const activeClass   = ['bg-gradient-to-r', 'from-[#1b2299]', 'to-[#e4306d]', 'text-white', 'shadow-md'];
            const inactiveClass = ['text-slate-400', 'hover:text-white'];

            // Hide all panels
            loginForm.classList.add('hidden');
            registerForm.classList.add('hidden');
            verifyPanel.classList.add('hidden');
            socialBtns.classList.remove('hidden');
            socialDiv.classList.remove('hidden');

            // Reset both tab buttons to inactive
            [loginBtn, registerBtn].forEach(b => {
                b.classList.remove(...activeClass);
                b.classList.add(...inactiveClass);
            });

            if (tab === 'login') {
                loginForm.classList.remove('hidden');
                loginBtn.classList.add(...activeClass);
                loginBtn.classList.remove(...inactiveClass);
            } else if (tab === 'register') {
                registerForm.classList.remove('hidden');
                registerBtn.classList.add(...activeClass);
                registerBtn.classList.remove(...inactiveClass);
            } else if (tab === 'verify') {
                verifyPanel.classList.remove('hidden');
                socialBtns.classList.add('hidden');
                socialDiv.classList.add('hidden');
                // Highlight neither tab button while verifying
            }
        }

        // ── OTP input UX: auto-advance, backspace, paste ─────────────────────
        document.addEventListener('DOMContentLoaded', function () {
            const digits = document.querySelectorAll('.otp-digit');
            if (!digits.length) return;

            digits.forEach((input, idx) => {
                input.addEventListener('input', () => {
                    input.value = input.value.replace(/[^0-9]/g, '').slice(-1);
                    if (input.value && idx < digits.length - 1) {
                        digits[idx + 1].focus();
                    }
                });
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !input.value && idx > 0) {
                        digits[idx - 1].focus();
                    }
                });
            });

            // Handle clipboard paste (e.g. copy-paste full 6-digit code)
            digits[0].addEventListener('paste', (e) => {
                e.preventDefault();
                const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                pasted.split('').forEach((ch, i) => {
                    if (digits[i]) digits[i].value = ch;
                });
                const lastFilled = Math.min(pasted.length, digits.length - 1);
                digits[lastFilled].focus();
            });
        });

        // Collect individual digit inputs into the hidden combined field
        function collectOtp(form) {
            const digits = form.querySelectorAll('.otp-digit');
            const combined = Array.from(digits).map(d => d.value).join('');
            form.querySelector('#otp-combined').value = combined;
            return true;
        }
    </script>
</body>
</html>
