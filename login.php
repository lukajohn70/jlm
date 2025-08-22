<?php
session_start();
require_once __DIR__ . '/config/config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['role'] !== 'admin') {
            $error = 'Access denied: Admins only.';
        } else {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email'] = $user['email'];
            header('Location: admin_dashboard.php');
            exit;
        }
    } else {
        $error = 'Invalid email or password.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - JLM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-900 to-gray-800 flex items-center justify-center p-4">
    <div class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-[9999]">
        <a href="index.php" class="inline-flex items-center text-gray-300 hover:text-white text-sm font-medium bg-black/30 backdrop-blur-sm px-4 py-3 rounded-full border border-gray-600/50 hover:bg-black/40 transition-all duration-200 shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            Back to Home
        </a>
    </div>
    <div class="w-full max-w-md z-10">
        <div class="bg-white rounded-2xl shadow-2xl border-0 p-8">
            <div class="flex flex-col items-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-800 to-pink-600 rounded-2xl flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 18.341A8 8 0 1 0 4.572 18.34M12 15v2m0 4h.01" /></svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Admin Portal</h1>
                <p class="text-gray-500 text-sm mt-1">Access the JLM admin dashboard</p>
            </div>
            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-800"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            <form method="post" class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12H8m8 0a4 4 0 1 0-8 0 4 4 0 0 0 8 0z" /></svg>
                        <input id="email" name="email" type="email" required class="pl-10 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="admin@jlmcreative.ng" />
                    </div>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 18.341A8 8 0 1 0 4.572 18.34M12 15v2m0 4h.01" /></svg>
                        <input id="password" name="password" type="password" required class="pl-10 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter your password" />
                    </div>
                </div>
                <button type="submit" class="w-full py-2 px-4 bg-gradient-to-r from-blue-800 to-pink-600 text-white font-semibold rounded-lg shadow hover:from-blue-900 hover:to-pink-700 transition">Sign In</button>
            </form>
            <div class="mt-6 text-center text-sm text-gray-500">
                <p>Forgot your password?</p>
                <button class="text-blue-800 hover:text-pink-600 font-medium" disabled>Reset Password</button>
            </div>
        </div>
    </div>
</body>
</html> 