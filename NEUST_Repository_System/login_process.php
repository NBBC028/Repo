<?php
require_once 'includes/session.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect_by_role($_SESSION['role']);
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    // Validate form data
    if (empty($username) || empty($password)) {
        header("Location: login.php?error=empty");
        exit;
    }
    
    // Check if user exists
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['last_activity'] = time();
            
            // Redirect to appropriate dashboard
            redirect_by_role($user['role']);
        } else {
            header("Location: login.php?error=invalid");
            exit;
        }
    } else {
        header("Location: login.php?error=invalid");
        exit;
    }
} else {
    // Redirect to login page if accessed directly
    header("Location: login.php");
    exit;
}
?>