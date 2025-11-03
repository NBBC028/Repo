<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Handle manuscript request submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $research_id = (int)$_POST['research_id'];
    $requester_name = sanitize_input($_POST['requester_name']);
    $requester_id = sanitize_input($_POST['requester_id']);
    $requester_section = sanitize_input($_POST['requester_section']);
    $reason = sanitize_input($_POST['reason']);
    
    // Validate inputs
    if (empty($research_id) || empty($requester_name) || empty($requester_id) || empty($requester_section) || empty($reason)) {
        $_SESSION['message'] = "Please fill in all required fields.";
        $_SESSION['message_type'] = "danger";
        header("Location: search_result.php?id=" . $research_id);
        exit;
    }
    
    // Check if research exists
    $stmt = $conn->prepare("SELECT id FROM research WHERE id = ?");
    $stmt->bind_param("i", $research_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $_SESSION['message'] = "Research not found.";
        $_SESSION['message_type'] = "danger";
        header("Location: index.php");
        exit;
    }
    
    // Check if student ID exists in verification system
    $stmt = $conn->prepare("SELECT id FROM student_verification WHERE student_id = ? AND status = 'approved'");
    $stmt->bind_param("s", $requester_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Student ID not verified, proceed with verification
        $_SESSION['message'] = "Your student ID needs verification. Please complete the verification process.";
        $_SESSION['message_type'] = "warning";
        $_SESSION['temp_request_data'] = [
            'research_id' => $research_id,
            'requester_name' => $requester_name,
            'requester_id' => $requester_id,
            'requester_section' => $requester_section,
            'reason' => $reason
        ];
        header("Location: verify_student.php");
        exit;
    }
    
    // Insert manuscript request
    $stmt = $conn->prepare("INSERT INTO manuscript_requests (research_id, requester_name, requester_id, requester_section, reason) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $research_id, $requester_name, $requester_id, $requester_section, $reason);
    
    if ($stmt->execute()) {
        // Create notification for admin
        $stmt = $conn->prepare("SELECT title FROM research WHERE id = ?");
        $stmt->bind_param("i", $research_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $research = $result->fetch_assoc();
        
        $message = "New manuscript request: " . $research['title'] . " by " . $requester_name;
        create_notification($message, 'manuscript_request');
        
        $_SESSION['message'] = "Your manuscript request has been submitted successfully. You will be notified when it is processed.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to submit manuscript request. Please try again.";
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: search_result.php?id=" . $research_id);
    exit;
}

// If not POST request, redirect to home
header("Location: index.php");
exit;
?>