<?php
/**
 * Student - Cancel Rental Request
 */
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireRole(['student']);

$conn = getConnection();
$studentId = getUserId();
$requestId = intval($_GET['id'] ?? 0);

// Verify ownership and pending status
$stmt = $conn->prepare("SELECT id FROM rental_requests WHERE id = ? AND student_id = ? AND overall_status = 'pending'");
$stmt->bind_param("ii", $requestId, $studentId);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    
    $updateStmt = $conn->prepare("UPDATE rental_requests SET overall_status = 'cancelled' WHERE id = ?");
    $updateStmt->bind_param("i", $requestId);
    $updateStmt->execute();
    $updateStmt->close();
    
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Request cancelled successfully.'];
} else {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Request not found or cannot be cancelled.'];
}

$conn->close();
header("Location: dashboard.php");
exit();
?>
