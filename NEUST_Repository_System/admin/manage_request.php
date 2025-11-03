<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!is_admin()) {
    header("Location: ../unauthorized.php");
    exit;
}

// ✅ Handle request actions
if (isset($_GET['action'], $_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);

    if (in_array($action, ['approve', 'reject', 'delete'])) {

        // Fetch request details first (for notifications)
        $stmt = $conn->prepare("
            SELECT mr.*, r.title 
            FROM manuscript_requests mr
            JOIN research_projects r ON mr.research_id = r.id
            WHERE mr.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();

        if (!$request) {
            $_SESSION['message'] = "Request not found.";
            $_SESSION['message_type'] = "danger";
            header("Location: manage_requests.php");
            exit;
        }

        // Process actions
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE manuscript_requests SET status = 'approved', processed_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // Insert notification
            $message = "Approved manuscript request for: " . $request['title'] . " by " . $request['requester_name'];
            $stmt2 = $conn->prepare("INSERT INTO notifications (message, type, created_at) VALUES (?, 'request_approved', NOW())");
            $stmt2->bind_param("s", $message);
            $stmt2->execute();

            $_SESSION['message'] = "Manuscript request approved successfully.";
            $_SESSION['message_type'] = "success";

        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE manuscript_requests SET status = 'rejected', processed_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $message = "Rejected manuscript request for: " . $request['title'] . " by " . $request['requester_name'];
            $stmt2 = $conn->prepare("INSERT INTO notifications (message, type, created_at) VALUES (?, 'request_rejected', NOW())");
            $stmt2->bind_param("s", $message);
            $stmt2->execute();

            $_SESSION['message'] = "Manuscript request rejected.";
            $_SESSION['message_type'] = "warning";

        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM manuscript_requests WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $_SESSION['message'] = "Manuscript request deleted successfully.";
            $_SESSION['message_type'] = "success";
        }

        header("Location: manage_requests.php");
        exit;
    }
}

// ✅ Get filters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// ✅ Build query
$sql = "
    SELECT mr.*, r.title AS research_title 
    FROM manuscript_requests mr
    JOIN research_projects r ON mr.research_id = r.id
    WHERE 1=1
";

$params = [];
$types = "";

if ($status_filter !== 'all') {
    $sql .= " AND mr.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (mr.requester_name LIKE ? OR mr.requester_id LIKE ? OR r.title LIKE ?)";
    $search_term = "%$search%";
    array_push($params, $search_term, $search_term, $search_term);
    $types .= "sss";
}

$sql .= " ORDER BY mr.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-file-alt"></i> Manage Manuscript Requests</h5>
        </div>
        <div class="card-body">
            
            <!-- Filter and Search -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form action="manage_requests.php" method="GET" class="d-flex">
                        <select name="status" class="form-select me-2">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?= $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <form action="manage_requests.php" method="GET" class="d-flex">
                        <input type="hidden" name="status" value="<?= $status_filter; ?>">
                        <input type="text" name="search" class="form-control me-2" placeholder="Search by name, ID, or title" value="<?= htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
            </div>

            <!-- Requests Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>Research Title</th>
                            <th>Requester</th>
                            <th>Student ID</th>
                            <th>Section</th>
                            <th>Status</th>
                            <th>Requested</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($requests)): ?>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td><?= $req['id']; ?></td>
                                    <td><?= htmlspecialchars($req['research_title']); ?></td>
                                    <td><?= htmlspecialchars($req['requester_name']); ?></td>
                                    <td><?= htmlspecialchars($req['requester_id']); ?></td>
                                    <td><?= htmlspecialchars($req['requester_section']); ?></td>
                                    <td>
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php elseif ($req['status'] === 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y h:i A', strtotime($req['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?= $req['id']; ?>"><i class="fas fa-eye"></i></button>
                                        
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <a href="manage_requests.php?action=approve&id=<?= $req['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this request?');"><i class="fas fa-check"></i></a>
                                            <a href="manage_requests.php?action=reject&id=<?= $req['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this request?');"><i class="fas fa-times"></i></a>
                                        <?php endif; ?>

                                        <a href="manage_requests.php?action=delete&id=<?= $req['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this request permanently?');"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>

                                <!-- View Modal -->
                                <div class="modal fade" id="viewModal<?= $req['id']; ?>" tabindex="-1" aria-labelledby="viewModalLabel<?= $req['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">Request Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Research:</strong> <?= htmlspecialchars($req['research_title']); ?></p>
                                                <p><strong>Requester:</strong> <?= htmlspecialchars($req['requester_name']); ?></p>
                                                <p><strong>Student ID:</strong> <?= htmlspecialchars($req['requester_id']); ?></p>
                                                <p><strong>Section:</strong> <?= htmlspecialchars($req['requester_section']); ?></p>
                                                <p><strong>Reason:</strong><br><?= nl2br(htmlspecialchars($req['reason'])); ?></p>
                                                <p><strong>Status:</strong> 
                                                    <?php if ($req['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning">Pending</span>
                                                    <?php elseif ($req['status'] === 'approved'): ?>
                                                        <span class="badge bg-success">Approved</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Rejected</span>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <?php if ($req['status'] === 'pending'): ?>
                                                    <a href="manage_requests.php?action=approve&id=<?= $req['id']; ?>" class="btn btn-success">Approve</a>
                                                    <a href="manage_requests.php?action=reject&id=<?= $req['id']; ?>" class="btn btn-danger">Reject</a>
                                                <?php endif; ?>
                                                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center">No manuscript requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
