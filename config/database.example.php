<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'YOUR_DB_USERNAME');
define('DB_PASS', 'YOUR_DB_PASSWORD');
define('DB_NAME', 'rental_inventory');

function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

define('MAIL_FROM', 'noreply@rentalinventory.edu');
define('MAIL_FROM_NAME', 'Rental Inventory System');

define('SITE_URL', 'http://localhost/rental-inventory');
define('SITE_NAME', 'Communication & Design Equipment Rental');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
?>