-- Database: neust_repository
CREATE DATABASE IF NOT EXISTS neust_repository;
USE neust_repository;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','faculty','student','guest') DEFAULT 'guest',
    email VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Research Projects table
CREATE TABLE research_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(150) NOT NULL,
    adviser VARCHAR(150) NOT NULL,
    abstract TEXT NOT NULL,
    keywords TEXT,
    department VARCHAR(100) NOT NULL,
    year_completed YEAR NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Sample Admin User (password = 'admin123')
INSERT INTO users (username, password, role, email) VALUES
('admin', MD5('admin123'), 'admin', 'admin@neust.edu.ph');
