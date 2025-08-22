<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/email_utils.php';

$success = false;
$error = '';
$formData = [];

// Check if form was successfully submitted (for clearing)
if (isset($_SESSION['registration_success']) && $_SESSION['registration_success']) {
    $success = true;
    unset($_SESSION['registration_success']);
    // Clear form data
    $formData = [];
} else {
    // Get form data for repopulation (if there were errors)
    $formData = $_POST;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'freelancer';
    if (!$name || !$email || !$password || !in_array($role, ['admin', 'freelancer'])) {
        $error = 'Please fill all fields correctly.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $email, $hash, $role]);
            
            // Send email notifications
            $emailResult = handleRegistrationEmail($name, $email, $role);
            
            // Log email results for debugging
            if (!$emailResult['admin'] || !$emailResult['user']) {
                error_log("Registration email notification failed - Admin: " . ($emailResult['admin'] ? 'success' : 'failed') . ", User: " . ($emailResult['user'] ? 'success' : 'failed'));
            }
            
            // Set success flag and clear form
            $_SESSION['registration_success'] = true;
            $success = true;
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - JLM</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <h1>Register</h1>
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success">Registration successful! You can now login.</div><?php endif; ?>
    <form method="post">
        <label>Name: <input type="text" name="name" required value="<?php echo isset($formData['name']) ? htmlspecialchars($formData['name']) : ''; ?>"></label><br>
        <label>Email: <input type="email" name="email" required value="<?php echo isset($formData['email']) ? htmlspecialchars($formData['email']) : ''; ?>"></label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        <label>Role:
            <select name="role">
                <option value="freelancer" <?php echo (isset($formData['role']) && $formData['role'] === 'freelancer') ? 'selected' : ''; ?>>Freelancer</option>
                <option value="admin" <?php echo (isset($formData['role']) && $formData['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
            </select>
        </label><br>
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login</a></p>
</body>
</html> 