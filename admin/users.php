<?php
/**
 * Admin User Management
 */
$pageTitle = 'Manage Users';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireRole(['admin']);

$conn = getConnection();
$error = '';
$success = '';

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    
    // Don't allow deleting yourself
    if ($deleteId == getUserId()) {
        $error = 'You cannot delete your own account.';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $deleteId);
        if ($stmt->execute()) {
            $success = 'User deleted successfully.';
        } else {
            $error = 'Failed to delete user.';
        }
        $stmt->close();
    }
}

// Handle add/edit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $email = sanitize($_POST['email'] ?? '');
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $role = sanitize($_POST['role'] ?? '');
    $studentId = sanitize($_POST['student_id'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($email) || empty($firstName) || empty($lastName) || empty($role)) {
        $error = 'Email, first name, last name, and role are required.';
    } elseif (!validateEmail($email)) {
        $error = 'Invalid email format.';
    } else {
        // Check for duplicate email
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkStmt->bind_param("si", $email, $id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $error = 'Email already exists.';
        }
        $checkStmt->close();
        
        if (empty($error)) {
            if ($id > 0) {
                // Update existing
                if (!empty($password)) {
                    $hashedPassword = hashPassword($password);
                    $stmt = $conn->prepare("UPDATE users SET email=?, first_name=?, last_name=?, role=?, student_id=?, phone=?, password=?, is_active=? WHERE id=?");
                    $stmt->bind_param("sssssssii", $email, $firstName, $lastName, $role, $studentId, $phone, $hashedPassword, $isActive, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET email=?, first_name=?, last_name=?, role=?, student_id=?, phone=?, is_active=? WHERE id=?");
                    $stmt->bind_param("ssssssii", $email, $firstName, $lastName, $role, $studentId, $phone, $isActive, $id);
                }
                $success = 'User updated successfully.';
            } else {
                // Add new - password required
                if (empty($password)) {
                    $error = 'Password is required for new users.';
                } else {
                    $hashedPassword = hashPassword($password);
                    $stmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name, role, student_id, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssssi", $email, $hashedPassword, $firstName, $lastName, $role, $studentId, $phone, $isActive);
                    $success = 'User added successfully.';
                }
            }
            
            if (empty($error) && isset($stmt)) {
                if ($stmt->execute()) {
                    header("Location: users.php?success=" . urlencode($success));
                    exit();
                } else {
                    $error = 'Failed to save user: ' . $conn->error;
                }
                $stmt->close();
            }
        }
    }
}

// Get filter parameters
$roleFilter = sanitize($_GET['role'] ?? '');
$search = sanitize($_GET['search'] ?? '');

// Build query
$query = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

if (!empty($roleFilter)) {
    $query .= " AND role = ?";
    $params[] = $roleFilter;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    $types .= "sss";
}

$query .= " ORDER BY role, first_name ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (isset($_GET['success'])) {
    $success = sanitize($_GET['success']);
}

$conn->close();

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2 px-0">
            <div class="sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i>Dashboard</a>
                    <a class="nav-link" href="equipment.php"><i class="bi bi-camera"></i>Equipment</a>
                    <a class="nav-link active" href="users.php"><i class="bi bi-people"></i>Users</a>
                    <a class="nav-link" href="requests.php"><i class="bi bi-file-text"></i>Requests</a>
                    <a class="nav-link" href="checkout.php"><i class="bi bi-box-arrow-right"></i>Checkout/Return</a>
                    <a class="nav-link" href="import.php"><i class="bi bi-upload"></i>Import Excel</a>
                    <a class="nav-link" href="courses.php"><i class="bi bi-book"></i>Courses</a>
                </nav>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-10 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-people me-2"></i>User Management</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                    <i class="bi bi-person-plus me-2"></i>Add User
                </button>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Filter Bar -->
            <div class="search-filter-bar">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="Search by name or email..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="role">
                            <option value="">All Roles</option>
                            <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="chair" <?php echo $roleFilter === 'chair' ? 'selected' : ''; ?>>Chair</option>
                            <option value="instructor" <?php echo $roleFilter === 'instructor' ? 'selected' : ''; ?>>Instructor</option>
                            <option value="student" <?php echo $roleFilter === 'student' ? 'selected' : ''; ?>>Student</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i>Search
                        </button>
                    </div>
                </form>
            </div>
            
            <p class="text-muted">Showing <?php echo count($users); ?> user(s)</p>
            
            <!-- Users Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Student ID</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>#<?php echo $user['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $user['role'] === 'admin' ? 'danger' : 
                                                    ($user['role'] === 'chair' ? 'warning' : 
                                                    ($user['role'] === 'instructor' ? 'info' : 'success')); 
                                            ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['student_id'] ?? '-'); ?></td>
                                        <td>
                                            <?php if ($user['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($user['created_at']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($user['id'] != getUserId()): ?>
                                                <a href="users.php?delete=<?php echo $user['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   data-confirm="Are you sure you want to delete this user?">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
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

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="id" id="user_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="first_name" id="u_first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="last_name" id="u_last_name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" id="u_email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password <span id="pwdNote">(required)</span></label>
                        <input type="password" class="form-control" name="password" id="u_password">
                        <small class="text-muted">Leave blank to keep existing password (when editing)</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role *</label>
                            <select class="form-select" name="role" id="u_role" required>
                                <option value="">Select Role</option>
                                <option value="admin">Admin</option>
                                <option value="chair">Chair</option>
                                <option value="instructor">Instructor</option>
                                <option value="student">Student</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Student ID</label>
                            <input type="text" class="form-control" name="student_id" id="u_student_id">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" id="u_phone">
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" id="u_is_active" checked>
                        <label class="form-check-label" for="u_is_active">Active Account</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('pwdNote').textContent = '(leave blank to keep existing)';
    document.getElementById('user_id').value = user.id;
    document.getElementById('u_first_name').value = user.first_name;
    document.getElementById('u_last_name').value = user.last_name;
    document.getElementById('u_email').value = user.email;
    document.getElementById('u_role').value = user.role;
    document.getElementById('u_student_id').value = user.student_id || '';
    document.getElementById('u_phone').value = user.phone || '';
    document.getElementById('u_is_active').checked = user.is_active == 1;
    document.getElementById('u_password').value = '';
    
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

document.getElementById('userModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalTitle').textContent = 'Add User';
    document.getElementById('pwdNote').textContent = '(required)';
    document.getElementById('user_id').value = '';
    this.querySelector('form').reset();
    document.getElementById('u_is_active').checked = true;
});
</script>

<?php require_once '../includes/footer.php'; ?>
