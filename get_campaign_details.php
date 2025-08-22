<?php
require_once __DIR__ . '/config/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid campaign ID']);
    exit;
}

$campaign_id = (int)$_GET['id'];

try {
    // Get campaign details
    $stmt = $pdo->prepare('SELECT * FROM email_campaigns WHERE id = ?');
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        echo json_encode(['success' => false, 'message' => 'Campaign not found']);
        exit;
    }
    
    // Get subscriber count
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM email_subscribers WHERE status = "active"');
    $stmt->execute();
    $subscriber_count = $stmt->fetchColumn();
    
    // Add subscriber count to campaign data
    $campaign['subscriber_count'] = $subscriber_count;
    
    echo json_encode([
        'success' => true,
        'campaign' => $campaign
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 