<?php
require_once '../includes/session.php';
require_once '../includes/db.php';

// Restrict access to admin only
restrict_access(['admin']);

// Handle research approval
if (isset($_GET['approve']) && !empty($_GET['approve'])) {
    $research_id = (int)$_GET['approve'];
    $stmt = $conn->prepare("UPDATE research SET status = 'public' WHERE id = ?");
    $stmt->bind_param("i", $research_id);
    $stmt->execute();
    $_SESSION['message'] = "Research approved successfully.";
    $_SESSION['message_type'] = "success";
    header("Location: dashboard.php");
    exit;
}

// Handle research deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $research_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM research WHERE id = ?");
    $stmt->bind_param("i", $research_id);
    $stmt->execute();
    $_SESSION['message'] = "Research deleted successfully.";
    $_SESSION['message_type'] = "danger";
    header("Location: dashboard.php");
    exit;
}

// Handle user verification/rejection
if (isset($_GET['verify_user']) && !empty($_GET['verify_user'])) {
    $user_id = (int)$_GET['verify_user'];
    $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $_SESSION['message'] = "User verified successfully.";
    $_SESSION['message_type'] = "success";
    header("Location: dashboard.php");
    exit;
}

if (isset($_GET['reject_user']) && !empty($_GET['reject_user'])) {
    $user_id = (int)$_GET['reject_user'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $_SESSION['message'] = "User rejected and removed.";
    $_SESSION['message_type'] = "danger";
    header("Location: dashboard.php");
    exit;
}

// Statistics
$total_research  = $conn->query("SELECT COUNT(*) as count FROM research")->fetch_assoc()['count'];
$total_users     = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_faculty   = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'faculty'")->fetch_assoc()['count'];
$total_students  = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'")->fetch_assoc()['count'];

// Top departments
$top_departments = $conn->query("
    SELECT department, COUNT(*) as count 
    FROM research 
    GROUP BY department 
    ORDER BY count DESC 
    LIMIT 5
");
?>

<?php include '../includes/header.php'; ?>

<style>
body {
    display: flex;
    background-color: #f8f9fa;
}

.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background-color: #003366;
    color: white;
    display: flex;
    flex-direction: column;
    padding-top: 20px;
    transition: all 0.3s ease;
}

.sidebar h3 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 20px;
    letter-spacing: 1px;
}

.sidebar a {
    padding: 15px 25px;
    color: white;
    text-decoration: none;
    display: block;
    font-size: 15px;
    transition: background 0.3s;
}

.sidebar a:hover, .sidebar a.active {
    background-color: #004080;
}

.main-content {
    margin-left: 250px;
    padding: 20px;
    width: 100%;
    transition: all 0.3s ease;
}

.toggle-btn {
    position: fixed;
    top: 15px;
    left: 260px;
    background-color: #003366;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    z-index: 999;
    transition: left 0.3s ease;
}

.sidebar.collapsed {
    width: 0;
    overflow: hidden;
}

.main-content.expanded {
    margin-left: 0;
}

.toggle-btn.collapsed {
    left: 15px;
}
</style>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <h3><img src="http://localhost/mgt%20repo/img/neust_logo.png" alt="NEUST Logo" height="40"> Admin Panel</h3>
    <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
    <a href="view_uploads.php"><i class="fas fa-folder-open me-2"></i> Research Uploads</a>
    <a href="manage_verification.php"><i class="fas fa-user-check me-2"></i> Student Verification</a>
    <a href="manage_faculty_verification.php"><i class="fas fa-chalkboard-teacher me-2"></i> Faculty Verification</a>
    <a href="manage_request.php"><i class="fas fa-file-alt me-2"></i> Manuscript Requests</a>
    <a href="../views/upload_research.php"><i class="fas fa-upload me-2"></i> Upload Research</a>
    <a href="../views/reports.php"><i class="fas fa-chart-bar me-2"></i> Reports</a>
    <a href="notifications.php"><i class="fas fa-bell me-2"></i> Notifications</a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
