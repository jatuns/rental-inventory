<?php
/**
 * Admin Dashboard
 */
$pageTitle = 'Admin Dashboard';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireRole(['admin']);

$conn = getConnection();

// Get dashboard statistics
$stats = [];

// Total equipment
$result = $conn->query("SELECT COUNT(*) as count FROM equipment");
$stats['total_equipment'] = $result->fetch_assoc()['count'];

// Available equipment
$result = $conn->query("SELECT COUNT(*) as count FROM equipment WHERE status = 'available'");
$stats['available_equipment'] = $result->fetch_assoc()['count'];

// Checked out equipment
$result = $conn->query("SELECT COUNT(*) as count FROM equipment WHERE status = 'checked_out'");
$stats['checked_out'] = $result->fetch_assoc()['count'];

// Pending requests
$result = $conn->query("SELECT COUNT(*) as count FROM rental_requests WHERE overall_status = 'pending'");
$stats['pending_requests'] = $result->fetch_assoc()['count'];

// Approved requests awaiting checkout
$result = $conn->query("SELECT COUNT(*) as count FROM rental_requests WHERE overall_status = 'approved'");
$stats['approved_requests'] = $result->fetch_assoc()['count'];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Recent requests
$recentRequests = $conn->query("
    SELECT rr.*, 
           u.first_name, u.last_name, u.email,
           e.name as equipment_name,
           c.course_name
    FROM rental_requests rr
    JOIN users u ON rr.student_id = u.id
    JOIN equipment e ON rr.equipment_id = e.id
    LEFT JOIN courses c ON rr.course_id = c.id
    ORDER BY rr.created_at DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Items pending checkout (approved by both)
$pendingCheckout = $conn->query("
    SELECT rr.*, 
           u.first_name, u.last_name,
           e.name as equipment_name
    FROM rental_requests rr
    JOIN users u ON rr.student_id = u.id
    JOIN equipment e ON rr.equipment_id = e.id
    WHERE rr.overall_status = 'approved'
    ORDER BY rr.created_at ASC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Items pending return
$pendingReturn = $conn->query("
    SELECT rr.*, 
           u.first_name, u.last_name,
           e.name as equipment_name
    FROM rental_requests rr
    JOIN users u ON rr.student_id = u.id
    JOIN equipment e ON rr.equipment_id = e.id
    WHERE rr.overall_status = 'checked_out'
    ORDER BY rr.due_date ASC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2 px-0">
            <div class="sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="equipment.php">
                        <i class="bi bi-camera"></i>Equipment
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="bi bi-people"></i>Users
                    </a>
                    <a class="nav-link" href="requests.php">
                        <i class="bi bi-file-text"></i>Requests
                    </a>
                    <a class="nav-link" href="checkout.php">
                        <i class="bi bi-box-arrow-right"></i>Checkout/Return
                    </a>
                    <a class="nav-link" href="import.php">
                        <i class="bi bi-upload"></i>Import Excel
                    </a>
                    <a class="nav-link" href="courses.php">
                        <i class="bi bi-book"></i>Courses
                    </a>
                </nav>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-10 py-4">
            <h2 class="mb-4">
                <i class="bi bi-speedometer2 me-2"></i>Admin Dashboard
            </h2>
            
            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <div class="d-flex align-items-center">
                            <div class="icon bg-primary-soft">
                                <i class="bi bi-camera"></i>
                            </div>
                            <div class="ms-3">
                                <div class="stat-value"><?php echo $stats['total_equipment']; ?></div>
                                <div class="stat-label">Total Equipment</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <div class="d-flex align-items-center">
                            <div class="icon bg-success-soft">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="ms-3">
                                <div class="stat-value"><?php echo $stats['available_equipment']; ?></div>
                                <div class="stat-label">Available</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <div class="d-flex align-items-center">
                            <div class="icon bg-warning-soft">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                            <div class="ms-3">
                                <div class="stat-value"><?php echo $stats['pending_requests']; ?></div>
                                <div class="stat-label">Pending Requests</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <div class="d-flex align-items-center">
                            <div class="icon bg-danger-soft">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="ms-3">
                                <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                                <div class="stat-label">Total Users</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Pending Checkout -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-box-arrow-right me-2"></i>Pending Checkout</h5>
                            <span class="badge bg-success"><?php echo $stats['approved_requests']; ?> Approved</span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($pendingCheckout)): ?>
                                <p class="text-muted text-center py-3">No items pending checkout</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Equipment</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pendingCheckout as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['equipment_name']); ?></td>
                                                    <td>
                                                        <a href="checkout.php?action=checkout&id=<?php echo $item['id']; ?>" 
                                                           class="btn btn-sm btn-success">Checkout</a>
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
                
                <!-- Pending Return -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-box-arrow-in-left me-2"></i>Pending Return</h5>
                            <span class="badge bg-info"><?php echo $stats['checked_out']; ?> Checked Out</span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($pendingReturn)): ?>
                                <p class="text-muted text-center py-3">No items pending return</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Equipment</th>
                                                <th>Due Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pendingReturn as $item): ?>
                                                <?php 
                                                    $isOverdue = strtotime($item['due_date']) < time();
                                                ?>
                                                <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                                                    <td><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['equipment_name']); ?></td>
                                                    <td>
                                                        <?php echo formatDate($item['due_date']); ?>
                                                        <?php if ($isOverdue): ?>
                                                            <span class="badge bg-danger">Overdue</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="checkout.php?action=return&id=<?php echo $item['id']; ?>" 
                                                           class="btn btn-sm btn-primary">Return</a>
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
            
            <!-- Recent Requests -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Requests</h5>
                    <a href="requests.php" class="btn btn-sm btn-outline-primary">View All</a>
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
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRequests as $request): ?>
                                    <tr>
                                        <td>#<?php echo $request['id']; ?></td>
                                        <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($request['equipment_name']); ?></td>
                                        <td><?php echo htmlspecialchars($request['course_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo formatDate($request['due_date']); ?></td>
                                        <td><?php echo getStatusBadge($request['instructor_status']); ?></td>
                                        <td><?php echo getStatusBadge($request['chair_status']); ?></td>
                                        <td><?php echo getStatusBadge($request['overall_status']); ?></td>
                                        <td><?php echo formatDate($request['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
