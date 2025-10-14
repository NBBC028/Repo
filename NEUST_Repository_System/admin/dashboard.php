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

// Handle research delete
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

// Handle user verification
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

// Handle user rejection
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

// Recent uploads with uploader info
$recent_uploads = $conn->query("
    SELECT r.id, r.title, r.authors, r.department, r.year_published, r.year_section,
           r.tags, r.file_path, r.status, r.created_at,
           r.uploaded_by, u.full_name AS uploader_name
    FROM research r
    LEFT JOIN users u ON r.uploaded_by = u.id
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

// Pending users
$pending_users = $conn->query("
    SELECT id, full_name, role, year_section, email, 
           COALESCE(id_image, '') as id_image, 
           COALESCE(is_verified, 0) as is_verified, 
           created_at
    FROM users
    WHERE role IN ('faculty','student')
    ORDER BY created_at DESC
");
?>

<?php include '../includes/header.php'; ?>

<!-- Admin Actions -->
<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">Admin Actions</h5>
    </div>
    <div class="card-body d-flex justify-content-between">
        <a href="admin_verify.php" class="btn btn-primary"><i class="fas fa-user-check"></i> Manage Student Verification</a>
        <a href="manage_requests.php" class="btn btn-primary"><i class="fas fa-file-alt"></i> Manage Manuscript Requests</a>
        <a href="../views/upload_research.php" class="btn btn-primary"><i class="fas fa-upload"></i> Upload New Research</a>
    </div>
</div>

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

<!-- Research uploads and User Verification -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5>Research Uploads</h5>
                <a href="#" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#researchUploadsModal">
                    <i class="fas fa-eye"></i> View All Uploads
                </a>
            </div>
            <div class="card-body text-center">
                <div class="d-flex justify-content-center mb-3">
                    <div class="text-center">
                        <img src="../assets/images/document-icon.png" alt="Research Uploads" class="img-fluid" style="max-width: 100px; height: auto;" onerror="this.src='https://via.placeholder.com/100?text=Research'">
                    </div>
                </div>
                <h5>Manage Research Uploads</h5>
                <p class="text-muted">View, approve and modify research papers</p>
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#researchUploadsModal">View All Uploads</a>
            </div>
        </div>
    </div>

    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'faculty'): ?>
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5>Faculty Verification</h5>
                <a href="#" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#userVerificationModal">
                    <i class="fas fa-eye"></i> View All Users
                </a>
            </div>
            <div class="card-body text-center">
                <div class="d-flex justify-content-center mb-3">
                    <div class="text-center">
                        <img src="../assets/images/user-icon.png" alt="User Verification" class="img-fluid" style="max-width: 100px; height: auto;" onerror="this.src='https://via.placeholder.com/100?text=Users'">
                    </div>
                </div>
                <h5>Manage Faculty Verifications</h5>
                <p class="text-muted">Verify or reject faculty accounts</p>
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userVerificationModal">View All Faculty</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
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
    </div>

    <div class="col-md-6 mb-4">
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

<!-- Research Uploads Modal -->
<div class="modal fade" id="researchUploadsModal" tabindex="-1" aria-labelledby="researchUploadsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="researchUploadsModalLabel">Research Uploads</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
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
                                <?php while ($research = $recent_uploads->fetch_assoc()): 
                                    $file_path = "../" . htmlspecialchars($research['file_path']);
                                    $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($research['title']); ?></td>
                                        <td><?= htmlspecialchars($research['authors']); ?></td>
                                        <td><?= htmlspecialchars($research['department']); ?></td>
                                        <td><?= htmlspecialchars($research['year_published']); ?></td>
                                        <td><?= $research['year_section'] ?: '-'; ?></td>
                                        <td>
                                            <?= !empty($research['tags']) 
                                                ? "<span class='badge bg-info text-dark'>" . htmlspecialchars($research['tags']) . "</span>" 
                                                : "<span class='text-muted'>No Tags</span>"; ?>
                                        </td>
                                        <td>
                                            <?= !empty($research['uploader_name'])
                                                ? "<a href='../views/user_profile.php?id={$research['uploaded_by']}' class='fw-bold text-decoration-none text-primary'>" . htmlspecialchars($research['uploader_name']) . "</a>"
                                                : "<span class='text-muted'>Unknown</span>"; ?>
                                        </td>
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
                                                <!-- ✅ Fullscreen View Button -->
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewResearch<?= $research['id']; ?>">
                                                    <i class="fas fa-eye"></i> View
                                                </button>

                                                <a href="../views/edit_research.php?id=<?= $research['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?= $research['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this research?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <?php if ($research['status'] === 'waiting'): ?>
                                                    <a href="?approve=<?= $research['id']; ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Fullscreen Modal -->
                                    <div class="modal fade" id="viewResearch<?= $research['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-fullscreen">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title"><?= htmlspecialchars($research['title']); ?></h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body bg-dark">
                                                    <?php if ($file_ext === 'pdf'): ?>
                                                        <iframe src="<?= $file_path; ?>" width="100%" height="100%" style="border:none;"></iframe>
                                                    <?php elseif (in_array($file_ext, ['doc', 'docx'])): ?>
                                                        <iframe src="https://view.officeapps.live.com/op/embed.aspx?src=<?= urlencode('http://localhost/NEUST_Repository_System/' . $file_path); ?>" width="100%" height="100%" style="border:none;"></iframe>
                                                    <?php else: ?>
                                                        <div class="text-center text-white">
                                                            <p>Preview not available. <a href="<?= $file_path; ?>" target="_blank" class="btn btn-light btn-sm">Download File</a></p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="10" class="text-center">No research found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- User Verification Modal -->
<div class="modal fade" id="userVerificationModal" tabindex="-1" aria-labelledby="userVerificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="userVerificationModalLabel">Faculty Verification</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Year & Section</th>
                                <th>Registration Date</th>
                                <th>ID Card</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Modify query to only show faculty users
                            $faculty_query = "SELECT * FROM users WHERE is_verified = 0 AND role = 'faculty' ORDER BY created_at DESC";
                            $unverified_faculty = $conn->query($faculty_query);
                            
                            if ($unverified_faculty && $unverified_faculty->num_rows > 0): 
                            ?>
                                <?php while ($user = $unverified_faculty->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['full_name']); ?></td>
                                        <td><?= htmlspecialchars($user['email']); ?></td>
                                        <td><?= htmlspecialchars(ucfirst($user['role'])); ?></td>
                                        <td><?= htmlspecialchars($user['department'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($user['year_section'] ?? '-'); ?></td>
                                        <td><?= date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <?php if (!empty($user['id_image'])): 
                                                $id_img = ltrim($user['id_image'], "./\\");
                                                $id_img_url = $base_url . ltrim($id_img, '/');
                                            ?>
                                                <button class="btn btn-sm btn-primary" onclick="showIdModal('<?php echo htmlspecialchars($id_img_url); ?>')">
                                                    <i class="fas fa-eye"></i> View ID
                                                </button>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">No ID Uploaded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="?verify_user=<?= $user['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Verify this faculty member?');">
                                                    <i class="fas fa-check"></i> Verify
                                                </a>
                                                <a href="?reject_user=<?= $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject and remove this faculty member?');">
                                                    <i class="fas fa-times"></i> Reject
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No faculty accounts pending verification.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
<!-- User Verification and Recent Activity -->
<div class="row mt-3">
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm">
            <div class="card-header text-white" style="background-color:#003366;">
                <h5 class="mb-0">User Verification (Faculty & Students)</h5>
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
                            <th style="white-space:nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pending_users && $pending_users->num_rows > 0): ?>
                            <?php while ($user = $pending_users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo $user['year_section'] ? htmlspecialchars($user['year_section']) : '-'; ?></td>
                                    <td>
                                        <?php if (!empty($user['id_image'])): 
                                            $id_img = ltrim($user['id_image'], "./\\");
                                            $id_img_url = $base_url . ltrim($id_img, '/');
                                        ?>
                                            <button class="btn btn-sm btn-primary" onclick="showIdModal('<?php echo htmlspecialchars($id_img_url); ?>')">
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
                                            <a href="?verify_user=<?php echo (int)$user['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Verify this user?');">
                                                <i class="fas fa-check"></i> Verify
                                            </a>
                                            <a href="?reject_user=<?php echo (int)$user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject and delete this user?');">
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
    
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header text-white" style="background-color:#003366;">
                <h5 class="mb-0">Admin Resources</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="admin_verify.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-user-check me-2"></i> User Verification
                        </div>
                        <span class="badge bg-primary rounded-pill">
                            <?php 
                                $unverified = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_verified = 0")->fetch_assoc()['count'];
                                echo $unverified;
                            ?>
                        </span>
                    </a>
                    <a href="manage_requests.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-file-alt me-2"></i> Manuscript Requests
                        </div>
                        <span class="badge bg-primary rounded-pill">
                            <?php 
                                $pending_requests = $conn->query("SELECT COUNT(*) as count FROM manuscript_requests WHERE status = 'pending'")->fetch_assoc()['count'];
                                echo $pending_requests;
                            ?>
                        </span>
                    </a>
                    <a href="../views/reports.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar me-2"></i> System Reports
                    </a>
                    <a href="../views/settings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i> System Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ID Modal -->
<div class="modal fade" id="idModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">ID Preview</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" id="idModalImg" class="img-fluid rounded shadow" alt="ID Image">
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