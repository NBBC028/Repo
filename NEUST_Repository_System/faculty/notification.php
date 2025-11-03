<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Restrict access to logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: /NEUST_REPOSITORY_SYSTEM/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'student'; // default to student if role not set

// ------------------------
// Handle mark as read
// ------------------------
if (isset($_GET['mark_read']) && !empty($_GET['mark_read'])) {
    $notif_id = (int)$_GET['mark_read'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_type=?");
    $stmt->bind_param("is", $notif_id, $role);
    $stmt->execute();
    header("Location: notifications.php");
    exit;
}

// Handle mark all as read
if (isset($_GET['mark_all'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE user_type=?");
    $stmt->bind_param("s", $role);
    $stmt->execute();
    header("Location: notifications.php");
    exit;
}

// ------------------------
// Fetch notifications
// ------------------------
$notif_query = $conn->prepare("SELECT * FROM notifications WHERE user_type=? ORDER BY created_at DESC");
$notif_query->bind_param("s", $role);
$notif_query->execute();
$notif_result = $notif_query->get_result();

?>
<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Notifications</h3>
        <a href="notifications.php?mark_all=1" class="btn btn-sm btn-primary">Mark All as Read</a>
    </div>

    <?php if ($notif_result->num_rows > 0): ?>
        <ul class="list-group">
            <?php while ($notif = $notif_result->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between align-items-start <?= $notif['is_read'] == 0 ? 'list-group-item-warning' : '' ?>">
                    <div class="ms-2 me-auto">
                        <div><?= htmlspecialchars($notif['message']); ?></div>
                        <small class="text-muted"><?= date('M d, Y H:i', strtotime($notif['created_at'])); ?></small>
                    </div>
                    <?php if ($notif['is_read'] == 0): ?>
                        <a href="notifications.php?mark_read=<?= $notif['id'] ?>" class="btn btn-sm btn-success">Mark Read</a>
                    <?php endif; ?>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <div class="alert alert-info">No notifications found.</div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
