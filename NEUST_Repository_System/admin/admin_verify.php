<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

// Only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle approve/reject actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE users SET status='approved' WHERE id=?");
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE users SET status='rejected' WHERE id=?");
    }

    if (isset($stmt)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    header("Location: admin_verify.php");
    exit;
}

// Fetch pending users
$result = $conn->query("SELECT id, full_name, username, email, role, department, year_section, verification_id, status 
                        FROM users WHERE status='pending' ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Users - NEUST Repository System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h2 class="mb-4">User Verification (Pending Accounts)</h2>

        <?php if ($result->num_rows > 0): ?>
            <table class="table table-bordered table-hover bg-white shadow">
                <thead class="table-primary">
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Year & Section</th>
                        <th>Verification ID</th>
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
                        <td><?= htmlspecialchars($row['year_section'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($row['verification_id'])): ?>
                                <a href="<?= $row['verification_id'] ?>" target="_blank">
                                    <img src="<?= $row['verification_id'] ?>" alt="ID" width="80" class="img-thumbnail">
                                </a>
                            <?php else: ?>
                                No ID
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-warning"><?= ucfirst($row['status']) ?></span></td>
                        <td>
                            <a href="admin_verify.php?action=approve&id=<?= $row['id'] ?>" class="btn btn-success btn-sm">Approve</a>
                            <a href="admin_verify.php?action=reject&id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Reject</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">No pending users at the moment.</div>
        <?php endif; ?>
    </div>
</body>
</html>
