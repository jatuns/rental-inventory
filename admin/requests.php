<?php
/**
 * Admin - View All Requests
 */
$pageTitle = 'All Requests';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireRole(['admin']);

$conn = getConnection();

$statusFilter = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');

$query = "SELECT rr.*, u.first_name, u.last_name, u.email,
          e.name as equipment_name, c.course_name, c.course_code
          FROM rental_requests rr
          JOIN users u ON rr.student_id = u.id
          JOIN equipment e ON rr.equipment_id = e.id
          LEFT JOIN courses c ON rr.course_id = c.id
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($statusFilter)) {
    $query .= " AND rr.overall_status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR e.name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    $types .= "sss";
}

$query .= " ORDER BY rr.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
                    <a class="nav-link active" href="requests.php"><i class="bi bi-file-text"></i>Requests</a>
                    <a class="nav-link" href="checkout.php"><i class="bi bi-box-arrow-right"></i>Checkout/Return</a>
                    <a class="nav-link" href="import.php"><i class="bi bi-upload"></i>Import Excel</a>
                    <a class="nav-link" href="courses.php"><i class="bi bi-book"></i>Courses</a>
                </nav>
            </div>
        </div>
        
        <div class="col-lg-10 py-4">
            <h2 class="mb-4"><i class="bi bi-file-text me-2"></i>All Rental Requests</h2>
            
            <div class="search-filter-bar">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="Search student or equipment..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="checked_out" <?php echo $statusFilter === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                            <option value="returned" <?php echo $statusFilter === 'returned' ? 'selected' : ''; ?>>Returned</option>
                            <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filter</button>
                    </div>
                </form>
            </div>
            
            <p class="text-muted">Showing <?php echo count($requests); ?> request(s)</p>
            
            <div class="card">
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
                                    <th>Overall</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td>#<?php echo $req['id']; ?></td>
                                        <td><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($req['equipment_name']); ?></td>
                                        <td><?php echo htmlspecialchars($req['course_code'] ?? 'N/A'); ?></td>
                                        <td><?php echo formatDate($req['due_date']); ?></td>
                                        <td><?php echo getStatusBadge($req['instructor_status']); ?></td>
                                        <td><?php echo getStatusBadge($req['chair_status']); ?></td>
                                        <td><?php echo getStatusBadge($req['overall_status']); ?></td>
                                        <td><?php echo formatDate($req['created_at']); ?></td>
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
