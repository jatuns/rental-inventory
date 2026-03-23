<?php
/**
 * Admin - Import Equipment from Excel
 */
$pageTitle = 'Import Excel';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireRole(['admin']);

$conn = getConnection();
$error = '';
$success = '';
$importResults = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed.';
    } else {
        $allowedTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'text/csv'
        ];
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            $error = 'Invalid file type. Please upload an Excel (.xlsx, .xls) or CSV file.';
        } else {
            // For CSV files
            if ($ext === 'csv') {
                $handle = fopen($file['tmp_name'], 'r');
                $header = fgetcsv($handle);
                
                $inserted = 0;
                $skipped = 0;
                $errors = [];
                
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) < 4) continue;
                    
                    $name = sanitize($row[0] ?? '');
                    $brand = sanitize($row[1] ?? '');
                    $model = sanitize($row[2] ?? '');
                    $serialNo = sanitize($row[3] ?? '');
                    $cost = floatval($row[4] ?? 0);
                    $description = sanitize($row[5] ?? '');
                    $categoryName = sanitize($row[6] ?? '');
                    
                    if (empty($name)) continue;
                    
                    // Check if serial number already exists
                    if (!empty($serialNo)) {
                        $checkStmt = $conn->prepare("SELECT id FROM equipment WHERE serial_no = ?");
                        $checkStmt->bind_param("s", $serialNo);
                        $checkStmt->execute();
                        if ($checkStmt->get_result()->num_rows > 0) {
                            $skipped++;
                            $checkStmt->close();
                            continue;
                        }
                        $checkStmt->close();
                    }
                    
                    // Get or create category
                    $categoryId = null;
                    if (!empty($categoryName)) {
                        $catStmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
                        $catStmt->bind_param("s", $categoryName);
                        $catStmt->execute();
                        $catResult = $catStmt->get_result();
                        if ($catResult->num_rows > 0) {
                            $categoryId = $catResult->fetch_assoc()['id'];
                        }
                        $catStmt->close();
                    }
                    
                    // Insert equipment
                    $stmt = $conn->prepare("INSERT INTO equipment (name, brand, model, serial_no, cost, description, category_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'available')");
                    $stmt->bind_param("ssssdsi", $name, $brand, $model, $serialNo, $cost, $description, $categoryId);
                    
                    if ($stmt->execute()) {
                        $inserted++;
                    } else {
                        $errors[] = "Failed to insert: $name";
                    }
                    $stmt->close();
                }
                
                fclose($handle);
                
                $importResults = [
                    'inserted' => $inserted,
                    'skipped' => $skipped,
                    'errors' => $errors
                ];
                
                if ($inserted > 0) {
                    $success = "Import completed! $inserted item(s) inserted, $skipped skipped (duplicate serial numbers).";
                } else {
                    $error = 'No items were imported. Check your file format.';
                }
            } else {
                // For XLSX files - require PhpSpreadsheet library
                $error = 'For XLSX files, please install PhpSpreadsheet library or use CSV format instead.';
            }
        }
    }
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
                    <a class="nav-link active" href="import.php"><i class="bi bi-upload"></i>Import Excel</a>
                    <a class="nav-link" href="courses.php"><i class="bi bi-book"></i>Courses</a>
                </nav>
            </div>
        </div>
        
        <div class="col-lg-10 py-4">
            <h2 class="mb-4"><i class="bi bi-upload me-2"></i>Import Equipment from Excel/CSV</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Upload File</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Select Excel or CSV File</label>
                                    <div class="file-upload-wrapper">
                                        <i class="bi bi-cloud-upload upload-icon"></i>
                                        <p class="upload-text mb-0">Click or drag file here</p>
                                        <small class="text-muted">CSV, XLS, or XLSX files</small>
                                        <input type="file" name="excel_file" accept=".csv,.xls,.xlsx" required>
                                    </div>
                                    <div id="selectedFileName" class="mt-2 text-muted"></div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload me-2"></i>Import Equipment
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>File Format</h5>
                        </div>
                        <div class="card-body">
                            <p>Your CSV file should have the following columns:</p>
                            <ol>
                                <li><strong>Name</strong> (required)</li>
                                <li><strong>Brand</strong></li>
                                <li><strong>Model</strong></li>
                                <li><strong>Serial Number</strong> (used to prevent duplicates)</li>
                                <li><strong>Cost</strong></li>
                                <li><strong>Description</strong></li>
                                <li><strong>Category Name</strong></li>
                            </ol>
                            
                            <h6>Example CSV:</h6>
                            <pre class="bg-light p-2 rounded">Name,Brand,Model,Serial No,Cost,Description,Category
Canon EOS R5,Canon,EOS R5,CN-R5-002,3899.00,Professional Camera,Cameras
Sony A7 IV,Sony,A7 IV,SN-A7-002,2498.00,Mirrorless Camera,Cameras</pre>
                            
                            <div class="alert alert-warning mt-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Note:</strong> Items with duplicate serial numbers will be skipped.
                            </div>
                            
                            <a href="sample_import.csv" class="btn btn-outline-primary btn-sm" download>
                                <i class="bi bi-download me-1"></i>Download Sample CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('input[name="excel_file"]').addEventListener('change', function() {
    document.getElementById('selectedFileName').textContent = this.files[0]?.name || '';
});
</script>

<?php require_once '../includes/footer.php'; ?>
