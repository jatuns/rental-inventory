<?php
/**
 * Department Chair Dashboard
 */
$pageTitle = 'Chair Dashboard';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireRole(['chair']);

$conn = getConnection();

// Get pending requests (waiting for chair approval)
$pendingRequests = $conn->query("
    SELECT rr.*, u.first_name, u.last_name, u.email, u.student_id,
           e.name as equipment_name, c.course_name, c.course_code,
           inst.first_name as inst_first, inst.last_name as inst_last
    FROM rental_requests rr
    JOIN users u ON rr.student_id = u.id
    JOIN equipment e ON rr.equipment_id = e.id
    LEFT JOIN courses c ON rr.course_id = c.id
    LEFT JOIN users inst ON c.instructor_id = inst.id
    WHERE rr.chair_status = 'pending'
    ORDER BY rr.created_at ASC
")->fetch_all(MYSQLI_ASSOC);

// Get all recent requests
$allRequests = $conn->query("
    SELECT rr.*, u.first_name, u.last_name, e.name as equipment_name, c.course_code
    FROM rental_requests rr
    JOIN users u ON rr.student_id = u.id
    JOIN equipment e ON rr.equipment_id = e.id
    LEFT JOIN courses c ON rr.course_id = c.id
    ORDER BY rr.created_at DESC
    LIMIT 20
")->fetch_all(MYSQLI_ASSOC);

// Get predefined comments
$predefinedComments = $conn->query("SELECT * FROM predefined_comments WHERE role = 'chair'")->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = intval($_POST['request_id'] ?? 0);
    $action = sanitize($_POST['action'] ?? '');
    $comment = sanitize($_POST['comment'] ?? '');
    
    if ($requestId > 0 && in_array($action, ['approve', 'reject'])) {
        $verifyStmt = $conn->prepare("SELECT id FROM rental_requests WHERE id = ? AND chair_status = 'pending'");
        $verifyStmt->bind_param("i", $requestId);
        $verifyStmt->execute();
        
        if ($verifyStmt->get_result()->num_rows > 0) {
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $now = date('Y-m-d H:i:s');
            
            $updateStmt = $conn->prepare("UPDATE rental_requests SET chair_status = ?, chair_comment = ?, chair_action_date = ? WHERE id = ?");
            $updateStmt->bind_param("sssi", $status, $comment, $now, $requestId);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Update overall status
            updateOverallStatus($requestId);
            
            if ($action === 'reject') {
                sendRejectionEmail($requestId, 'Department Chair', $comment);
            }
            
            $success = 'Request ' . ($action === 'approve' ? 'approved' : 'rejected') . ' successfully.';
            
            header("Location: dashboard.php?success=" . urlencode($success));
            exit();
        }
        $verifyStmt->close();
    }
}

if (isset($_GET['success'])) {
    $success = sanitize($_GET['success']);
}

$conn->close();
require_once '../includes/header.php';
?>

<div class="container">
    <h2 class="mb-4"><i class="bi bi-speedometer2 me-2"></i>Department Chair Dashboard</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="dashboard-card">
                <div class="d-flex align-items-center">
                    <div class="icon bg-warning-soft"><i class="bi bi-hourglass-split"></i></div>
                    <div class="ms-3">
                        <div class="stat-value"><?php echo count($pendingRequests); ?></div>
                        <div class="stat-label">Pending Approval</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pending Requests -->
    <div class="card mb-4">
        <div class="card-header bg-warning">
            <h5 class="mb-0 text-dark">
                <i class="bi bi-hourglass-split me-2"></i>Requests Pending Your Approval
                <span class="badge bg-dark ms-2"><?php echo count($pendingRequests); ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($pendingRequests)): ?>
                <p class="text-muted text-center py-3">No pending requests.</p>
            <?php else: ?>
                <?php foreach ($pendingRequests as $req): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5><?php echo htmlspecialchars($req['equipment_name']); ?></h5>
                                    <p class="mb-1">
                                        <strong>Student:</strong> <?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?>
                                        (<?php echo htmlspecialchars($req['student_id'] ?? 'N/A'); ?>)
                                    </p>
                                    <p class="mb-1">
                                        <strong>Course:</strong> <?php echo htmlspecialchars(($req['course_code'] ?? 'N/A') . ' - ' . ($req['course_name'] ?? '')); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Instructor:</strong> 
                                        <?php echo $req['inst_first'] ? htmlspecialchars($req['inst_first'] . ' ' . $req['inst_last']) : 'N/A'; ?>
                                        - <?php echo getStatusBadge($req['instructor_status']); ?>
                                        <?php if ($req['instructor_comment']): ?>
                                            <br><small class="text-muted">"<?php echo htmlspecialchars($req['instructor_comment']); ?>"</small>
                                        <?php endif; ?>
                                    </p>
                                    <p class="mb-1"><strong>Due Date:</strong> <?php echo formatDate($req['due_date']); ?></p>
                                    <p class="mb-0">
                                        <strong>Purpose:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($req['purpose'])); ?>
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <form method="POST">
                                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                        
                                        <div class="mb-2">
                                            <label class="form-label">Comment</label>
                                            <textarea class="form-control form-control-sm" name="comment" rows="2" id="comment<?php echo $req['id']; ?>"></textarea>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">Quick comments:</small><br>
                                            <?php foreach ($predefinedComments as $pc): ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary mb-1 predefined-comment" 
                                                        data-comment="<?php echo htmlspecialchars($pc['comment_text']); ?>"
                                                        data-target="comment<?php echo $req['id']; ?>">
                                                    <?php echo htmlspecialchars(substr($pc['comment_text'], 0, 25)) . '...'; ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm flex-fill">
                                                <i class="bi bi-check-circle me-1"></i>Approve
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm flex-fill">
                                                <i class="bi bi-x-circle me-1"></i>Reject
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- All Requests -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>All Recent Requests</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Equipment</th>
                            <th>Course</th>
                            <th>Due Date</th>
                            <th>Instructor</th>
                            <th>Chair</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allRequests as $req): ?>
                            <tr>
                                <td>#<?php echo $req['id']; ?></td>
                                <td><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($req['equipment_name']); ?></td>
                                <td><?php echo htmlspecialchars($req['course_code'] ?? 'N/A'); ?></td>
                                <td><?php echo formatDate($req['due_date']); ?></td>
                                <td><?php echo getStatusBadge($req['instructor_status']); ?></td>
                                <td><?php echo getStatusBadge($req['chair_status']); ?></td>
                                <td><?php echo getStatusBadge($req['overall_status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
