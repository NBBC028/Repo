<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Restrict access to student only
restrict_access(['student']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $research_id = isset($_POST['research_id']) ? intval($_POST['research_id']) : 0;
    $encoded_student_id = isset($_POST['student_id']) ? $_POST['student_id'] : '';
    $purpose = isset($_POST['purpose']) ? $conn->real_escape_string($_POST['purpose']) : '';
    $other_purpose = isset($_POST['other_purpose']) ? $conn->real_escape_string($_POST['other_purpose']) : '';
    
    // Decode student ID and verify it matches the session user
    $decoded_student_id = base64_decode($encoded_student_id);
    
    if ($decoded_student_id != $_SESSION['user_id']) {
        set_message('error', 'Invalid student verification. Please try again.');
        header('Location: dashboard.php');
        exit;
    }
    
    // Check if research exists
    $check_research = $conn->prepare("SELECT id, title FROM research WHERE id = ? AND status = 'public'");
    $check_research->bind_param('i', $research_id);
    $check_research->execute();
    $research_result = $check_research->get_result();
    
    if ($research_result->num_rows === 0) {
        set_message('error', 'Research not found or not available.');
        header('Location: dashboard.php');
        exit;
    }
    
    $research = $research_result->fetch_assoc();
    
    // Check if request already exists
    $check_request = $conn->prepare("SELECT id, status FROM manuscript_requests 
                                    WHERE research_id = ? AND student_id = ?");
    $check_request->bind_param('ii', $research_id, $_SESSION['user_id']);
    $check_request->execute();
    $request_result = $check_request->get_result();
    
    if ($request_result->num_rows > 0) {
        $existing_request = $request_result->fetch_assoc();
        if ($existing_request['status'] === 'approved') {
            // Redirect to view approved manuscript
            header('Location: view_manuscript.php?id=' . $research_id . '&request=' . $existing_request['id']);
            exit;
        } elseif ($existing_request['status'] === 'pending') {
            set_message('info', 'You already have a pending request for this manuscript.');
            header('Location: dashboard.php');
            exit;
        } elseif ($existing_request['status'] === 'rejected') {
            // Allow resubmission if previously rejected
            $update_request = $conn->prepare("UPDATE manuscript_requests 
                                            SET purpose = ?, other_purpose = ?, status = 'pending', 
                                                created_at = NOW(), updated_at = NOW() 
                                            WHERE id = ?");
            $update_request->bind_param('ssi', $purpose, $other_purpose, $existing_request['id']);
            $update_request->execute();
            
            set_message('success', 'Your manuscript request has been resubmitted and is pending approval.');
            header('Location: dashboard.php');
            exit;
        }
    }
    
    // Create new request
    $insert_request = $conn->prepare("INSERT INTO manuscript_requests 
                                    (research_id, student_id, purpose, other_purpose, status, created_at, updated_at) 
                                    VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())");
    $insert_request->bind_param('iiss', $research_id, $_SESSION['user_id'], $purpose, $other_purpose);
    
    if ($insert_request->execute()) {
        set_message('success', 'Your manuscript request has been submitted and is pending approval.');
    } else {
        set_message('error', 'Failed to submit request. Please try again.');
    }
    
    header('Location: dashboard.php');
    exit;
} else {
    // Redirect if accessed directly without POST
    header('Location: dashboard.php');
    exit;
}
?>