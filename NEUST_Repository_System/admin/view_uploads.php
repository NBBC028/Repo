<?php
session_start();

/* ✅ Locate and include the database connection file */
$possiblePaths = [
    __DIR__ . '/../config/db_connect.php',
    __DIR__ . '/config/db_connect.php',
    dirname(__DIR__) . '/config/db_connect.php',
    $_SERVER['DOCUMENT_ROOT'] . '/NEUST_Repository_System/config/db_connect.php'
];

$dbIncluded = false;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        include_once $path;
        $dbIncluded = true;
        break;
    }
}

if (!$dbIncluded) {
    die("<strong>Database connection file not found.</strong><br>Checked paths:<br>" . implode("<br>", $possiblePaths));
}

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? "Connection variable not found."));
}

/* ✅ Verify if admin is logged in */
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

/* ✅ Handle Approve/Reject actions */
if (isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if (in_array($action, ['approve', 'reject'])) {
        $status = ($action === 'approve') ? 'Approved' : 'Rejected';
        $update = $conn->prepare("UPDATE research SET status = ? WHERE id = ?");
        $update->bind_param("si", $status, $id);
        $update->execute();
        $update->close();
        header("Location: view_uploads.php");
        exit();
    }
}

/* ✅ Fetch all uploaded research papers (faculty uploads) */
$query = "
    SELECT 
        r.id,
        r.title,
        r.authors,
        r.department,
        r.year_published AS year,
        r.year_section,
        u.full_name AS uploaded_by,
        DATE_FORMAT(r.uploaded_on, '%b %d, %Y') AS uploaded_date,
        r.status,
        r.file_path
    FROM research r
    LEFT JOIN users u ON r.uploaded_by = u.id
    WHERE u.role = 'faculty'
    ORDER BY r.uploaded_on DESC
";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>All Research Uploads | Admin Dashboard</title>
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
<style>
/* Your existing CSS */
body { background-color: #f0f2f5; font-family: "Poppins", sans-serif; }
.container { max-width: 1150px; margin: 50px auto; }
.header-title { background-color: #003366; color: white; padding: 15px 25px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; }
.header-title h3 { margin: 0; font-weight: 600; }
.btn-back { background-color: #0d6efd; color: white; border: none; border-radius: 6px; padding: 8px 18px; font-size: 14px; text-decoration: none; }
.btn-back:hover { background-color: #004cbf; }
.card { background: #fff; border: none; border-radius: 0 0 8px 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
table { margin: 0; border: none; }
thead { background-color: #003366; color: white; }
thead th { font-weight: 500; text-align: center; padding: 12px; }
tbody td { text-align: center; vertical-align: middle; padding: 10px; }
tbody tr:hover { background-color: #f9f9f9; }
.badge-approve { background-color: #198754; color: #fff; padding: 6px 10px; border-radius: 5px; }
.badge-reject { background-color: #dc3545; color: #fff; padding: 6px 10px; border-radius: 5px; }
.badge-pending { background-color: #ffc107; color: #000; padding: 6px 10px; border-radius: 5px; }
.btn-view, .btn-approve, .btn-reject, .btn-delete { border: none; border-radius: 6px; padding: 5px 12px; font-size: 14px; text-decoration: none; color: #fff; margin:2px; }
.btn-view { background-color: #0d6efd; }
.btn-view:hover { background-color: #004cbf; }
.btn-approve { background-color: #198754; }
.btn-approve:hover { background-color: #146c43; }
.btn-reject { background-color: #dc3545; }
.btn-reject:hover { background-color: #b02a37; }
.btn-delete { background-color: #6c757d; }
.btn-delete:hover { background-color: #565e64; }
.no-records { text-align: center; color: #888; font-style: italic; padding: 20px; }
</style>
</head>
<body>

<div class="container">
    <div class="header-title">
        <h3>All Faculty Research Uploads</h3>
        <a href="dashboard.php" class="btn-back">← Back </a>
    </div>

    <div class="card p-4">
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Authors</th>
                        <th>Department</th>
                        <th>Year</th>
                        <th>Uploaded By</th>
                        <th>Status</th>
                        <th>Date Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['title']); ?></td>
                                <td><?= htmlspecialchars($row['authors']); ?></td>
                                <td><?= htmlspecialchars($row['department']); ?></td>
                                <td><?= htmlspecialchars($row['year']); ?></td>
                                <td><?= htmlspecialchars($row['uploaded_by']); ?></td>
                                <td>
                                    <?php
                                    $status = strtolower($row['status']);
                                    if ($status === 'approved') {
                                        echo '<span class="badge-approve">Approved</span>';
                                    } elseif ($status === 'rejected') {
                                        echo '<span class="badge-reject">Rejected</span>';
                                    } else {
                                        echo '<span class="badge-pending">Pending</span>';
                                    }
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($row['uploaded_date']); ?></td>
                                <td>
                                    <?php
                                    $filePath = "../uploads/" . basename($row['file_path']);
                                    if (!empty($row['file_path']) && file_exists($filePath)): ?>
                                        <a href="<?= htmlspecialchars($filePath); ?>" target="_blank" class="btn-view">View</a>
                                    <?php else: ?>
                                        <button class="btn-view" disabled>Missing File</button>
                                    <?php endif; ?>

                                    <!-- Approve/Reject Buttons -->
                                    <?php if ($status !== 'approved'): ?>
                                        <a href="?action=approve&id=<?= $row['id']; ?>" class="btn-approve" onclick="return confirm('Approve this research?');">Approve</a>
                                    <?php endif; ?>
                                    <?php if ($status !== 'rejected'): ?>
                                        <a href="?action=reject&id=<?= $row['id']; ?>" class="btn-reject" onclick="return confirm('Reject this research?');">Reject</a>
                                    <?php endif; ?>

                                    <a href="delete_research.php?id=<?= $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this research?');">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="no-records">No research uploads found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
