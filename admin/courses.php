<?php
/**
 * Admin - Manage Courses
 */
$pageTitle = 'Manage Courses';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireRole(['admin']);

$conn = getConnection();
$error = '';
$success = '';

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    if ($stmt->execute()) {
        $success = 'Course deleted successfully.';
    }
    $stmt->close();
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $courseCode = sanitize($_POST['course_code'] ?? '');
    $courseName = sanitize($_POST['course_name'] ?? '');
    $instructorId = intval($_POST['instructor_id'] ?? 0);
    $semester = sanitize($_POST['semester'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($courseCode) || empty($courseName)) {
        $error = 'Course code and name are required.';
    } else {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE courses SET course_code=?, course_name=?, instructor_id=?, semester=?, is_active=? WHERE id=?");
            $stmt->bind_param("ssissi", $courseCode, $courseName, $instructorId, $semester, $isActive, $id);
            $success = 'Course updated successfully.';
        } else {
            $stmt = $conn->prepare("INSERT INTO courses (course_code, course_name, instructor_id, semester, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisi", $courseCode, $courseName, $instructorId, $semester, $isActive);
            $success = 'Course added successfully.';
        }
        
        if ($stmt->execute()) {
            header("Location: courses.php?success=" . urlencode($success));
            exit();
        } else {
            $error = 'Failed to save course.';
        }
        $stmt->close();
    }
}

// Get courses with instructor names
$courses = $conn->query("
    SELECT c.*, u.first_name, u.last_name 
    FROM courses c 
    LEFT JOIN users u ON c.instructor_id = u.id 
    ORDER BY c.course_code
")->fetch_all(MYSQLI_ASSOC);

// Get instructors for dropdown
$instructors = $conn->query("SELECT id, first_name, last_name FROM users WHERE role = 'instructor' ORDER BY first_name")->fetch_all(MYSQLI_ASSOC);

if (isset($_GET['success'])) {
    $success = sanitize($_GET['success']);
}

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
                    <a class="nav-link" href="checkout.php"><i class="bi bi-box-arrow-right"></i>Checkout/Return</a>
                    <a class="nav-link" href="import.php"><i class="bi bi-upload"></i>Import Excel</a>
                    <a class="nav-link active" href="courses.php"><i class="bi bi-book"></i>Courses</a>
                </nav>
            </div>
        </div>
        
        <div class="col-lg-10 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-book me-2"></i>Course Management</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#courseModal">
                    <i class="bi bi-plus-circle me-2"></i>Add Course
                </button>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Instructor</th>
                                    <th>Semester</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                        <td><?php echo $course['first_name'] ? htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) : 'N/A'; ?></td>
                                        <td><?php echo htmlspecialchars($course['semester'] ?? '-'); ?></td>
                                        <td>
                                            <?php if ($course['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editCourse(<?php echo htmlspecialchars(json_encode($course)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="courses.php?delete=<?php echo $course['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" data-confirm="Delete this course?">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
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

<!-- Course Modal -->
<div class="modal fade" id="courseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="id" id="course_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Course Code *</label>
                            <input type="text" class="form-control" name="course_code" id="c_code" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Semester</label>
                            <input type="text" class="form-control" name="semester" id="c_semester" placeholder="e.g., Fall 2024">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Course Name *</label>
                        <input type="text" class="form-control" name="course_name" id="c_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Instructor</label>
                        <select class="form-select" name="instructor_id" id="c_instructor">
                            <option value="">Select Instructor</option>
                            <?php foreach ($instructors as $inst): ?>
                                <option value="<?php echo $inst['id']; ?>">
                                    <?php echo htmlspecialchars($inst['first_name'] . ' ' . $inst['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" id="c_active" checked>
                        <label class="form-check-label" for="c_active">Active Course</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCourse(course) {
    document.getElementById('modalTitle').textContent = 'Edit Course';
    document.getElementById('course_id').value = course.id;
    document.getElementById('c_code').value = course.course_code;
    document.getElementById('c_name').value = course.course_name;
    document.getElementById('c_semester').value = course.semester || '';
    document.getElementById('c_instructor').value = course.instructor_id || '';
    document.getElementById('c_active').checked = course.is_active == 1;
    new bootstrap.Modal(document.getElementById('courseModal')).show();
}

document.getElementById('courseModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalTitle').textContent = 'Add Course';
    document.getElementById('course_id').value = '';
    this.querySelector('form').reset();
    document.getElementById('c_active').checked = true;
});
</script>

<?php require_once '../includes/footer.php'; ?>
