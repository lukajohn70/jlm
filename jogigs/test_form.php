<?php
// Test form submission
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Form Submission Test</h2>";

echo "<h3>Request Method: " . $_SERVER['REQUEST_METHOD'] . "</h3>";
echo "<h3>POST Data:</h3>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

echo "<h3>All Request Data:</h3>";
echo "<pre>" . print_r($_REQUEST, true) . "</pre>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Form submitted!</h3>";
    if (isset($_POST['login'])) {
        echo "<p>✅ Login button was clicked</p>";
        echo "<p>Email: " . ($_POST['email'] ?? 'NOT SET') . "</p>";
        echo "<p>Password: " . (isset($_POST['password']) ? 'SET' : 'NOT SET') . "</p>";
    } else {
        echo "<p>❌ Login button was NOT clicked</p>";
    }
} else {
    echo "<h3>No form submission yet</h3>";
}

echo "<hr>";
echo "<h3>Test Form:</h3>";
?>
<form method="POST" action="">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit" name="login">Test Login</button>
</form>

