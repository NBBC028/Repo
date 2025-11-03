<?php
require_once '../includes/db_connect.php';
session_start();

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'] === 'approve' ? 'Approved' : 'Rejected';

    $stmt = $conn->prepare("UPDATE student_requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $action, $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success_message'] = "Request has been {$action} successfully.";
}

header("Location: dashboard.php");
exit;
?>
