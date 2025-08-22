<?php
// Test session persistence between files
session_start();

echo "<h2>Session Persistence Test</h2>";

// Check if we have session data
if (isset($_SESSION['user_id'])) {
    echo "<h3>✅ Session Found</h3>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "User Email: " . $_SESSION['user_email'] . "<br>";
    echo "Login Time: " . $_SESSION['login_time'] . "<br>";
    
    echo "<h3>Testing Dashboard Access</h3>";
    echo "<a href='dashboard.php' target='_blank'>Open Dashboard</a><br>";
    
} else {
    echo "<h3>❌ No Session Found</h3>";
    echo "<p>You need to login first.</p>";
    echo "<a href='index.php'>Go to Login Page</a><br>";
}

echo "<h3>Current Session Data</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h3>Session Configuration</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Name: " . session_name() . "<br>";
echo "Session Save Path: " . session_save_path() . "<br>";
echo "Session Cookie Params: <pre>" . print_r(session_get_cookie_params(), true) . "</pre>";
?>
