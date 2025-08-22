<?php
// Clean session test - no output before session_start
session_start();

// Now we can output
echo "<h2>Clean Session Test</h2>";

echo "<h3>1. Session Information</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h3>2. Test Session Variables</h3>";
if (isset($_SESSION['test_var'])) {
    echo "Test variable exists: " . $_SESSION['test_var'] . "<br>";
} else {
    echo "Test variable does not exist<br>";
    $_SESSION['test_var'] = 'test_value_' . time();
    echo "Created test variable: " . $_SESSION['test_var'] . "<br>";
}

echo "<h3>3. Session Configuration</h3>";
echo "Session name: " . session_name() . "<br>";
echo "Session save path: " . session_save_path() . "<br>";

echo "<h3>4. Test Session Persistence</h3>";
echo "<p><a href='test_session_clean.php'>Refresh this page</a> to test session persistence</p>";
?>




