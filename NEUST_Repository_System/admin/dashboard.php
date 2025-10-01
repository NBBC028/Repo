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
    header("Location: dashboard.php"); exit;
}

// Handle research delete
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $research_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM research WHERE id = ?");
    $stmt->bind_param("i", $research_id);
    $stmt->execute();
    $_SESSION['message'] = "Research deleted successfully.";
    $_SESSION['message_type'] = "danger";
    header("Location: dashboard.php"); exit;
}

// Handle user verification
if (isset($_GET['verify_user']) && !empty($_GET['verify_user'])) {
    $user_id = (int)$_GET['verify_user'];
    $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $_SESSION['message'] = "User verified successfully.";
    $_SESSION['message_type'] = "success";
    header("Location: dashboard.php"); exit;
}

// Handle user rejection
if (isset($_GET['reject_user']) && !empty($_GET['reject_user'])) {
    $user_id = (int)$_GET['reject_user'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $_SESSION['message'] = "User rejected and removed.";
    $_SESSION['message_type'] = "danger";
    header("Location: dashboard.php"); exit;
}

// Statistics
$total_research  = $conn->query("SELECT COUNT(*) as count FROM research")->fetch_assoc()['count'];
$total_users     = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_faculty   = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'faculty'")->fetch_assoc()['count'];
$total_students  = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'")->fetch_assoc()['count'];

// Recent uploads (with tags)
$recent_uploads = $conn->query("
    SELECT r.*, u.full_name, u.year_section
    FROM research r 
    JOIN users u ON r.uploaded_by = u.id 
    ORDER BY r.created_at DESC 
    LIMIT 10
");

// Top departments
$top_departments = $conn->query("
    SELECT department, COUNT(*) as count 
    FROM research 
    GROUP BY department 
    ORDER BY count DESC 
    LIMIT 5
");

// Pending users (NO tags here)
$pending_users = $conn->query("
    SELECT id, full_name, role, year_section, email, id_image, is_verified, created_at
    FROM users
    WHERE role IN ('faculty','student')
    ORDER BY created_at DESC
");
?>

<?php include '../includes/header.php'; ?>

<!-- Statistics Cards -->
<div class="row">
    <?php
    $stats = [
        ["Total Research Papers", $total_research, "../views/search.php"],
        ["Total Users", $total_users, "../views/reports.php?report=users"],
        ["Faculty Members", $total_faculty, "../views/reports.php?report=faculty"],
        ["Students", $total_students, "../views/reports.php?report=students"]
    ];
    foreach ($stats as $stat): ?>
        <div class="col-md-3 mb-4">
            <div class="card text-white shadow-sm" style="background-color:#003366;">
                <div class="card-body text-center">
                    <h6 class="card-title"><?php echo $stat[0]; ?></h6>
                    <h2 class="fw-bold"><?php echo $stat[1]; ?></h2>
                    <a href="<?php echo $stat[2]; ?>" class="btn btn-light btn-sm mt-2">View</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Research uploads -->
<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5>Recent Research Uploads</h5>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author(s)</th>
                            <th>Department</th>
                            <th>Year</th>
                            <th>Year & Section</th>
                            <th>Tags</th>
                            <th>Uploaded By</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_uploads->num_rows > 0): ?>
                            <?php while ($research = $recent_uploads->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($research['title']); ?></td>
                                    <td><?= htmlspecialchars($research['authors']); ?></td>
                                    <td><?= htmlspecialchars($research['department']); ?></td>
                                    <td><?= htmlspecialchars($research['year_published']); ?></td>
                                    <td><?= $research['year_section'] ?: '-'; ?></td>
                                    <td>
                                        <?php if (!empty($research['tags'])): ?>
                                            <span class="badge bg-info text-dark"><?= htmlspecialchars($research['tags']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">No Tags</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($research['full_name']); ?></td>
                                    <td><?= date('M d, Y', strtotime($research['created_at'])); ?></td>
                                    <td>
                                        <?php if ($research['status'] === 'waiting'): ?>
                                            <span class="badge bg-warning text-dark">Waiting</span>
                                        <?php elseif ($research['status'] === 'public'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($research['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-nowrap gap-1">
                                            <a href="../<?= $research['file_path']; ?>" target="_blank" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></a>
                                            <a href="../views/edit_research.php?id=<?= $research['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                            <a href="?delete=<?= $research['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this research?');"><i class="fas fa-trash"></i></a>
                                            <?php if ($research['status'] === 'waiting'): ?>
                                                <a href="?approve=<?= $research['id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-check"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center">No research found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Departments & Quick Actions -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5>Top Departments</h5>
            </div>
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

        <!-- Quick Actions -->
        <div class="card shadow-sm">
            <div class="card-header text-white" style="background-color:#003366;">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="../views/upload_research.php" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Research</a>
                <a href="../views/reports.php" class="btn btn-primary"><i class="fas fa-chart-bar"></i> Generate Reports</a>
                <a href="../views/search.php" class="btn btn-primary"><i class="fas fa-search"></i> Advanced Search</a>
            </div>
        </div>
    </div>
</div>

<!-- User Verification -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header text-white" style="background-color:#003366;">
                <h5>User Verification (Faculty & Students)</h5>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Year & Section</th>
                            <th>ID Image</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pending_users->num_rows > 0): ?>
                            <?php while ($user = $pending_users->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['full_name']); ?></td>
                                    <td><?= ucfirst($user['role']); ?></td>
                                    <td><?= htmlspecialchars($user['email']); ?></td>
                                    <td><?= $user['year_section'] ?: '-'; ?></td>
                                    <td>
                                        <?php if (!empty($user['id_image'])): ?>
                                            <button class="btn btn-sm btn-primary" onclick="showIdModal('../<?= htmlspecialchars($user['id_image']); ?>')">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">No ID</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['is_verified']): ?>
                                            <span class="badge bg-success">Verified</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$user['is_verified']): ?>
                                            <a href="?verify_user=<?= $user['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Verify this user?');">
                                               <i class="fas fa-check"></i> Verify
                                            </a>
                                            <a href="?reject_user=<?= $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject and delete this user?');">
                                               <i class="fas fa-times"></i> Reject
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No Action</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ID Modal -->
<div class="modal fade" id="idModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-body text-center">
        <img src="" id="idModalImg" class="img-fluid rounded shadow">
      </div>
    </div>
  </div>
</div>

<script>
function showIdModal(imgPath) {
    document.getElementById("idModalImg").src = imgPath;
    var myModal = new bootstrap.Modal(document.getElementById('idModal'));
    myModal.show();
}
</script>

<?php include '../includes/footer.php'; ?>
