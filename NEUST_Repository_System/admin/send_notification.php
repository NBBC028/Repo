<?php
require_once '../includes/db.php';
require_once '../includes/session.php';

// Restrict to admin only
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_type = $_POST['user_type'];
    $recipient_id = $_POST['recipient_id'];
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);

    if (!empty($title) && !empty($message) && !empty($user_type)) {
        // Broadcast to all users of a role
        if ($recipient_id === "all") {
            if ($user_type === 'faculty') {
                $result = $conn->query("SELECT id FROM faculty");
            } elseif ($user_type === 'student') {
                $result = $conn->query("SELECT id FROM students");
            } else {
                $result = false;
            }

            if ($result && $result->num_rows > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO notifications (recipient_id, user_type, title, message, is_read)
                    VALUES (?, ?, ?, ?, 0)
                ");

                while ($row = $result->fetch_assoc()) {
                    $rid = $row['id'];
                    $stmt->bind_param("isss", $rid, $user_type, $title, $message);
                    $stmt->execute();
                }

                $stmt->close();
            }
        } else {
            // Send to a specific recipient
            $recipient_id = intval($recipient_id);
            $stmt = $conn->prepare("
                INSERT INTO notifications (recipient_id, user_type, title, message, is_read)
                VALUES (?, ?, ?, ?, 0)
            ");
            $stmt->bind_param("isss", $recipient_id, $user_type, $title, $message);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: dashboard.php?msg=NotificationSent");
        exit();
    } else {
        echo "All fields are required.";
    }
}
?>
