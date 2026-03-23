-- Rental Inventory Database Setup
-- Communication & Design Department Equipment Rental System

CREATE DATABASE IF NOT EXISTS rental_inventory;
USE rental_inventory;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'instructor', 'chair', 'student') NOT NULL,
    student_id VARCHAR(50) NULL,
    department VARCHAR(100) DEFAULT 'Communication and Design',
    phone VARCHAR(20) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Locations Table
CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    building VARCHAR(100),
    room VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Equipment Items Table
CREATE TABLE IF NOT EXISTS equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    brand VARCHAR(100),
    model VARCHAR(100),
    serial_no VARCHAR(100) UNIQUE,
    cost DECIMAL(10, 2),
    category_id INT,
    location_id INT,
    image_path VARCHAR(255),
    status ENUM('available', 'checked_out', 'maintenance', 'retired') DEFAULT 'available',
    total_borrow_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
);

-- Courses Table
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    instructor_id INT,
    semester VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Rental Requests Table
CREATE TABLE IF NOT EXISTS rental_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    equipment_id INT NOT NULL,
    course_id INT,
    purpose TEXT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date DATE NOT NULL,
    checkout_date DATE NULL,
    return_date DATE NULL,
    instructor_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    instructor_comment TEXT,
    instructor_action_date TIMESTAMP NULL,
    chair_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    chair_comment TEXT,
    chair_action_date TIMESTAMP NULL,
    overall_status ENUM('pending', 'approved', 'rejected', 'checked_out', 'returned', 'cancelled') DEFAULT 'pending',
    admin_checkout_by INT NULL,
    admin_return_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_checkout_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_return_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Item Subscriptions (for notifications when item becomes available)
CREATE TABLE IF NOT EXISTS item_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    equipment_id INT NOT NULL,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notified TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    UNIQUE KEY unique_subscription (user_id, equipment_id)
);

-- Email Notifications Log
CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Predefined Comments for Approval/Rejection
CREATE TABLE IF NOT EXISTS predefined_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comment_text TEXT NOT NULL,
    comment_type ENUM('approval', 'rejection') NOT NULL,
    role ENUM('instructor', 'chair') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Borrow History (for tracking who borrowed items)
CREATE TABLE IF NOT EXISTS borrow_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    user_id INT NOT NULL,
    rental_request_id INT NOT NULL,
    checkout_date DATE NOT NULL,
    return_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (rental_request_id) REFERENCES rental_requests(id) ON DELETE CASCADE
);

-- Insert Default Categories
INSERT INTO categories (name, description) VALUES
('Cameras', 'DSLR, Mirrorless, and Video Cameras'),
('Lenses', 'Camera Lenses and Accessories'),
('Tripods & Stabilizers', 'Tripods, Gimbals, and Stabilization Equipment'),
('Lighting', 'Studio Lights, LED Panels, and Light Modifiers'),
('Audio', 'Microphones, Recorders, and Audio Accessories'),
('Computers', 'Laptops and Desktop Computers'),
('Monitors', 'Display Monitors and Reference Displays'),
('Storage', 'Memory Cards, Hard Drives, and Storage Devices'),
('Accessories', 'Cables, Batteries, and Other Accessories'),
('Drones', 'Aerial Photography and Videography Equipment');

-- Insert Default Locations
INSERT INTO locations (name, building, room) VALUES
('Main Equipment Room', 'Communication Building', 'Room 101'),
('Studio A', 'Media Center', 'Studio A'),
('Studio B', 'Media Center', 'Studio B'),
('Computer Lab', 'Communication Building', 'Room 205'),
('Storage Room', 'Communication Building', 'Room B1');

-- Insert Default Admin User (password: admin123)
INSERT INTO users (email, password, first_name, last_name, role) VALUES
('admin@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin');

-- Insert Sample Chair User (password: chair123)
INSERT INTO users (email, password, first_name, last_name, role) VALUES
('chair@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Department', 'Chair', 'chair');

-- Insert Sample Instructor (password: instructor123)
INSERT INTO users (email, password, first_name, last_name, role) VALUES
('instructor@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', 'instructor');

-- Insert Sample Student (password: student123)
INSERT INTO users (email, password, first_name, last_name, role, student_id) VALUES
('student@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Doe', 'student', '2021001234');

-- Insert Sample Courses
INSERT INTO courses (course_code, course_name, instructor_id, semester, is_active) VALUES
('COMM301', 'Video Production', 3, 'Fall 2024', 1),
('COMM302', 'Photography Fundamentals', 3, 'Fall 2024', 1),
('COMM401', 'Advanced Cinematography', 3, 'Fall 2024', 1),
('COMM202', 'Digital Media Design', 3, 'Fall 2024', 1);

