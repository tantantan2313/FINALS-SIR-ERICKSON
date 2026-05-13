<?php
// Setup script to create database and tables

$servername = "localhost";
$username = "root";
$password = "";

// Create connection without database
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS school_transport";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select database
$conn->select_db("school_transport");

// Create tables
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        role ENUM('admin', 'parent', 'driver') NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        grade VARCHAR(20),
        address TEXT,
        parent_id INT,
        driver_id INT,
        schedule TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES users(id),
        FOREIGN KEY (driver_id) REFERENCES users(id)
    )",

    "CREATE TABLE IF NOT EXISTS drivers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNIQUE,
        license_number VARCHAR(50),
        vehicle_type VARCHAR(50),
        vehicle_plate VARCHAR(20),
        profile_picture VARCHAR(255),
        qrcode_image VARCHAR(255),
        bio TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        rating DECIMAL(2,1) DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )",

    "CREATE TABLE IF NOT EXISTS trips (
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
        photo_proof VARCHAR(255),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (driver_id) REFERENCES users(id),
        FOREIGN KEY (student_id) REFERENCES students(id)
    )",

    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        message TEXT,
        type ENUM('pickup', 'dropoff', 'delay', 'general'),
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )",

    "CREATE TABLE IF NOT EXISTS payments (
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
    )"
];

foreach ($tables as $table_sql) {
    if ($conn->query($table_sql) === TRUE) {
        echo "Table created successfully<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
}

// Insert default admin
$hashed_password = password_hash('password', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (username, password, email, role, full_name) VALUES
('admin', '$hashed_password', 'admin@school.com', 'admin', 'System Administrator')";

if ($conn->query($sql) === TRUE) {
    echo "Default admin user created<br>";
} else {
    echo "Error creating admin user: " . $conn->error . "<br>";
}

$conn->close();

echo "<br>Setup completed! You can now access the system at <a href='index.php'>index.php</a>";
?>