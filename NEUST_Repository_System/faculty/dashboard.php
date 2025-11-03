<?php
require_once '../includes/session.php';
require_once '../includes/db.php';

// Restrict access to faculty only
restrict_access(['faculty']);

$faculty_id = $_SESSION['user_id'];

// Fetch faculty research
$faculty_research = $conn->query("
    SELECT * 
    FROM research 
    WHERE uploaded_by = $faculty_id 
    ORDER BY created_at DESC
");

// Count total research
$total_research = $conn->query("
    SELECT COUNT(*) as count 
    FROM research 
    WHERE uploaded_by = $faculty_id
")->fetch_assoc()['count'];

// Fetch unread notifications
$notifications = $conn->query("
    SELECT * FROM notifications 
    WHERE user_id = $faculty_id 
    ORDER BY created_at DESC 
    LIMIT 5
");

$unread_count = $conn->query("
    SELECT COUNT(*) AS count 
    FROM notifications 
    WHERE user_id = $faculty_id AND is_read = 0
")->fetch_assoc()['count'];
?>

<?php include '../includes/header.php'; ?>

<!-- Notification Bell Script -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    const bell = document.getElementById("notificationBell");
    const dropdown = document.getElementById("notificationDropdown");

    bell.addEventListener("click", () => {
        dropdown.classList.toggle("show");
        // Mark all as read when clicked
        fetch("mark_notifications_read.php").then(() => {
            document.getElementById("notifCount").style.display = "none";
        });
    });

    // Hide dropdown when clicking outside
    document.addEventListener("click", (e) => {
        if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove("show");
        }
    });
});
</script>

<style>
.notification-bell {
    position: relative;
    cursor: pointer;
    margin-left: 15px;
}
.notification-bell i {
    font-size: 20px;
    color: white;
}
.notification-count {
    position: absolute;
    top: -5px;
    right: -8px;
    background: red;
    color: white;
    border-radius: 50%;
    font-size: 12px;
    padding: 2px 6px;
}
.notification-dropdown {
    position: absolute;
    top: 45px;
    right: 0;
    width: 320px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    display: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    z-index: 1000;
}
.notification-dropdown.show {
    display: block;
}
.notification-dropdown ul {
    list-style: none;
    margin: 0;
    padding: 0;
}
.notification-dropdown li {
    padding: 10px 15px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
}
.notification-dropdown li:hover {
    background: #f5f5f5;
}
.notification-dropdown .no-notif {
    padding: 15px;
    text-align: center;
    color: #777;
}
</style>

<!-- ===== Faculty Dashboard ===== -->
<div class="container-fluid dashboard-container dashboard-bg">
    <!-- Header -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Faculty Dashboard</h3>
            <div class="d-flex align-items-center">
                <!-- Upload Link -->
                <a href="../views/upload_research.php" class="btn btn-light btn-sm me-2">
                    <i class="fas fa-upload"></i> Upload Research
                </a>

                <!-- Notification Bell -->
                <div class="notification-bell" id="notificationBell">
                    <i class="fas fa-bell"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-count" id="notifCount"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                    <div class="notification-dropdown" id="notificationDropdown">
                        <ul>
                            <?php if ($notifications->num_rows > 0): ?>
                                <?php while ($notif = $notifications->fetch_assoc()): ?>
                                    <li>
                                        <?php echo htmlspecialchars($notif['message']); ?><br>
                                        <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></small>
                                    </li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li class="no-notif">No new notifications</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">My Research Papers</h5>
                    <h2 class="display-4"><?php echo $total_research; ?></h2>
                    <a href="../views/view_uploads.php" class="btn btn-light btn-sm mt-2">View All Uploads</a>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4 mb-3">
                            <a href="../views/upload_research.php" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-upload fa-2x mb-2"></i><br>Upload Research
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="../views/search.php" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-search fa-2x mb-2"></i><br>Search Repository
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="#" class="btn btn-primary btn-lg w-100" data-bs-toggle="modal" data-bs-target="#profileModal">
                                <i class="fas fa-user-edit fa-2x mb-2"></i><br>Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Research Table -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">My Research Papers</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>Title</th>
                            <th>Authors</th>
                            <th>Year</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Date Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($faculty_research && $faculty_research->num_rows > 0): ?>
                            <?php while ($research = $faculty_research->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($research['title']); ?></td>
                                    <td><?php echo htmlspecialchars($research['authors']); ?></td>
                                    <td><?php echo htmlspecialchars($research['year_published']); ?></td>
                                    <td><?php echo htmlspecialchars($research['department']); ?></td>
                                    <td>
                                        <?php
                                        $status = strtolower($research['status']);
                                        if ($status == 'approved' || $status == 'public') {
                                            echo '<span class="badge bg-success">Approved</span>';
                                        } elseif ($status == 'rejected') {
                                            echo '<span class="badge bg-danger">Rejected</span>';
                                        } else {
                                            echo '<span class="badge bg-warning text-dark">Pending</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($research['created_at'])); ?></td>
                                    <td>
                                        <a href="../<?php echo htmlspecialchars($research['file_path']); ?>" class="btn btn-sm btn-primary" target="_blank">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted">No research uploaded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-3">
                <a href="../views/upload_research.php" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload New Research
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
