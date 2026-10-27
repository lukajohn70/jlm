<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/email_utils.php';
require_once __DIR__ . '/includes/blog_helpers.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid admin email address.';
    } elseif (!$pdo) {
        $error = 'Database connection is unavailable.';
    } else {
        $stmt = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE email = ? AND role = "admin" LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && (int) ($user['is_active'] ?? 1) === 1) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + 1800);
            $resetUrl = jlm_base_url() . '/reset_password.php?token=' . rawurlencode($token) . '&email=' . rawurlencode($user['email']);

            $pdo->prepare('UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ?')->execute([$tokenHash, $expiresAt, $user['id']]);
            sendPasswordResetEmail($user['email'], $user['name'], $resetUrl);
        }

        $success = 'If that admin account exists, a password reset link has been sent.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - JLM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-900 to-gray-800 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-6">
                <img src="assets/images/jlm.png" alt="JLM Logo" class="h-14 w-auto mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Reset Admin Password</h1>
                <p class="text-sm text-gray-500 mt-2">Enter your admin email and we’ll send a reset link.</p>
            </div>
            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="post" class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Admin Email</label>
                    <input id="email" name="email" type="email" required class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter your admin email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-blue-800 to-pink-600 text-white font-semibold rounded-lg shadow hover:from-blue-900 hover:to-pink-700 transition">
                    Send Reset Link
                </button>
            </form>
            <div class="mt-6 text-center text-sm">
                <a href="login.php" class="text-blue-700 hover:text-pink-600 font-medium">Back to login</a>
            </div>
        </div>
    </div>
</body>
</html>
