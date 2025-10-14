<?php
require_once '../includes/session.php';
require_once '../includes/db.php';

// Only allow access to admin users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Handle approval or rejection actions
if (isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'approve' || $action === 'reject') {
        $status = $action === 'approve' ? 'approved' : 'rejected';

        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin_verify.php");
    exit;
}

// Fetch pending users
$query = "
    SELECT 
        id, full_name, username, email, role, department, year_section, verification_id, status, created_at
    FROM users 
    WHERE status = 'pending' 
    ORDER BY created_at DESC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Users - NEUST Repository System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fa-solid fa-user-check me-2"></i> User Verification (Pending Accounts)</h4>
                <a href="dashboard.php" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
            </div>

            <div class="card-body">
                <?php if ($result && $result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Department</th>
                                    <th>Year & Section</th>
                                    <th>ID Proof</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= ucfirst($row['role']) ?></td>
                                    <td><?= htmlspecialchars($row['department']) ?></td>
                                    <td><?= htmlspecialchars($row['year_section'] ?: '-') ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($row['verification_id'])): ?>
                                            <a href="../<?= htmlspecialchars($row['verification_id']) ?>" target="_blank">
                                                <img src="../<?= htmlspecialchars($row['verification_id']) ?>" alt="ID" width="80" class="img-thumbnail rounded">
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No ID</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark"><?= ucfirst($row['status']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <a href="admin_verify.php?action=approve&id=<?= $row['id'] ?>" class="btn btn-success btn-sm">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </a>
                                        <a href="admin_verify.php?action=reject&id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center mb-0">
                        <i class="fa-solid fa-circle-info"></i> No pending users at the moment.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
