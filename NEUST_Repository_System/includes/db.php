<?php
/**
 * Database Connection
 * Establishes connection to the MySQL database
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'neust_repository');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === FALSE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db(DB_NAME);

// Create users table if not exists
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'faculty', 'student', 'guest') NOT NULL,
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";

if ($conn->query($sql) === FALSE) {
    die("Error creating users table: " . $conn->error);
}

// Create research table if not exists
$sql = "CREATE TABLE IF NOT EXISTS research (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    abstract TEXT NOT NULL,
    authors VARCHAR(255) NOT NULL,
    year_published YEAR NOT NULL,
    department VARCHAR(100) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    status ENUM('public', 'restricted') NOT NULL DEFAULT 'public',
    keywords VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB";

if ($conn->query($sql) === FALSE) {
    die("Error creating research table: " . $conn->error);
}

// Insert default admin user if not exists
$check_admin = "SELECT * FROM users WHERE username = 'admin'";
$result = $conn->query($check_admin);

if ($result && $result->num_rows == 0) {
    $default_password = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, password, email, full_name, role, department) 
            VALUES ('admin', '$default_password', 'admin@neust.edu.ph', 'System Administrator', 'admin', 'IT Department')";
    
    if ($conn->query($sql) === FALSE) {
        die("Error creating default admin: " . $conn->error);
    }
}
?>