-- Insert Sample Equipment
INSERT INTO equipment (name, description, brand, model, serial_no, cost, category_id, location_id, status, image_path) VALUES
('Canon EOS R5 Mark II', 'Professional Full-Frame Mirrorless Camera with 45MP sensor, 8K video recording, and advanced autofocus system', 'Canon', 'EOS R5 Mark II', 'CN-R5-001', 3899.00, 1, 1, 'available', 'canon-eos-r5.jpg'),
('Sony A7 IV', 'Full-Frame Mirrorless Camera for Photo and Video with 33MP sensor and real-time Eye AF', 'Sony', 'A7 IV', 'SN-A7IV-001', 2498.00, 1, 1, 'available', 'sony-a7iv.webp'),
('Canon RF 24-70mm f/2.8L IS USM', 'Professional Standard Zoom Lens with Image Stabilization for RF Mount', 'Canon', 'RF 24-70mm f/2.8L IS USM', 'CN-RF2470-001', 2299.00, 2, 1, 'available', 'canon-rf-24-70.jpg'),
('Manfrotto 504X Fluid Video Head', 'Professional Video Tripod Head with Fluid Drag System, supports up to 12kg', 'Manfrotto', '504X', 'MF-504X-001', 599.00, 3, 1, 'available', 'manfrotto-504x.jpg'),
('DJI Ronin-S', '3-Axis Gimbal Stabilizer for DSLR and Mirrorless cameras, max payload 3.6kg', 'DJI', 'Ronin-S', 'DJI-RS-001', 749.00, 3, 1, 'available', 'dji-ronin-s.avif'),
('Godox SL-200W II', 'LED Video Light 200W with Bowens Mount, 5600K daylight balanced', 'Godox', 'SL-200W II', 'GX-SL200-001', 299.00, 4, 2, 'available', 'godox-sl200w.jpg'),
('Aputure 120D II', 'LED Daylight Fixture with 135W output, CRI 97+, quiet fan', 'Aputure', '120D II', 'AP-120D-001', 745.00, 4, 2, 'available', 'aputure-120d.jpg'),
('Rode NTG5', 'Broadcast-Grade Shotgun Microphone with ultra-lightweight design', 'Rode', 'NTG5', 'RD-NTG5-001', 499.00, 5, 1, 'available', 'rode-ntg5.jpg'),
('Zoom H6', '6-Track Portable Handy Recorder with interchangeable mic capsules', 'Zoom', 'H6', 'ZM-H6-001', 349.00, 5, 1, 'available', 'zoom-h6.jpg'),
('MacBook Pro 16"', 'Apple M3 Pro Laptop with 16" Liquid Retina XDR display for Video Editing', 'Apple', 'MacBook Pro 16" M3 Pro', 'AP-MBP16-001', 2499.00, 6, 4, 'available', 'macbook-pro-16.jpeg'),
('BenQ PD2700U', '27" 4K UHD Designer Monitor with HDR10, 100% sRGB and Rec.709', 'BenQ', 'PD2700U', 'BQ-PD27-001', 549.00, 7, 4, 'available', 'benq-pd2700u.jpg'),
('SanDisk Extreme Pro 256GB', 'CFexpress Type B Memory Card with 1700MB/s read speed', 'SanDisk', 'Extreme Pro 256GB CFexpress', 'SD-CFE256-001', 399.00, 8, 1, 'available', 'sandisk-extreme-pro.avif'),
('DJI Mavic 3', 'Professional Drone with Hasselblad Camera, 4/3 CMOS sensor, 46min flight time', 'DJI', 'Mavic 3', 'DJI-MV3-001', 2049.00, 10, 3, 'available', 'dji-mavic-3.avif'),
('Sony FX3', 'Cinema Line Full-Frame Camera with S-Cinetone, 4K 120fps, active cooling', 'Sony', 'FX3', 'SN-FX3-001', 3898.00, 1, 1, 'available', 'sony-fx3.webp'),
('Sennheiser MKE 600', 'Shotgun Microphone with switchable low-cut filter and battery/phantom power', 'Sennheiser', 'MKE 600', 'SN-MKE600-001', 329.00, 5, 1, 'available', 'sennheiser-mke600.jpg');

-- Insert Predefined Comments
INSERT INTO predefined_comments (comment_text, comment_type, role) VALUES
('Approved for course project use.', 'approval', 'instructor'),
('Equipment needed for assignment - approved.', 'approval', 'instructor'),
('Good reason for borrowing - approved.', 'approval', 'instructor'),
('Not related to course curriculum.', 'rejection', 'instructor'),
('Please provide more details about usage.', 'rejection', 'instructor'),
('Equipment already reserved for class.', 'rejection', 'instructor'),
('Approved by Department Chair.', 'approval', 'chair'),
('Approved for departmental project.', 'approval', 'chair'),
('Request approved - please handle with care.', 'approval', 'chair'),
('Budget constraints - please try alternative equipment.', 'rejection', 'chair'),
('Equipment reserved for priority project.', 'rejection', 'chair'),
('Request needs instructor approval first.', 'rejection', 'chair');
