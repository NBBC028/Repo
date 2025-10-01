<?php
require_once 'includes/session.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect_by_role($_SESSION['role']);
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        header("Location: login.php?error=empty");
        exit;
    }

    // Find user
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // 🔹 Check verification status
        if ($user['verification_status'] === 'pending') {
            header("Location: login.php?error=pending");
            exit;
        } elseif ($user['verification_status'] === 'rejected') {
            header("Location: login.php?error=rejected");
            exit;
        }

        // Verify password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['username']    = $user['username'];
            $_SESSION['email']       = $user['email'];
            $_SESSION['full_name']   = $user['full_name'];
            $_SESSION['role']        = $user['role'];
            $_SESSION['department']  = $user['department'];
            $_SESSION['last_activity'] = time();

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
    header("Location: login.php");
    exit;
}