</div>

<!-- Toggle Button -->
<button class="toggle-btn" id="toggle-btn"><i class="fas fa-bars"></i></button>

<!-- Main content -->
<div class="main-content" id="main-content">

    <!-- Admin Quick Actions -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Quick Management</h3>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-4 mb-3">
                    <a href="manage_verification.php" class="btn btn-primary w-100 py-3">
                        <i class="fas fa-user-graduate me-2"></i> Manage Student Verifications
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="manage_faculty_verification.php" class="btn btn-primary w-100 py-3">
                        <i class="fas fa-chalkboard-teacher me-2"></i> Manage Faculty Verifications
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="manage_request.php" class="btn btn-primary w-100 py-3">
                        <i class="fas fa-file-alt me-2"></i> Manage Manuscript Requests
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Section -->
    <div class="row">
        <?php
        $stats = [
            ["Total Research Papers", $total_research, "view_uploads.php"],
            ["Total Users", $total_users, "../views/reports.php?report=users"],
            ["Faculty Members", $total_faculty, "../views/reports.php?report=faculty"],
            ["Students", $total_students, "../views/reports.php?report=students"]
        ];
        foreach ($stats as $stat): ?>
            <div class="col-md-3 mb-4">
                <div class="card text-white shadow-sm" style="background-color:#003366;">
                    <div class="card-body text-center">
                        <h6 class="card-title"><?= $stat[0]; ?></h6>
                        <h2 class="fw-bold"><?= $stat[1]; ?></h2>
                        <a href="<?= $stat[2]; ?>" class="stretched-link"></a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Dashboard Sections -->
    <div class="row">
        <!-- Research Uploads -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5>Research Uploads</h5>
                    <a href="view_uploads.php" class="btn btn-sm btn-light"><i class="fas fa-eye"></i> View All Uploads</a>
                </div>
                <div class="card-body text-center">
                    <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                    <h5>Manage Research Uploads</h5>
                    <p class="text-muted">View and manage all research papers uploaded to the system.</p>
                    <a href="view_uploads.php" class="btn btn-primary w-100">View All Uploads</a>
                </div>
            </div>
        </div>

        <!-- Verification Management -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5>Verification Management</h5>
                    <a href="manage_verification.php" class="btn btn-sm btn-light"><i class="fas fa-eye"></i> View Students</a>
                </div>
                <div class="card-body text-center">
                    <i class="fas fa-user-check fa-3x text-primary mb-3"></i>
                    <h5>Manage Student and Faculty Verifications</h5>
                    <p class="text-muted">Verify or reject user accounts in the system.</p>
                    <a href="manage_verification.php" class="btn btn-primary w-100 mb-2">View Students</a>
                    <a href="manage_faculty_verification.php" class="btn btn-secondary w-100">View Faculty</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Departments -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white"><h5>Top Departments</h5></div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php if ($top_departments->num_rows > 0): ?>
                            <?php while ($dept = $top_departments->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($dept['department']); ?>
                                    <span class="badge bg-primary rounded-pill"><?= $dept['count']; ?></span>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="list-group-item">No departments found.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header text-white" style="background-color:#003366;"><h5>Quick Actions</h5></div>
                <div class="card-body d-grid gap-2">
                    <a href="../views/upload_research.php" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Research</a>
                    <a href="../views/reports.php" class="btn btn-primary"><i class="fas fa-chart-bar"></i> Generate Reports</a>
                    <a href="view_uploads.php" class="btn btn-primary"><i class="fas fa-folder-open"></i> View All Uploads</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById("toggle-btn").addEventListener("click", function() {
    document.getElementById("sidebar").classList.toggle("collapsed");
    document.getElementById("main-content").classList.toggle("expanded");
    this.classList.toggle("collapsed");
});
</script>

<?php include '../includes/footer.php'; ?>
