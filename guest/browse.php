<?php
/**
 * Guest Browse Equipment Page
 * Allows anyone to view and search available equipment
 */
$pageTitle = 'Browse Equipment';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

$conn = getConnection();

// Get search and filter parameters
$search = sanitize($_GET['search'] ?? '');
$categoryId = intval($_GET['category'] ?? 0);
$status = sanitize($_GET['status'] ?? '');

// Build query
$query = "SELECT e.*, c.name as category_name, l.name as location_name 
          FROM equipment e 
          LEFT JOIN categories c ON e.category_id = c.id 
          LEFT JOIN locations l ON e.location_id = l.id 
          WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (e.name LIKE ? OR e.description LIKE ? OR e.brand LIKE ? OR e.model LIKE ? OR e.serial_no LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    $types .= "sssss";
}

if ($categoryId > 0) {
    $query .= " AND e.category_id = ?";
    $params[] = $categoryId;
    $types .= "i";
}

if (!empty($status)) {
    $query .= " AND e.status = ?";
    $params[] = $status;
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

// Get categories for filter
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$conn->close();

require_once '../includes/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-grid me-2"></i>Browse Equipment</h2>
            <p class="text-muted mb-0">Explore our catalog of professional equipment</p>
        </div>
        <?php if (isLoggedIn() && getUserRole() === 'student'): ?>
            <a href="../student/request.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>New Rental Request
            </a>
        <?php elseif (!isLoggedIn()): ?>
            <a href="../index.php" class="btn btn-outline-primary">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login to Rent
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Search & Filter Bar -->
    <div class="search-filter-bar">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="Search equipment..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $categoryId == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="available" <?php echo $status === 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="checked_out" <?php echo $status === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                    <option value="maintenance" <?php echo $status === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter me-1"></i>Filter
                </button>
            </div>
        </form>
    </div>
    
    <!-- Results Count -->
    <p class="text-muted mb-3">
        <i class="bi bi-box me-1"></i>Showing <?php echo count($equipment); ?> item(s)
    </p>
    
    <!-- Equipment Grid -->
    <?php if (empty($equipment)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>No equipment found matching your criteria.
        </div>
    <?php else: ?>
        <div class="equipment-grid">
            <?php foreach ($equipment as $item): ?>
                <div class="equipment-card" data-searchable data-category="<?php echo $item['category_id']; ?>" data-status="<?php echo $item['status']; ?>">
                    <div class="image-container">
                        <?php if ($item['image_path'] && file_exists('../uploads/' . $item['image_path'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="no-image">
                                <i class="bi bi-camera"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h5 class="equipment-name"><?php echo htmlspecialchars($item['name']); ?></h5>
                        <p class="equipment-brand mb-2">
                            <?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="equipment-category">
                                <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?>
                            </span>
                            <?php echo getStatusBadge($item['status']); ?>
                        </div>
                        
                        <?php if ($item['location_name']): ?>
                            <p class="text-muted small mb-2">
                                <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($item['location_name']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-sm btn-outline-primary w-100" 
                                data-bs-toggle="modal" data-bs-target="#equipmentModal<?php echo $item['id']; ?>">
                            <i class="bi bi-eye me-1"></i>View Details
                        </button>
                    </div>
                </div>
                
                <!-- Equipment Detail Modal -->
                <div class="modal fade" id="equipmentModal<?php echo $item['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-5">
                                        <?php if ($item['image_path'] && file_exists('../uploads/' . $item['image_path'])): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                                 class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 250px;">
                                                <i class="bi bi-camera display-1 text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-7">
                                        <table class="table table-borderless">
                                            <tr>
                                                <th width="35%">Brand:</th>
                                                <td><?php echo htmlspecialchars($item['brand'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Model:</th>
                                                <td><?php echo htmlspecialchars($item['model'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Serial No:</th>
                                                <td><?php echo htmlspecialchars($item['serial_no'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Category:</th>
                                                <td><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Location:</th>
                                                <td><?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Status:</th>
                                                <td><?php echo getStatusBadge($item['status']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Value:</th>
                                                <td>$<?php echo number_format($item['cost'] ?? 0, 2); ?></td>
                                            </tr>
                                        </table>
                                        
                                        <?php if ($item['description']): ?>
                                            <h6>Description</h6>
                                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <?php if (isLoggedIn() && getUserRole() === 'student' && $item['status'] === 'available'): ?>
                                    <a href="../student/request.php?equipment_id=<?php echo $item['id']; ?>" class="btn btn-primary">
                                        <i class="bi bi-cart-plus me-1"></i>Request This Item
                                    </a>
                                <?php elseif (!isLoggedIn()): ?>
                                    <a href="../index.php" class="btn btn-primary">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>Login to Request
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
