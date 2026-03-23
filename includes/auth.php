<?php
/**
 * Authentication Functions
 * Handles login, logout, and session management
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Attempt to login a user
 */
function login($email, $password) {
    $conn = getConnection();
    
    $stmt = $conn->prepare("SELECT id, email, password, first_name, last_name, role, is_active FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (!$user['is_active']) {
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Your account has been deactivated. Please contact administrator.'];
        }
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            $stmt->close();
            $conn->close();
            return ['success' => true, 'role' => $user['role']];
        }
    }
    
    $stmt->close();
    $conn->close();
    return ['success' => false, 'message' => 'Invalid email or password.'];
}

/**
 * Logout the current user
 */
function logout() {
    session_unset();
    session_destroy();
    header("Location: ../index.php");
    exit();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Get current user's role
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user's ID
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user's full name
 */
function getUserName() {
    if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
        return $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    }
    return 'Guest';
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../index.php?error=Please login to access this page");
        exit();
    }
}

/**
 * Require specific role(s)
 */
function requireRole($roles) {
    requireLogin();
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    if (!in_array(getUserRole(), $roles)) {
        header("Location: ../index.php?error=You don't have permission to access this page");
        exit();
    }
}

/**
 * Redirect to appropriate dashboard based on role
 */
function redirectToDashboard() {
    $role = getUserRole();
    
    switch ($role) {
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
}

/**
 * Hash a password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>