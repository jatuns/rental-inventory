<?php
/**
 * Student - Edit Rental Request
 */
$pageTitle = 'Edit Request';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireRole(['student']);

$conn = getConnection();
$studentId = getUserId();
$requestId = intval($_GET['id'] ?? 0);
$error = '';

// Fetch the request
$stmt = $conn->prepare("SELECT rr.*, e.name as equipment_name FROM rental_requests rr 
                        JOIN equipment e ON rr.equipment_id = e.id 
                        WHERE rr.id = ? AND rr.student_id = ? AND rr.overall_status = 'pending'");
$stmt->bind_param("ii", $requestId, $studentId);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Request not found or cannot be edited.'];
    header("Location: dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = intval($_POST['course_id'] ?? 0);
    $purpose = sanitize($_POST['purpose'] ?? '');
    $dueDate = sanitize($_POST['due_date'] ?? '');
    
    if (empty($purpose) || empty($dueDate)) {
        $error = 'Please fill in all required fields.';
    } elseif (strtotime($dueDate) < strtotime('today')) {
        $error = 'Due date must be in the future.';
    } else {
        $courseId = $courseId > 0 ? $courseId : null;
        $stmt = $conn->prepare("UPDATE rental_requests SET course_id = ?, purpose = ?, due_date = ? WHERE id = ?");
        $stmt->bind_param("issi", $courseId, $purpose, $dueDate, $requestId);
        
        if ($stmt->execute()) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Request updated successfully.'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = 'Failed to update request.';
        }
        $stmt->close();
    }
}

// Get courses
$courses = $conn->query("SELECT * FROM courses WHERE is_active = 1 ORDER BY course_code")->fetch_all(MYSQLI_ASSOC);

$conn->close();
require_once '../includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h2 class="mb-4"><i class="bi bi-pencil me-2"></i>Edit Request</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card mb-3">
                <div class="card-body">
                    <strong>Equipment:</strong> <?php echo htmlspecialchars($request['equipment_name']); ?>
                </div>
            </div>
            
            <div class="rental-form">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Course</label>
                        <select class="form-select" name="course_id">
                            <option value="">Select course (optional)...</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>" 
                                        <?php echo $request['course_id'] == $course['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Purpose *</label>
                        <textarea class="form-control" name="purpose" rows="4" required><?php echo htmlspecialchars($request['purpose']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Return Date *</label>
                        <input type="date" class="form-control" name="due_date" 
                               value="<?php echo $request['due_date']; ?>" required 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
