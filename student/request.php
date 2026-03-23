<?php
/**
 * Student - Create Rental Request
 */
$pageTitle = 'New Rental Request';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireRole(['student']);

$conn = getConnection();
$studentId = getUserId();
$error = '';
$success = '';

// Get equipment ID from URL if provided
$selectedEquipmentId = intval($_GET['equipment_id'] ?? 0);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipmentId = intval($_POST['equipment_id'] ?? 0);
    $courseId = intval($_POST['course_id'] ?? 0);
    $purpose = sanitize($_POST['purpose'] ?? '');
    $dueDate = sanitize($_POST['due_date'] ?? '');
    
    if (empty($equipmentId) || empty($purpose) || empty($dueDate)) {
        $error = 'Please fill in all required fields.';
    } elseif (strtotime($dueDate) < strtotime('today')) {
        $error = 'Due date must be in the future.';
    } else {
        // Check if equipment is available
        $checkStmt = $conn->prepare("SELECT status FROM equipment WHERE id = ?");
        $checkStmt->bind_param("i", $equipmentId);
        $checkStmt->execute();
        $equipment = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();
        
        if (!$equipment || $equipment['status'] !== 'available') {
            $error = 'This equipment is not available for rental.';
        } else {
            // Check for existing pending request
            $existingStmt = $conn->prepare("SELECT id FROM rental_requests WHERE student_id = ? AND equipment_id = ? AND overall_status IN ('pending', 'approved', 'checked_out')");
            $existingStmt->bind_param("ii", $studentId, $equipmentId);
            $existingStmt->execute();
            if ($existingStmt->get_result()->num_rows > 0) {
                $error = 'You already have an active request for this equipment.';
            }
            $existingStmt->close();
        }
        
        if (empty($error)) {
            $courseId = $courseId > 0 ? $courseId : null;
            $stmt = $conn->prepare("INSERT INTO rental_requests (student_id, equipment_id, course_id, purpose, due_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiss", $studentId, $equipmentId, $courseId, $purpose, $dueDate);
            
            if ($stmt->execute()) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Rental request submitted successfully! Awaiting approval.'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Failed to submit request. Please try again.';
            }
            $stmt->close();
        }
    }
}

// Get available equipment
$equipment = $conn->query("
    SELECT e.*, c.name as category_name 
    FROM equipment e 
    LEFT JOIN categories c ON e.category_id = c.id 
    WHERE e.status = 'available' 
    ORDER BY e.name
")->fetch_all(MYSQLI_ASSOC);

// Get active courses
$courses = $conn->query("SELECT * FROM courses WHERE is_active = 1 ORDER BY course_code")->fetch_all(MYSQLI_ASSOC);

$conn->close();
require_once '../includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-plus-circle me-2"></i>New Rental Request</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="rental-form">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="form-section">
                        <h5 class="form-section-title"><i class="bi bi-camera me-2"></i>Select Equipment</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Equipment *</label>
                            <select class="form-select" name="equipment_id" id="equipment_id" required>
                                <option value="">Choose equipment...</option>
                                <?php foreach ($equipment as $item): ?>
                                    <option value="<?php echo $item['id']; ?>" 
                                            <?php echo $selectedEquipmentId == $item['id'] ? 'selected' : ''; ?>
                                            data-image="<?php echo htmlspecialchars($item['image_path'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($item['name'] . ' - ' . $item['brand'] . ' ' . $item['model']); ?>
                                        (<?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select equipment.</div>
                        </div>
                        
                        <div id="equipmentPreview" class="mb-3" style="display: none;">
                            <img id="previewImage" src="" class="rounded" style="max-height: 150px;">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h5 class="form-section-title"><i class="bi bi-book me-2"></i>Course Information</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Course</label>
                            <select class="form-select" name="course_id">
                                <option value="">Select course (optional)...</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Select the course this equipment is needed for.</small>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h5 class="form-section-title"><i class="bi bi-chat-text me-2"></i>Request Details</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Purpose *</label>
                            <textarea class="form-control" name="purpose" rows="4" required
                                      placeholder="Describe why you need this equipment and how you plan to use it..."><?php echo htmlspecialchars($_POST['purpose'] ?? ''); ?></textarea>
                            <div class="invalid-feedback">Please describe the purpose of your rental.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Return Date *</label>
                            <input type="date" class="form-control" name="due_date" id="due_date" 
                                   value="<?php echo htmlspecialchars($_POST['due_date'] ?? ''); ?>" required>
                            <div class="invalid-feedback">Please select a return date.</div>
                            <small class="text-muted">When will you return the equipment?</small>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Note:</strong> Your request will be reviewed by your course instructor and the department chair. 
                        You will receive an email notification when your request is approved or rejected.
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-2"></i>Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Set minimum date to today
document.getElementById('due_date').min = new Date().toISOString().split('T')[0];

// Equipment preview
document.getElementById('equipment_id').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const imagePath = selected.dataset.image;
    const preview = document.getElementById('equipmentPreview');
    const img = document.getElementById('previewImage');
    
    if (imagePath) {
        img.src = '../uploads/' + imagePath;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
