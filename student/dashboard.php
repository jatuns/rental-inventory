<?php
/**
 * Student Dashboard
 */
$pageTitle = 'Student Dashboard';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireRole(['student']);

$conn = getConnection();
$studentId = getUserId();

// Get student's requests
$myRequests = $conn->query("
    SELECT rr.*, e.name as equipment_name, e.image_path, c.course_name, c.course_code
    FROM rental_requests rr
    JOIN equipment e ON rr.equipment_id = e.id
    LEFT JOIN courses c ON rr.course_id = c.id
    WHERE rr.student_id = $studentId
    ORDER BY rr.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Count by status
$stats = [
    'pending' => 0,
    'approved' => 0,
    'checked_out' => 0,
    'returned' => 0
];

foreach ($myRequests as $req) {
    if (isset($stats[$req['overall_status']])) {
        $stats[$req['overall_status']]++;
    }
}

$conn->close();
require_once '../includes/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-speedometer2 me-2"></i>My Dashboard</h2>
            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</p>
        </div>
        <a href="request.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>New Rental Request
        </a>
    </div>
    
    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="dashboard-card">
                <div class="d-flex align-items-center">
                    <div class="icon bg-warning-soft"><i class="bi bi-hourglass-split"></i></div>
                    <div class="ms-3">
                        <div class="stat-value"><?php echo $stats['pending']; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-card">
                <div class="d-flex align-items-center">
                    <div class="icon bg-success-soft"><i class="bi bi-check-circle"></i></div>
                    <div class="ms-3">
                        <div class="stat-value"><?php echo $stats['approved']; ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-card">
                <div class="d-flex align-items-center">
                    <div class="icon bg-primary-soft"><i class="bi bi-box-arrow-right"></i></div>
                    <div class="ms-3">
                        <div class="stat-value"><?php echo $stats['checked_out']; ?></div>
                        <div class="stat-label">Checked Out</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-card">
                <div class="d-flex align-items-center">
                    <div class="icon bg-secondary" style="background-color: rgba(100,116,139,0.1) !important; color: #64748b;">
                        <i class="bi bi-box-arrow-in-left"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stat-value"><?php echo $stats['returned']; ?></div>
                        <div class="stat-label">Returned</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- My Requests -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-file-text me-2"></i>My Rental Requests</h5>
            <a href="../guest/browse.php" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-grid me-1"></i>Browse Equipment
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($myRequests)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="text-muted mt-3">You haven't made any rental requests yet.</p>
                    <a href="request.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Create Your First Request
                    </a>
                </div>
            <?php else: ?>
            <?php $detailModalsHtml = ''; ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Course</th>
                                <th>Due Date</th>
                                <th>Instructor</th>
                                <th>Chair</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myRequests as $req): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($req['image_path']): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($req['image_path']); ?>" 
                                                     class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php endif; ?>
                                            <strong><?php echo htmlspecialchars($req['equipment_name']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($req['course_code'] ?? 'N/A'); ?></td>
                                    <td><?php echo formatDate($req['due_date']); ?></td>
                                    <td><?php echo getStatusBadge($req['instructor_status']); ?></td>
                                    <td><?php echo getStatusBadge($req['chair_status']); ?></td>
                                    <td><?php echo getStatusBadge($req['overall_status']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info"
                                                data-bs-toggle="modal" data-bs-target="#detailModal<?php echo (int)$req['id']; ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <?php if ($req['overall_status'] === 'pending'): ?>
                                            <a href="edit-request.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="cancel-request.php?id=<?php echo $req['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" data-confirm="Cancel this request?">
                                                <i class="bi bi-x-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                                  $detailModalsHtml .= '
' 
                                    . '<div class="modal fade" id="detailModal' . (int)$req['id'] . '" tabindex="-1" aria-hidden="true">'
                                    . '  <div class="modal-dialog modal-dialog-centered">'
                                    . '    <div class="modal-content">'
                                    . '      <div class="modal-header">'
                                    . '        <h5 class="modal-title">Request Details</h5>'
                                    . '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>'
                                    . '      </div>'
                                    . '      <div class="modal-body">'
                                    . '        <h6>' . htmlspecialchars($req['equipment_name']) . '</h6>'
                                    . '        <hr>'
                                    . '        <p><strong>Purpose:</strong><br>' . nl2br(htmlspecialchars($req['purpose'])) . '</p>'
                                    . '        <p><strong>Due Date:</strong> ' . formatDate($req['due_date']) . '</p>'
                                    . '        <p><strong>Requested:</strong> ' . formatDateTime($req['created_at']) . '</p>'
                                    . '        <h6 class="mt-3">Approval Status</h6>'
                                    . '        <div class="status-timeline">'
                                    . '          <div class="status-timeline-item ' . ($req['instructor_status'] === 'approved' ? 'completed' : ($req['instructor_status'] === 'rejected' ? 'rejected' : 'pending')) . '">' 
                                    . '            <strong>Instructor:</strong> ' . ucfirst($req['instructor_status'])
                                    . (empty($req['instructor_comment']) ? '' : '<br><small class="text-muted">' . htmlspecialchars($req['instructor_comment']) . '</small>')
                                    . '          </div>'
                                    . '          <div class="status-timeline-item ' . ($req['chair_status'] === 'approved' ? 'completed' : ($req['chair_status'] === 'rejected' ? 'rejected' : 'pending')) . '">' 
                                    . '            <strong>Department Chair:</strong> ' . ucfirst($req['chair_status'])
                                    . (empty($req['chair_comment']) ? '' : '<br><small class="text-muted">' . htmlspecialchars($req['chair_comment']) . '</small>')
                                    . '          </div>'
                                    . '        </div>'
                                    . '      </div>'
                                    . '    </div>'
                                    . '  </div>'
                                    . '</div>';
                                ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
  /* Ensure the backdrop captures pointer events and stays behind the modal */
  .modal-backdrop { pointer-events: auto; }
</style>
<?php
  // Render all request detail modals at the end of the page to avoid hover/transform side-effects
  // from table/card/container elements affecting fixed-position Bootstrap modals.
  if (!empty($detailModalsHtml)) {
      echo $detailModalsHtml;
  }
?>

<?php require_once '../includes/footer.php'; ?>