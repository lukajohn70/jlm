<?php
require_once __DIR__ . '/includes/admin_auth.php';
jlm_admin_session_bootstrap();
session_start();
require_once __DIR__ . '/config/config.php';

if (empty($_SESSION['pending_2fa_user_id']) || empty($_SESSION['pending_2fa_email'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$email = $_SESSION['pending_2fa_email'];
$userId = (int) $_SESSION['pending_2fa_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = (string) ($_POST['code'] ?? '');
    $code = preg_replace('/\D+/', '', $code);
    $code = str_pad(substr($code, 0, 6), 6, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role = "admin" LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = 'Verification session is invalid. Please sign in again.';
    } elseif (empty($user['two_factor_code']) || empty($user['two_factor_expires_at'])) {
        $error = 'No active verification code was found. Please sign in again.';
    } elseif (strtotime($user['two_factor_expires_at']) < time()) {
        $error = 'Your verification code has expired. Please sign in again.';
    } else {
        $storedCode = (string) ($user['two_factor_code'] ?? '');
        $storedCode = preg_replace('/\D+/', '', $storedCode);
        $storedCode = str_pad(substr($storedCode, 0, 6), 6, '0', STR_PAD_LEFT);
    }

    if (!$error && !hash_equals($storedCode, $code)) {
        $error = 'Invalid verification code.';
    } elseif (!$error) {
        $pdo->prepare('UPDATE users SET two_factor_code = NULL, two_factor_expires_at = NULL, last_login_at = NOW() WHERE id = ?')->execute([$userId]);
        jlm_start_admin_session($user);
        header('Location: admin_dashboard.php');
        exit;
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Sign-In - JLM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-900 to-gray-800 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-6">
                <img src="assets/images/jlm.png" alt="JLM Logo" class="h-14 w-auto mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Verify Your Sign-In</h1>
                <p class="text-sm text-gray-500 mt-2">A 6-digit code was sent to <?php echo htmlspecialchars($email); ?>.</p>
            </div>
            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="post" class="space-y-4">
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
                    <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required class="w-full border border-gray-300 rounded-lg px-4 py-3 text-center tracking-[0.35em] text-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="000000">
                </div>
                <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-blue-800 to-pink-600 text-white font-semibold rounded-lg shadow hover:from-blue-900 hover:to-pink-700 transition">
                    Verify and Continue
                </button>
            </form>
            <div class="mt-6 text-center text-sm">
                <a href="login.php" class="text-blue-700 hover:text-pink-600 font-medium">Back to login</a>
            </div>
        </div>
    </div>
</body>
</html>
