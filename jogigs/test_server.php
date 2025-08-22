<?php
// Test server configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Server Configuration Test</h2>";

echo "<h3>PHP Version: " . phpversion() . "</h3>";
echo "<h3>Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'NOT SET') . "</h3>";
echo "<h3>Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'NOT SET') . "</h3>";
echo "<h3>Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'NOT SET') . "</h3>";
echo "<h3>Content Length: " . ($_SERVER['CONTENT_LENGTH'] ?? 'NOT SET') . "</h3>";

echo "<h3>All Server Variables:</h3>";
echo "<pre>" . print_r($_SERVER, true) . "</pre>";

echo "<h3>POST Data:</h3>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

echo "<h3>GET Data:</h3>";
echo "<pre>" . print_r($_GET, true) . "</pre>";

echo "<h3>REQUEST Data:</h3>";
echo "<pre>" . print_r($_REQUEST, true) . "</pre>";

echo "<hr>";
echo "<h3>Test Form:</h3>";
?>
<form method="POST" action="">
    <input type="text" name="test_field" value="test_value"><br><br>
    <button type="submit" name="test_submit">Test Submit</button>
</form>

