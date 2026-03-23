<?php
/**
 * Subscribe/Unsubscribe to Equipment Availability Notifications
 */
require_once 'includes/auth.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit();
}

$equipmentId = intval($_POST['equipment_id'] ?? 0);
$action = sanitize($_POST['action'] ?? '');
$userId = getUserId();

if ($equipmentId <= 0 || !in_array($action, ['subscribe', 'unsubscribe'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$conn = getConnection();

if ($action === 'subscribe') {
    $stmt = $conn->prepare("INSERT IGNORE INTO item_subscriptions (user_id, equipment_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $userId, $equipmentId);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Subscribed successfully.']);
} else {
    $stmt = $conn->prepare("DELETE FROM item_subscriptions WHERE user_id = ? AND equipment_id = ?");
    $stmt->bind_param("ii", $userId, $equipmentId);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Unsubscribed successfully.']);
}

$conn->close();
?>
