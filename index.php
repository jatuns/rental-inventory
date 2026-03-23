<?php
/* Landing Page with Login Rental Inventory System */

session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirectToDashboard();
}

$error = '';
$email = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $result = login($email, $password);
        
        if ($result['success']) {
            // Redirect based on role
            switch ($result['role']) {
                case 'admin':
                    header("Location: admin/dashboard.php");
                    break;
                case 'instructor':
                    header("Location: instructor/dashboard.php");
                    break;
                case 'chair':
                    header("Location: chair/dashboard.php");
                    break;
                case 'student':
                    header("Location: student/dashboard.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

// Get error from URL if exists
if (isset($_GET['error'])) {
    $error = sanitize($_GET['error']);
}

// Get success message from URL
$success = isset($_GET['success']) ? sanitize($_GET['success']) : '';

// Fetch some stats for the landing page
$conn = getConnection();
$statsQuery = $conn->query("SELECT 
    (SELECT COUNT(*) FROM equipment WHERE status = 'available') as available_count,
    (SELECT COUNT(*) FROM categories) as category_count,
    (SELECT COUNT(*) FROM equipment) as total_equipment
");
$stats = $statsQuery->fetch_assoc();

// Fetch featured equipment (6 items with images)
$featuredQuery = $conn->query("SELECT e.*, c.name as category_name 
    FROM equipment e 
    LEFT JOIN categories c ON e.category_id = c.id 
    WHERE e.status = 'available' AND e.image_path IS NOT NULL AND e.image_path != ''
    ORDER BY RAND() LIMIT 6");
$featuredEquipment = $featuredQuery->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Inventory System - Communication & Design</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-camera-reels me-2"></i>
                <span>Rental Inventory</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="guest/browse.php">
                            <i class="bi bi-grid me-1"></i>Browse Equipment
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <h1 class="display-4 fw-bold mb-4">Communication & Design<br>Equipment Rental</h1>
                    <p class="lead mb-4">Access professional cameras, lighting, audio equipment, and more for your academic projects. Our digital rental system makes borrowing equipment quick and easy.</p>
                    
                    <div class="d-flex gap-3 mb-4">
                        <div class="text-center">
                            <div class="display-5 fw-bold"><?php echo $stats['total_equipment'] ?? 0; ?></div>
                            <small class="text-white-50">Total Equipment</small>
                        </div>
                        <div class="text-center">
                            <div class="display-5 fw-bold"><?php echo $stats['available_count'] ?? 0; ?></div>
                            <small class="text-white-50">Available Now</small>
                        </div>
                        <div class="text-center">
                            <div class="display-5 fw-bold"><?php echo $stats['category_count'] ?? 0; ?></div>
                            <small class="text-white-50">Categories</small>
                        </div>
                    </div>
                    
                    <a href="guest/browse.php" class="btn btn-light btn-lg">
                        <i class="bi bi-search me-2"></i>Browse Equipment
                    </a>
                </div>
                
                <div class="col-lg-5 offset-lg-1">
                    <div class="login-card card shadow-lg">
                        <div class="card-header">
                            <h3><i class="bi bi-box-arrow-in-right me-2"></i>Login to Your Account</h3>
                            <p class="mb-0 opacity-75">Access your dashboard and manage rentals</p>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="index.php" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($email); ?>" 
                                               placeholder="your.email@university.edu" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" 
                                               placeholder="Enter your password" required>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                </button>
                            </form>
                            
                            <hr class="my-4">
                            
                            <div class="text-center">
                                <p class="text-muted mb-2">Quick access for guests:</p>
                                <a href="guest/browse.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-eye me-2"></i>Browse as Guest
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">How It Works</h2>
            
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="dashboard-card text-center h-100">
                        <div class="icon bg-primary-soft mx-auto mb-3">
                            <i class="bi bi-search"></i>
                        </div>
                        <h5 class>Browse Equipment</h5>
                        <p class= "mb-0">Explore our catalog of professional cameras, lighting, audio gear, and more.</p>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="dashboard-card text-center h-100">
                        <div class="icon bg-success-soft mx-auto mb-3">
                            <i class="bi bi-file-text"></i>
                        </div>
                        <h5>Submit Request</h5>
                        <p class="text-muted mb-0">Create a rental request with your course and project details.</p>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="dashboard-card text-center h-100">
                        <div class="icon bg-warning-soft mx-auto mb-3">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                        <h5>Get Approval</h5>
                        <p class="text-muted mb-0">Your instructor and department chair review and approve your request.</p>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="dashboard-card text-center h-100">
                        <div class="icon bg-danger-soft mx-auto mb-3">
                            <i class="bi bi-camera"></i>
                        </div>
                        <h5>Pick Up Equipment</h5>
                        <p class="text-muted mb-0">Collect your equipment from the equipment room after approval.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Equipment Categories Preview -->
    <section class="py-5 bg-white">
        <div class="container">
            <h2 class="text-center mb-2">Equipment Categories</h2>
            <p class="text-center text-muted mb-5">Professional gear for all your creative projects</p>
            
            <div class="row g-4 justify-content-center">
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="text-center">
                        <div class="icon bg-primary-soft mx-auto mb-3" style="width: 80px; height: 80px; border-radius: 50%;">
                            <i class="bi bi-camera" style="font-size: 2rem; line-height: 80px;"></i>
                        </div>
                        <h6>Cameras</h6>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="text-center">
                        <div class="icon bg-success-soft mx-auto mb-3" style="width: 80px; height: 80px; border-radius: 50%;">
                            <i class="bi bi-brightness-high" style="font-size: 2rem; line-height: 80px;"></i>
                        </div>
                        <h6>Lighting</h6>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="text-center">
                        <div class="icon bg-warning-soft mx-auto mb-3" style="width: 80px; height: 80px; border-radius: 50%;">
                            <i class="bi bi-mic" style="font-size: 2rem; line-height: 80px;"></i>
                        </div>
                        <h6>Audio</h6>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="text-center">
                        <div class="icon bg-danger-soft mx-auto mb-3" style="width: 80px; height: 80px; border-radius: 50%;">
                            <i class="bi bi-triangle" style="font-size: 2rem; line-height: 80px;"></i>
                        </div>
                        <h6>Tripods</h6>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="text-center">
                        <div class="icon bg-primary-soft mx-auto mb-3" style="width: 80px; height: 80px; border-radius: 50%;">
                            <i class="bi bi-laptop" style="font-size: 2rem; line-height: 80px;"></i>
                        </div>
                        <h6>Computers</h6>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="text-center">
                        <div class="icon bg-success-soft mx-auto mb-3" style="width: 80px; height: 80px; border-radius: 50%;">
                            <i class="bi bi-airplane" style="font-size: 2rem; line-height: 80px;"></i>
                        </div>
                        <h6>Drones</h6>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Featured Equipment Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-2">Featured Equipment</h2>
            <p class="text-center text-muted mb-5">Explore our selection of professional-grade equipment</p>
            
            <?php if (!empty($featuredEquipment)): ?>
            <div class="row g-4">
                <?php foreach ($featuredEquipment as $item): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="equipment-card h-100">
                        <div class="image-container">
                            <?php if ($item['image_path'] && file_exists('uploads/' . $item['image_path'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($item['image_path']); ?>" 
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
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="equipment-category">
                                    <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?>
                                </span>
                                <span class="badge bg-success">Available</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="guest/browse.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-grid me-2"></i>View All Equipment
                </a>
            </div>
        </div>
    </section>
    
    <!-- Demo Credentials (for testing) -->
    <section class="py-4 bg-light">
        <div class="container">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-info-circle me-2"></i>Demo Credentials (For Testing)
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Admin:</strong><br>
                            <small>admin@university.edu<br>password</small>
                        </div>
                        <div class="col-md-3">
                            <strong>Chair:</strong><br>
                            <small>chair@university.edu<br>password</small>
                        </div>
                        <div class="col-md-3">
                            <strong>Instructor:</strong><br>
                            <small>instructor@university.edu<br>password</small>
                        </div>
                        <div class="col-md-3">
                            <strong>Student:</strong><br>
                            <small>student@university.edu<br>password</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="bi bi-camera-reels me-2"></i>Rental Inventory System</h5>
                    <p class="text-white-50 mb-0">Communication & Design Department</p>
                    <p class="text-white-50">Equipment rental management for students and faculty.</p>
                </div>
                <div class="col-md-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="guest/browse.php" class="text-white-50">Browse Equipment</a></li>
                        <li><a href="index.php" class="text-white-50">Login</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Contact</h6>
                    <ul class="list-unstyled text-white-50">
                        <li><i class="bi bi-geo-alt me-2"></i>Communication Building</li>
                        <li><i class="bi bi-envelope me-2"></i>equipment@university.edu</li>
                        <li><i class="bi bi-telephone me-2"></i>(555) 123-4567</li>
                    </ul>
                </div>
            </div>
            <hr class="my-3">
            <div class="text-center text-white-50">
                <small>&copy; <?php echo date('Y'); ?> Rental Inventory System. All rights reserved.</small>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
