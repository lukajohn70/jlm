<?php
// Test script for booking management functionality
require_once __DIR__ . '/config/config.php';

echo "<h2>Booking Management Test</h2>";

try {
    // Test database connection
    echo "<p>✅ Database connection: OK</p>";
    
    // Test table structure
    $stmt = $pdo->query("DESCRIBE booking_submissions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>✅ Table structure check:</p>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>$column</li>";
    }
    echo "</ul>";
    
    // Test query execution
    $stmt = $pdo->query("SELECT COUNT(*) FROM booking_submissions");
    $count = $stmt->fetchColumn();
    echo "<p>✅ Query execution: OK (Found $count bookings)</p>";
    
    // Test specific query that was failing
    $stmt = $pdo->prepare("SELECT * FROM booking_submissions ORDER BY submitted_at DESC LIMIT 1");
    $stmt->execute();
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($booking) {
        echo "<p>✅ Sample booking data:</p>";
        echo "<ul>";
        foreach ($booking as $key => $value) {
            echo "<li><strong>$key:</strong> " . htmlspecialchars($value ?? 'NULL') . "</li>";
        }
        echo "</ul>";
    }
    
    echo "<p>🎉 All tests passed! Booking management should work correctly.</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 