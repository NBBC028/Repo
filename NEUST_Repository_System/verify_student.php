<?php
require_once 'includes/db_connect.php'; // your database connection file
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $research_id = intval($_POST['research_id']);
    $student_name = mysqli_real_escape_string($conn, $_POST['student_name']);
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $section = mysqli_real_escape_string($conn, $_POST['section']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);

    // File upload
    $uploadDir = 'uploads/student_ids/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = basename($_FILES['id_photo']['name']);
    $targetFile = $uploadDir . time() . "_" . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    $allowedTypes = ['jpg', 'jpeg', 'png'];

    if (!in_array($fileType, $allowedTypes)) {
        $_SESSION['error_messages'] = ['Only JPG, JPEG, or PNG files are allowed.'];
        header("Location: student/dashboard.php");
        exit;
    }

    if (move_uploaded_file($_FILES['id_photo']['tmp_name'], $targetFile)) {
        $stmt = $conn->prepare("INSERT INTO student_requests (research_id, student_name, student_id, section, id_photo, reason) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $research_id, $student_name, $student_id, $section, $targetFile, $reason);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Your verification request has been submitted successfully. Please wait for admin approval.";
        } else {
            $_SESSION['error_messages'] = ["Database error: " . $stmt->error];
        }

        $stmt->close();
    } else {
        $_SESSION['error_messages'] = ["Failed to upload your student ID photo."];
    }
}

header("Location: student/dashboard.php");
exit;
?>
