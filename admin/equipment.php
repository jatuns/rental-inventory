<?php
/**
 * Admin Equipment Management
 */
$pageTitle = 'Manage Equipment';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireRole(['admin']);

$conn = getConnection();
$error = '';
$success = '';

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    
    // Check if equipment is currently checked out
    $checkStmt = $conn->prepare("SELECT status, image_path FROM equipment WHERE id = ?");
    $checkStmt->bind_param("i", $deleteId);
    $checkStmt->execute();
    $equipment = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();
    
    if ($equipment && $equipment['status'] === 'checked_out') {
        $error = 'Cannot delete equipment that is currently checked out.';
    } else {
        // Delete image if exists
        if ($equipment && $equipment['image_path']) {
            deleteImage($equipment['image_path']);
        }
        
        $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ?");
        $stmt->bind_param("i", $deleteId);
        if ($stmt->execute()) {
            $success = 'Equipment deleted successfully.';
        } else {
            $error = 'Failed to delete equipment.';
        }
        $stmt->close();
    }
}

// Handle add/edit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $brand = sanitize($_POST['brand'] ?? '');
    $model = sanitize($_POST['model'] ?? '');
    $serialNo = sanitize($_POST['serial_no'] ?? '');
    $cost = floatval($_POST['cost'] ?? 0);
    $categoryId = intval($_POST['category_id'] ?? 0);
    $locationId = intval($_POST['location_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'available');
    
    if (empty($name)) {
        $error = 'Equipment name is required.';
    } else {
        // Handle image upload
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadImage($_FILES['image']);
            if ($uploadResult['success']) {
                $imagePath = $uploadResult['filename'];
                
                // Delete old image if updating
                if ($id > 0) {
                    $oldImageStmt = $conn->prepare("SELECT image_path FROM equipment WHERE id = ?");
                    $oldImageStmt->bind_param("i", $id);
                    $oldImageStmt->execute();
                    $oldImage = $oldImageStmt->get_result()->fetch_assoc();
                    $oldImageStmt->close();
                    
                    if ($oldImage && $oldImage['image_path']) {
                        deleteImage($oldImage['image_path']);
                    }
                }
            } else {
                $error = $uploadResult['message'];
            }
        }
        
        if (empty($error)) {
            if ($id > 0) {
                // Update existing
                if ($imagePath) {
                    $stmt = $conn->prepare("UPDATE equipment SET name=?, description=?, brand=?, model=?, serial_no=?, cost=?, category_id=?, location_id=?, status=?, image_path=? WHERE id=?");
                    $stmt->bind_param("sssssdiissi", $name, $description, $brand, $model, $serialNo, $cost, $categoryId, $locationId, $status, $imagePath, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE equipment SET name=?, description=?, brand=?, model=?, serial_no=?, cost=?, category_id=?, location_id=?, status=? WHERE id=?");
                    $stmt->bind_param("sssssdiisi", $name, $description, $brand, $model, $serialNo, $cost, $categoryId, $locationId, $status, $id);
                }
                $success = 'Equipment updated successfully.';
            } else {
                // Add new
                $stmt = $conn->prepare("INSERT INTO equipment (name, description, brand, model, serial_no, cost, category_id, location_id, status, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssdiiss", $name, $description, $brand, $model, $serialNo, $cost, $categoryId, $locationId, $status, $imagePath);
                $success = 'Equipment added successfully.';
            }
            
            if ($stmt->execute()) {
                header("Location: equipment.php?success=" . urlencode($success));
                exit();
            } else {
                $error = 'Failed to save equipment: ' . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Get search/filter parameters
$search = sanitize($_GET['search'] ?? '');
$categoryFilter = intval($_GET['category'] ?? 0);
$statusFilter = sanitize($_GET['status'] ?? '');

// Build query
$query = "SELECT e.*, c.name as category_name, l.name as location_name,
          (SELECT COUNT(*) FROM borrow_history WHERE equipment_id = e.id) as borrow_count
          FROM equipment e 
          LEFT JOIN categories c ON e.category_id = c.id 
          LEFT JOIN locations l ON e.location_id = l.id 
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (e.name LIKE ? OR e.brand LIKE ? OR e.model LIKE ? OR e.serial_no LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    $types .= "ssss";
}

if ($categoryFilter > 0) {
    $query .= " AND e.category_id = ?";
    $params[] = $categoryFilter;
    $types .= "i";
}

if (!empty($statusFilter)) {
    $query .= " AND e.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$query .= " ORDER BY e.name ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$equipment = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get categories and locations for dropdowns
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$locations = $conn->query("SELECT * FROM locations ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Check for success message in URL
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
                    <a class="nav-link active" href="equipment.php"><i class="bi bi-camera"></i>Equipment</a>
                    <a class="nav-link" href="users.php"><i class="bi bi-people"></i>Users</a>
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
                <h2><i class="bi bi-camera me-2"></i>Equipment Management</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#equipmentModal">
                    <i class="bi bi-plus-circle me-2"></i>Add Equipment
                </button>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Search & Filter -->
            <div class="search-filter-bar">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="Search by name, brand, model, serial..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="available" <?php echo $statusFilter === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="checked_out" <?php echo $statusFilter === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                            <option value="maintenance" <?php echo $statusFilter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="retired" <?php echo $statusFilter === 'retired' ? 'selected' : ''; ?>>Retired</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i>Search
                        </button>
                    </div>
                </form>
            </div>
            
            <p class="text-muted">Showing <?php echo count($equipment); ?> item(s)</p>
            
            <!-- Equipment Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Brand/Model</th>
                                    <th>Serial No</th>
                                    <th>Category</th>
                                    <th>Location</th>
                                    <th>Cost</th>
                                    <th>Status</th>
                                    <th>Borrowed</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($equipment as $item): ?>
                                    <tr>
                                        <td>
                                            <?php if ($item['image_path']): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                                     alt="" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                     style="width: 50px; height: 50px;">
                                                    <i class="bi bi-camera text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></td>
                                        <td><code><?php echo htmlspecialchars($item['serial_no'] ?? 'N/A'); ?></code></td>
                                        <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?></td>
                                        <td>$<?php echo number_format($item['cost'], 2); ?></td>
                                        <td><?php echo getStatusBadge($item['status']); ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $item['borrow_count']; ?> times</span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editEquipment(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-info"
                                                    onclick="viewBorrowHistory(<?php echo $item['id']; ?>)">
                                                <i class="bi bi-clock-history"></i>
                                            </button>
                                            <a href="equipment.php?delete=<?php echo $item['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               data-confirm="Are you sure you want to delete this equipment?">
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

<!-- Add/Edit Equipment Modal -->
<div class="modal fade" id="equipmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="equipment_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Equipment Name *</label>
                                <input type="text" class="form-control" name="name" id="eq_name" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Brand</label>
                                    <input type="text" class="form-control" name="brand" id="eq_brand">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Model</label>
                                    <input type="text" class="form-control" name="model" id="eq_model">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Serial Number</label>
                                    <input type="text" class="form-control" name="serial_no" id="eq_serial">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Cost ($)</label>
                                    <input type="number" step="0.01" class="form-control" name="cost" id="eq_cost">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Category</label>
                                    <select class="form-select" name="category_id" id="eq_category">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Location</label>
                                    <select class="form-select" name="location_id" id="eq_location">
                                        <option value="">Select Location</option>
                                        <?php foreach ($locations as $loc): ?>
                                            <option value="<?php echo $loc['id']; ?>"><?php echo htmlspecialchars($loc['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="eq_status">
                                    <option value="available">Available</option>
                                    <option value="checked_out">Checked Out</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="retired">Retired</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="eq_description" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Equipment Image</label>
                            <div class="file-upload-wrapper mb-3">
                                <i class="bi bi-cloud-upload upload-icon"></i>
                                <p class="upload-text mb-0">Click or drag image here</p>
                                <small class="text-muted">JPG, PNG, GIF (Max 5MB)</small>
                                <input type="file" name="image" accept="image/*" data-preview="imagePreview">
                            </div>
                            <div class="image-preview mx-auto" id="imagePreviewContainer">
                                <img id="imagePreview" src="" alt="Preview" style="display: none;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Equipment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Borrow History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Borrow History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="historyContent">
                Loading...
            </div>
        </div>
    </div>
</div>

<script>
function editEquipment(item) {
    document.getElementById('modalTitle').textContent = 'Edit Equipment';
    document.getElementById('equipment_id').value = item.id;
    document.getElementById('eq_name').value = item.name;
    document.getElementById('eq_brand').value = item.brand || '';
    document.getElementById('eq_model').value = item.model || '';
    document.getElementById('eq_serial').value = item.serial_no || '';
    document.getElementById('eq_cost').value = item.cost || '';
    document.getElementById('eq_category').value = item.category_id || '';
    document.getElementById('eq_location').value = item.location_id || '';
    document.getElementById('eq_status').value = item.status;
    document.getElementById('eq_description').value = item.description || '';
    
    if (item.image_path) {
        document.getElementById('imagePreview').src = '../uploads/' + item.image_path;
        document.getElementById('imagePreview').style.display = 'block';
    }
    
    new bootstrap.Modal(document.getElementById('equipmentModal')).show();
}

function viewBorrowHistory(equipmentId) {
    document.getElementById('historyContent').innerHTML = 'Loading...';
    new bootstrap.Modal(document.getElementById('historyModal')).show();
    
    fetch('ajax/borrow_history.php?equipment_id=' + equipmentId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('historyContent').innerHTML = html;
        });
}

// Reset modal on close
document.getElementById('equipmentModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalTitle').textContent = 'Add Equipment';
    document.getElementById('equipment_id').value = '';
    this.querySelector('form').reset();
    document.getElementById('imagePreview').style.display = 'none';
});
</script>

<?php require_once '../includes/footer.php'; ?>
