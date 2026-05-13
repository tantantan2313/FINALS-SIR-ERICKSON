-- Database schema for School Service Transportation Monitoring System

CREATE DATABASE IF NOT EXISTS school_transport;
USE school_transport;

-- Users table (admin, parents, drivers)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'parent', 'driver') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    grade VARCHAR(20),
    address TEXT,
    parent_id INT,
    driver_id INT,
    schedule TEXT, -- JSON or text for pickup/dropoff times
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(id),
    FOREIGN KEY (driver_id) REFERENCES users(id)
);

-- Drivers table (additional info)
CREATE TABLE drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    license_number VARCHAR(50),
    vehicle_type VARCHAR(50),
    vehicle_plate VARCHAR(20),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    rating DECIMAL(2,1) DEFAULT 0,
    qrcode_image VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Trips table
CREATE TABLE trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT,
    student_id INT,
    trip_date DATE,
    pickup_time TIME,
    dropoff_time TIME,
    status ENUM('scheduled', 'picked_up', 'in_transit', 'dropped_off', 'completed') DEFAULT 'scheduled',
    pickup_lat DECIMAL(10,8),
    pickup_lng DECIMAL(11,8),
    dropoff_lat DECIMAL(10,8),
    dropoff_lng DECIMAL(11,8),
    current_lat DECIMAL(10,8),
    current_lng DECIMAL(11,8),
    photo_proof VARCHAR(255), -- file path
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES users(id),
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT,
    data JSON,
    type ENUM('pickup', 'dropoff', 'delay', 'general'),
    title VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT,
    student_id INT,
    amount DECIMAL(10,2),
    status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
    due_date DATE,
    paid_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(id),
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- Insert default admin user
INSERT INTO users (username, password, email, role, full_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@school.com', 'admin', 'System Administrator');
-- Password is 'password' hashed with bcrypt