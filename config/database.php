<?php

define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'rental_inventory');
define('DB_PORT', (int)(getenv('MYSQLPORT') ?: 3306));

function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

define('MAIL_FROM', 'noreply@rentalinventory.edu');
define('MAIL_FROM_NAME', 'Rental Inventory System');

define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/rental-inventory');
define('SITE_NAME', 'Communication & Design Equipment Rental');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
?>