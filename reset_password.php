<?php
session_start();
require_once __DIR__ . '/config/config.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$email = trim($_GET['email'] ?? $_POST['email'] ?? '');
$error = '';
$success = '';
$user = null;

if ($pdo && $token !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $tokenHash = hash('sha256', $token);
    $stmt = $pdo->prepare('
        SELECT id, name, email, reset_token_expires_at
        FROM users
        WHERE email = ? AND role = "admin" AND reset_token_hash = ?
        LIMIT 1
    ');
    $stmt->execute([$email, $tokenHash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (!$pdo || !$user) {
        $error = 'This reset link is invalid or has already been used.';
    } elseif (empty($user['reset_token_expires_at']) || strtotime($user['reset_token_expires_at']) < time()) {
        $error = 'This reset link has expired. Request a new one.';
    } elseif (strlen($password) < 8) {
        $error = 'Use a password with at least 8 characters.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Passwords do not match.';
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('
            UPDATE users
            SET password_hash = ?, reset_token_hash = NULL, reset_token_expires_at = NULL, two_factor_code = NULL, two_factor_expires_at = NULL
            WHERE id = ?
        ');
        $stmt->execute([$passwordHash, $user['id']]);
        $success = 'Your password has been reset successfully. You can sign in now.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Choose New Password - JLM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-900 to-gray-800 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-6">
                <img src="assets/images/jlm.png" alt="JLM Logo" class="h-14 w-auto mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Choose a New Password</h1>
                <p class="text-sm text-gray-500 mt-2">Set a new password for your JLM admin account.</p>
            </div>
            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800"><?php echo htmlspecialchars($success); ?></div>
                <div class="text-center text-sm">
                    <a href="login.php" class="text-blue-700 hover:text-pink-600 font-medium">Return to login</a>
                </div>
            <?php elseif (!$user || empty($user['reset_token_expires_at']) || strtotime($user['reset_token_expires_at']) < time()): ?>
                <div class="text-center text-sm">
                    <a href="forgot_password.php" class="text-blue-700 hover:text-pink-600 font-medium">Request a new reset link</a>
                </div>
            <?php else: ?>
                <form method="post" class="space-y-4">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input id="password" name="password" type="password" required class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Create a strong password">
                    </div>
                    <div>
                        <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input id="password_confirm" name="password_confirm" type="password" required class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Repeat your new password">
                    </div>
                    <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-blue-800 to-pink-600 text-white font-semibold rounded-lg shadow hover:from-blue-900 hover:to-pink-700 transition">
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
