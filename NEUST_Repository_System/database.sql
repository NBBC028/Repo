-- Database: neust_repository
CREATE DATABASE IF NOT EXISTS neust_repository;
USE neust_repository;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','faculty','guest') DEFAULT 'guest',
    email VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Research Projects table
CREATE TABLE research_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    authors VARCHAR(255) NOT NULL,
    abstract TEXT NOT NULL,
    manuscript_file_path VARCHAR(255) NOT NULL,
    keywords TEXT,
    department VARCHAR(100) NOT NULL,
    year_published YEAR NOT NULL,
    year_section VARCHAR(50) NOT NULL,
    expert_sampling_description TEXT,
    stratified_sampling_description TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Student Verification table
CREATE TABLE student_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(150) NOT NULL,
    student_id VARCHAR(50) NOT NULL UNIQUE,
    section VARCHAR(50) NOT NULL,
    id_image_path VARCHAR(255) NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Manuscript Access Requests table
CREATE TABLE manuscript_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    research_id INT NOT NULL,
    requester_name VARCHAR(150) NOT NULL,
    requester_id VARCHAR(50) NOT NULL,
    requester_section VARCHAR(50) NOT NULL,
    request_reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (research_id) REFERENCES research_projects(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sample Admin User (password = 'admin123')
INSERT INTO users (username, password, role, email) VALUES
('admin', MD5('admin123'), 'admin', 'admin@neust.edu.ph');
