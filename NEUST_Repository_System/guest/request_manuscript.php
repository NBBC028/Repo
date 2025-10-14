<?php
// Initialize session
session_start();

// Include database connection
require_once '../includes/db_connection.php';

// Set upload directory for student IDs
$upload_dir = '../uploads/student_ids/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $research_id = isset($_POST['research_id']) ? intval($_POST['research_id']) : 0;
    $student_name = isset($_POST['student_name']) ? trim($_POST['student_name']) : '';
    $student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $department = isset($_POST['department']) ? trim($_POST['department']) : '';
    $purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';
    $other_purpose = isset($_POST['other_purpose']) ? trim($_POST['other_purpose']) : '';
    
    // Validate required fields
    $errors = [];
    if (empty($research_id)) $errors[] = "Research ID is required";
    if (empty($student_name)) $errors[] = "Student name is required";
    if (empty($student_id)) $errors[] = "Student ID is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($department)) $errors[] = "Department is required";
    if (empty($purpose)) $errors[] = "Purpose is required";
    if ($purpose === 'Other' && empty($other_purpose)) $errors[] = "Please specify the purpose";
    
    // Validate file upload
    if (!isset($_FILES['id_card']) || $_FILES['id_card']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Student ID image is required";
    } else {
        $file_info = pathinfo($_FILES['id_card']['name']);
        $file_extension = strtolower($file_info['extension']);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed";
        }
        
        if ($_FILES['id_card']['size'] > 5000000) { // 5MB limit
            $errors[] = "File size must be less than 5MB";
        }
    }
    
    // Check if research exists
    $stmt = $conn->prepare("SELECT id FROM research WHERE id = ?");
    $stmt->bind_param("i", $research_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $errors[] = "Research not found";
    }
    $stmt->close();
    
    // Check if student has already requested this manuscript
    $stmt = $conn->prepare("SELECT id, status FROM manuscript_requests WHERE research_id = ? AND student_id = ?");
    $stmt->bind_param("is", $research_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $request = $result->fetch_assoc();
        if ($request['status'] === 'approved') {
            // Redirect to view manuscript if already approved
            header("Location: view_manuscript.php?request_id=" . $request['id'] . "&verification=" . md5($student_id));
            exit;
        } elseif ($request['status'] === 'pending') {
            $errors[] = "You already have a pending request for this manuscript";
        } elseif ($request['status'] === 'rejected') {
            $errors[] = "Your previous request for this manuscript was rejected. Please contact the administrator.";
        }
    }
    $stmt->close();
    
    // Process if no errors
    if (empty($errors)) {
        // Upload student ID image
        $file_name = time() . '_' . $student_id . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['id_card']['tmp_name'], $file_path)) {
            // Insert request into database
            $final_purpose = ($purpose === 'Other') ? $other_purpose : $purpose;
            
            $stmt = $conn->prepare("INSERT INTO manuscript_requests (research_id, student_id, student_name, email, department, purpose, id_card_path, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->bind_param("issssss", $research_id, $student_id, $student_name, $email, $department, $final_purpose, $file_path);
            
            if ($stmt->execute()) {
                // Set success message
                $_SESSION['success_message'] = "Your manuscript request has been submitted successfully. You will be notified via email once it's approved.";
                header("Location: dashboard.php");
                exit;
            } else {
                $errors[] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Failed to upload student ID image";
        }
    }
    
    // If there are errors, store them in session and redirect back
    if (!empty($errors)) {
        $_SESSION['error_messages'] = $errors;
        header("Location: dashboard.php");
        exit;
    }
} else {
    // Redirect if not POST request
    header("Location: dashboard.php");
    exit;
}
?>