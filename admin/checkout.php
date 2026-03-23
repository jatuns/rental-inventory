<?php
/**
 * Admin Checkout & Return Management
 */
$pageTitle = 'Checkout & Return';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireRole(['admin']);

$conn = getConnection();
$error = '';
$success = '';

// Handle checkout action
if (isset($_GET['action']) && $_GET['action'] === 'checkout' && isset($_GET['id'])) {
    $requestId = intval($_GET['id']);
    $adminId = getUserId();
    
    $stmt = $conn->prepare("SELECT rr.*, e.id as equipment_id FROM rental_requests rr 
                            JOIN equipment e ON rr.equipment_id = e.id 
                            WHERE rr.id = ? AND rr.overall_status = 'approved'");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($request) {
        $today = date('Y-m-d');
        $stmt = $conn->prepare("UPDATE rental_requests SET overall_status = 'checked_out', checkout_date = ?, admin_checkout_by = ? WHERE id = ?");
        $stmt->bind_param("sii", $today, $adminId, $requestId);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conn->prepare("UPDATE equipment SET status = 'checked_out', total_borrow_count = total_borrow_count + 1 WHERE id = ?");
        $stmt->bind_param("i", $request['equipment_id']);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conn->prepare("INSERT INTO borrow_history (equipment_id, user_id, rental_request_id, checkout_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $request['equipment_id'], $request['student_id'], $requestId, $today);
        $stmt->execute();
        $stmt->close();
        
        $success = 'Equipment checked out successfully.';
    } else {
        $error = 'Invalid request or not approved.';
    }
}

// Handle return action
if (isset($_GET['action']) && $_GET['action'] === 'return' && isset($_GET['id'])) {
    $requestId = intval($_GET['id']);
    $adminId = getUserId();
    
    $stmt = $conn->prepare("SELECT rr.*, e.id as equipment_id FROM rental_requests rr 
                            JOIN equipment e ON rr.equipment_id = e.id 
                            WHERE rr.id = ? AND rr.overall_status = 'checked_out'");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($request) {
        $today = date('Y-m-d');
        
        $stmt = $conn->prepare("UPDATE rental_requests SET overall_status = 'returned', return_date = ?, admin_return_by = ? WHERE id = ?");
        $stmt->bind_param("sii", $today, $adminId, $requestId);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conn->prepare("UPDATE equipment SET status = 'available' WHERE id = ?");
        $stmt->bind_param("i", $request['equipment_id']);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conn->prepare("UPDATE borrow_history SET return_date = ? WHERE rental_request_id = ?");
        $stmt->bind_param("si", $today, $requestId);
        $stmt->execute();
        $stmt->close();
        
        sendAvailabilityNotifications($request['equipment_id']);
        
        $success = 'Equipment returned successfully.';
    } else {
        $error = 'Invalid request or not checked out.';
    }
}

$approvedRequests = $conn->query("
    SELECT rr.*, u.first_name, u.last_name, u.email, u.student_id,
           e.name as equipment_name, e.serial_no, c.course_name, c.course_code
    FROM rental_requests rr
    JOIN users u ON rr.student_id = u.id
    JOIN equipment e ON rr.equipment_id = e.id
    LEFT JOIN courses c ON rr.course_id = c.id
    WHERE rr.overall_status = 'approved'
    ORDER BY rr.created_at ASC
")->fetch_all(MYSQLI_ASSOC);

$checkedOutItems = $conn->query("
    SELECT rr.*, u.first_name, u.last_name, u.email, u.student_id,
           e.name as equipment_name, e.serial_no, c.course_name, c.course_code
    FROM rental_requests rr
    JOIN users u ON rr.student_id = u.id
    JOIN equipment e ON rr.equipment_id = e.id
    LEFT JOIN courses c ON rr.course_id = c.id
    WHERE rr.overall_status = 'checked_out'
    ORDER BY rr.due_date ASC
")->fetch_all(MYSQLI_ASSOC);

$conn->close();
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 px-0">
            <div class="sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i>Dashboard</a>
                    <a class="nav-link" href="equipment.php"><i class="bi bi-camera"></i>Equipment</a>
                    <a class="nav-link" href="users.php"><i class="bi bi-people"></i>Users</a>
                    <a class="nav-link" href="requests.php"><i class="bi bi-file-text"></i>Requests</a>
                    <a class="nav-link active" href="checkout.php"><i class="bi bi-box-arrow-right"></i>Checkout/Return</a>
                    <a class="nav-link" href="import.php"><i class="bi bi-upload"></i>Import Excel</a>
                    <a class="nav-link" href="courses.php"><i class="bi bi-book"></i>Courses</a>
                </nav>
            </div>
        </div>
        
        <div class="col-lg-10 py-4">
            <h2 class="mb-4"><i class="bi bi-box-arrow-right me-2"></i>Checkout & Return</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Pending Checkout -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-box-arrow-right me-2"></i>Ready for Checkout
                        <span class="badge bg-light text-success ms-2"><?php echo count($approvedRequests); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($approvedRequests)): ?>
                        <p class="text-muted text-center py-3">No approved requests pending checkout.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Request #</th>
                                        <th>Student</th>
                                        <th>Equipment</th>
                                        <th>Course</th>
                                        <th>Due Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approvedRequests as $req): ?>
                                        <tr>
                                            <td>#<?php echo $req['id']; ?></td>
                                            <td><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($req['equipment_name']); ?></td>
                                            <td><?php echo htmlspecialchars($req['course_code'] ?? 'N/A'); ?></td>
                                            <td><?php echo formatDate($req['due_date']); ?></td>
                                            <td>
                                                <a href="checkout.php?action=checkout&id=<?php echo $req['id']; ?>" 
                                                   class="btn btn-success btn-sm" data-confirm="Confirm checkout?">
                                                    <i class="bi bi-box-arrow-right me-1"></i>Checkout
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Pending Return -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-box-arrow-in-left me-2"></i>Pending Return
                        <span class="badge bg-light text-info ms-2"><?php echo count($checkedOutItems); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($checkedOutItems)): ?>
                        <p class="text-muted text-center py-3">No items currently checked out.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Request #</th>
                                        <th>Student</th>
                                        <th>Equipment</th>
                                        <th>Checkout Date</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($checkedOutItems as $item): 
                                        $isOverdue = strtotime($item['due_date']) < time();
                                    ?>
                                        <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                                            <td>#<?php echo $item['id']; ?></td>
                                            <td><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['equipment_name']); ?></td>
                                            <td><?php echo formatDate($item['checkout_date']); ?></td>
                                            <td><?php echo formatDate($item['due_date']); ?></td>
                                            <td>
                                                <?php if ($isOverdue): ?>
                                                    <span class="badge bg-danger">Overdue</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">On Time</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="checkout.php?action=return&id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-primary btn-sm" data-confirm="Confirm return?">
                                                    <i class="bi bi-box-arrow-in-left me-1"></i>Return
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
