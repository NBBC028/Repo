<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $department = $_POST['department'];
    $year_section = !empty($_POST['year_section']) ? $_POST['year_section'] : NULL;
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate required fields
    if (empty($full_name) || empty($username) || empty($email) || empty($role) || empty($department) || empty($password) || empty($confirm_password)) {
        header("Location: register.php?error=empty");
        exit;
    }

    // Password confirmation check
    if ($password !== $confirm_password) {
        header("Location: register.php?error=password");
        exit;
    }

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        header("Location: register.php?error=username");
        exit;
    }
    $stmt->close();

    // File upload handling (Verification ID)
    if (isset($_FILES['verification_id']) && $_FILES['verification_id']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $file_name = $_FILES['verification_id']['name'];
        $file_tmp = $_FILES['verification_id']['tmp_name'];
        $file_size = $_FILES['verification_id']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed) || $file_size > 2 * 1024 * 1024) { // 2MB limit
            header("Location: register.php?error=file");
            exit;
        }

        $upload_dir = "uploads/ids/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $new_file_name = uniqid("id_") . "." . $file_ext;
        $file_path = $upload_dir . $new_file_name;

        if (!move_uploaded_file($file_tmp, $file_path)) {
            header("Location: register.php?error=file");
            exit;
        }
    } else {
        header("Location: register.php?error=file");
        exit;
    }

    // Determine status based on role
    // Faculty = Pending approval
    // Student = Auto approved
    $status = ($role === 'faculty') ? 'Pending' : 'Approved';

    // Hash password securely
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO users 
        (full_name, username, email, role, department, year_section, verification_id, password, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssssssss", 
        $full_name, $username, $email, $role, $department, $year_section, $file_path, $hashed_password, $status
    );

    if ($stmt->execute()) {
        // If registration success
        if ($role === 'faculty') {
            header("Location: register.php?success=pending"); // show “awaiting approval” message
        } else {
            header("Location: login.php?success=registered");
        }
        exit;
    } else {
        header("Location: register.php?error=unknown");
        exit;
    }
}
?>
