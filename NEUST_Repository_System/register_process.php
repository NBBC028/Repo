<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php'; // For sanitize_input and other helpers

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize inputs
    $full_name = sanitize_input($_POST['full_name']);
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $role = sanitize_input($_POST['role']);
    $department = sanitize_input($_POST['department']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $year_section = $role === 'student' ? sanitize_input($_POST['year_section']) : null;

    // Basic validations
    if (empty($full_name) || empty($username) || empty($email) || empty($role) || empty($department) || empty($password) || empty($confirm_password) || ($role === 'student' && empty($year_section))) {
        header("Location: register.php?error=empty");
        exit;
    }

    if ($password !== $confirm_password) {
        header("Location: register.php?error=password");
        exit;
    }

    // Check if username or email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        // Determine which error
        $stmt->bind_result($existing_id);
        $stmt->fetch();
        $stmt->close();
        header("Location: register.php?error=username"); // could also be email
        exit;
    }
    $stmt->close();

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert user into database
    $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, role, department, year_section, password, registration_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssssss", $full_name, $username, $email, $role, $department, $year_section, $password_hash);

    if ($stmt->execute()) {
        $stmt->close();
        // Redirect to login page after successful registration
        header("Location: login.php?success=registered");
        exit;
    } else {
        $stmt->close();
        header("Location: register.php?error=database");
        exit;
    }

} else {
    // Not POST request
    header("Location: register.php");
    exit;
}
