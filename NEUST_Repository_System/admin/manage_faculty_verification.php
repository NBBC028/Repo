<?php
require_once '../includes/session.php';
require_once '../includes/db.php';

// Restrict access to admin only
restrict_access(['admin']);

// Verify faculty
if (isset($_GET['verify_user']) && !empty($_GET['verify_user'])) {
    $user_id = (int)$_GET['verify_user'];
    $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $_SESSION['message'] = "Faculty verified successfully.";
    $_SESSION['message_type'] = "success";
    header("Location: manage_faculty_verification.php");
    exit;
}

// Reject faculty
if (isset($_GET['reject_user']) && !empty($_GET['reject_user'])) {
    $user_id = (int)$_GET['reject_user'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $_SESSION['message'] = "Faculty rejected and removed.";
    $_SESSION['message_type'] = "danger";
    header("Location: manage_faculty_verification.php");
    exit;
}

// Fetch faculty data
$query = "
    SELECT 
        id,
        username AS fullname,
        email,
        department,
        id_image,      -- ✅ Added faculty ID image column
        is_verified
    FROM users
    WHERE role = 'faculty'
    ORDER BY created_at DESC
";
$faculty = $conn->query($query);
?>

<?php include '../includes/header.php'; ?>

<style>
body {
    background-color: #f8f9fa;
}
.card-header {
    background-color: #003366 !important;
    color: white !important;
}
.table thead {
    background-color: #003366;
    color: white;
}
.btn-verify {
    background-color: #28a745;
    color: white;
}
.btn-reject {
    background-color: #dc3545;
    color: white;
}
.btn-verify:hover,
.btn-reject:hover {
    opacity: 0.9;
}
.id-thumbnail {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
    cursor: pointer;
    transition: transform 0.2s;
}
.id-thumbnail:hover {
    transform: scale(1.1);
}
</style>

<div class="container-fluid mt-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-user-check me-2"></i> Manage Faculty Verifications</h4>
            <a href="dashboard.php" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="card-body">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Faculty Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>ID Preview</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($faculty && $faculty->num_rows > 0): ?>
                            <?php $i = 1; while ($row = $faculty->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $i++; ?></td>
                                    <td><?= htmlspecialchars($row['fullname']); ?></td>
                                    <td><?= htmlspecialchars($row['email']); ?></td>
                                    <td><?= htmlspecialchars($row['department']); ?></td>
                                    <td>
                                        <?php if (!empty($row['id_image'])): ?>
                                            <img src="../uploads/ids/<?= htmlspecialchars($row['id_image']); ?>" 
                                                 class="id-thumbnail"
                                                 alt="Faculty ID"
                                                 onclick="showIdModal('../uploads/ids/<?= htmlspecialchars($row['id_image']); ?>')">
                                        <?php else: ?>
                                            <span class="text-muted">No ID Uploaded</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['is_verified']): ?>
                                            <span class="badge bg-success">Verified</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$row['is_verified']): ?>
                                            <a href="?verify_user=<?= $row['id']; ?>" 
                                               class="btn btn-verify btn-sm">
                                               <i class="fas fa-check"></i> Verify
                                            </a>
                                            <a href="?reject_user=<?= $row['id']; ?>" 
                                               class="btn btn-reject btn-sm"
                                               onclick="return confirm('Are you sure you want to reject this faculty member?');">
                                               <i class="fas fa-times"></i> Reject
                                            </a>
                                        <?php else: ?>
                                            <span class="text-success">✔ Verified</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted">No faculty accounts found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ✅ ID Preview Modal -->
<div class="modal fade" id="idModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Faculty ID Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
    new bootstrap.Modal(document.getElementById('idModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
