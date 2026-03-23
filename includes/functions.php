<?php
/**
 * Helper Functions
 * Common utility functions used throughout the application
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Send email notification
 */
function sendEmail($to, $subject, $message) {
    $conn = getConnection();
    
    // Log the email
    $stmt = $conn->prepare("INSERT INTO email_logs (recipient_email, subject, message, status) VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param("sss", $to, $subject, $message);
    $stmt->execute();
    $logId = $conn->insert_id;
    $stmt->close();
    
    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>' . "\r\n";
    
    // Attempt to send
    $sent = @mail($to, $subject, $message, $headers);
    
    // Update log status
    $status = $sent ? 'sent' : 'failed';
    $stmt = $conn->prepare("UPDATE email_logs SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $logId);
    $stmt->execute();
    $stmt->close();
    
    $conn->close();
    
    return $sent;
}

/**
 * Send approval notification email
 */
function sendApprovalEmail($requestId) {
    $conn = getConnection();
    
    $stmt = $conn->prepare("
        SELECT rr.*, u.email as student_email, u.first_name as student_name,
               e.name as equipment_name,
               (SELECT email FROM users WHERE role = 'admin' LIMIT 1) as admin_email
        FROM rental_requests rr
        JOIN users u ON rr.student_id = u.id
        JOIN equipment e ON rr.equipment_id = e.id
        WHERE rr.id = ?
    ");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    if ($request) {
        $subject = "Rental Request Approved - " . $request['equipment_name'];
        $message = "
        <html>
        <body>
            <h2>Rental Request Approved</h2>
            <p>Dear {$request['student_name']},</p>
            <p>Your rental request for <strong>{$request['equipment_name']}</strong> has been approved by both the instructor and department chair.</p>
            <p>Please visit the equipment room to check out your item.</p>
            <p><strong>Due Date:</strong> {$request['due_date']}</p>
            <p>Thank you,<br>Rental Inventory System</p>
        </body>
        </html>
        ";
        
        // Send to student
        sendEmail($request['student_email'], $subject, $message);
        
        // Send to admin
        if ($request['admin_email']) {
            $adminMessage = str_replace("Dear {$request['student_name']}", "Dear Administrator", $message);
            $adminMessage = str_replace("Your rental request", "A rental request by {$request['student_name']}", $adminMessage);
            sendEmail($request['admin_email'], $subject, $adminMessage);
        }
    }
}

/**
 * Send rejection notification email
 */
function sendRejectionEmail($requestId, $rejectedBy, $reason) {
    $conn = getConnection();
    
    $stmt = $conn->prepare("
        SELECT rr.*, u.email as student_email, u.first_name as student_name,
               e.name as equipment_name,
               (SELECT email FROM users WHERE role = 'admin' LIMIT 1) as admin_email
        FROM rental_requests rr
        JOIN users u ON rr.student_id = u.id
        JOIN equipment e ON rr.equipment_id = e.id
        WHERE rr.id = ?
    ");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    if ($request) {
        $subject = "Rental Request Rejected - " . $request['equipment_name'];
        $message = "
        <html>
        <body>
            <h2>Rental Request Rejected</h2>
            <p>Dear {$request['student_name']},</p>
            <p>Your rental request for <strong>{$request['equipment_name']}</strong> has been rejected by the {$rejectedBy}.</p>
            <p><strong>Reason:</strong> {$reason}</p>
            <p>If you have questions, please contact the {$rejectedBy}.</p>
            <p>Thank you,<br>Rental Inventory System</p>
        </body>
        </html>
        ";
        
        // Send to student
        sendEmail($request['student_email'], $subject, $message);
        
        // Send to admin
        if ($request['admin_email']) {
            $adminMessage = str_replace("Dear {$request['student_name']}", "Dear Administrator", $message);
            $adminMessage = str_replace("Your rental request", "A rental request by {$request['student_name']}", $adminMessage);
            sendEmail($request['admin_email'], $subject, $adminMessage);
        }
    }
}

/**
 * Send availability notification to subscribed users
 */
function sendAvailabilityNotifications($equipmentId) {
    $conn = getConnection();
    
    // Get equipment details
    $stmt = $conn->prepare("SELECT name FROM equipment WHERE id = ?");
    $stmt->bind_param("i", $equipmentId);
    $stmt->execute();
    $equipment = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($equipment) {
        // Get subscribed users who haven't been notified
        $stmt = $conn->prepare("
            SELECT s.id as subscription_id, u.email, u.first_name
            FROM item_subscriptions s
            JOIN users u ON s.user_id = u.id
            WHERE s.equipment_id = ? AND s.notified = 0
        ");
        $stmt->bind_param("i", $equipmentId);
        $stmt->execute();
        $subscriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        foreach ($subscriptions as $sub) {
            $subject = "Equipment Now Available - " . $equipment['name'];
            $message = "
            <html>
            <body>
                <h2>Equipment Available</h2>
                <p>Dear {$sub['first_name']},</p>
                <p>The equipment <strong>{$equipment['name']}</strong> that you were waiting for is now available!</p>
                <p>Please login to the Rental Inventory System to submit a rental request.</p>
                <p>Thank you,<br>Rental Inventory System</p>
            </body>
            </html>
            ";
            
            sendEmail($sub['email'], $subject, $message);
            
            // Mark as notified
            $updateStmt = $conn->prepare("UPDATE item_subscriptions SET notified = 1 WHERE id = ?");
            $updateStmt->bind_param("i", $sub['subscription_id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }
    
    $conn->close();
}

/**
 * Format date for display
 */
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
        'approved' => '<span class="badge bg-success">Approved</span>',
        'rejected' => '<span class="badge bg-danger">Rejected</span>',
        'checked_out' => '<span class="badge bg-info">Checked Out</span>',
        'returned' => '<span class="badge bg-secondary">Returned</span>',
        'cancelled' => '<span class="badge bg-dark">Cancelled</span>',
        'available' => '<span class="badge bg-success">Available</span>',
        'maintenance' => '<span class="badge bg-warning text-dark">Maintenance</span>',
        'retired' => '<span class="badge bg-dark">Retired</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Handle file upload
 */
function uploadImage($file, $targetDir = null) {
    if ($targetDir === null) {
        $targetDir = UPLOAD_DIR;
    }
    
    // Create directory if it doesn't exist
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.'];
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        return ['success' => false, 'message' => 'File too large. Maximum size is 5MB.'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . $extension;
    $targetPath = $targetDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $targetPath];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file.'];
}

/**
 * Delete uploaded image
 */
function deleteImage($filename) {
    $path = UPLOAD_DIR . $filename;
    if (file_exists($path)) {
        return unlink($path);
    }
    return false;
}

/**
 * Get equipment image URL
 */
function getEquipmentImageUrl($imagePath) {
    if ($imagePath && file_exists(UPLOAD_DIR . $imagePath)) {
        return 'uploads/' . $imagePath;
    }
    return 'assets/images/no-image.png';
}

/**
 * Pagination helper
 */
function getPagination($totalItems, $currentPage, $perPage = 10) {
    $totalPages = ceil($totalItems / $perPage);
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'per_page' => $perPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

/**
 * Display flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if request is approved by both instructor and chair
 */
function isFullyApproved($instructorStatus, $chairStatus) {
    return $instructorStatus === 'approved' && $chairStatus === 'approved';
}

/**
 * Update overall request status based on individual approvals
 */
function updateOverallStatus($requestId) {
    $conn = getConnection();
    
    $stmt = $conn->prepare("SELECT instructor_status, chair_status FROM rental_requests WHERE id = ?");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($request) {
        $newStatus = 'pending';
        
        if ($request['instructor_status'] === 'rejected' || $request['chair_status'] === 'rejected') {
            $newStatus = 'rejected';
        } elseif ($request['instructor_status'] === 'approved' && $request['chair_status'] === 'approved') {
            $newStatus = 'approved';
            
            // Send approval email
            sendApprovalEmail($requestId);
        }
        
        $stmt = $conn->prepare("UPDATE rental_requests SET overall_status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $requestId);
        $stmt->execute();
        $stmt->close();
    }
    
    $conn->close();
}
?>
