<?php
require_once '../includes/session.php';

// Only admin allowed
if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit;
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action == 'approve') {
        $status = 'approved';
    } elseif ($action == 'reject') {
        $status = 'rejected';
    } else {
        header("Location: admin_verify.php?error=invalid");
        exit;
    }

    $sql = "UPDATE users SET verification_status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        header("Location: admin_verify.php?success=$status");
        exit;
    } else {
        header("Location: admin_verify.php?error=db");
        exit;
    }
} else {
    header("Location: admin_verify.php?error=missing");
    exit;
}
?>
