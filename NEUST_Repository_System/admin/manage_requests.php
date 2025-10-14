<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Restrict access to admin only
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['message'] = "You don't have permission to access this page.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../index.php");
    exit;
}

// Handle request approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];

    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'approved' : 'rejected';

        // Update request status
        $stmt = $conn->prepare("UPDATE manuscript_requests SET status = ?, processed_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $request_id);
        $stmt->execute();

        // Get request details for notification
        $stmt = $conn->prepare("
            SELECT r.title, mr.requester_name, mr.requester_email, mr.research_id 
            FROM manuscript_requests mr 
            JOIN research r ON mr.research_id = r.id 
            WHERE mr.id = ?
        ");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();

        // Create notification for the requester
        if ($request) {
            $message = "Your request for the manuscript titled '" . $request['title'] . "' has been " . $status . ".";
            $type = 'request_' . $status;

            $notif_stmt = $conn->prepare("
                INSERT INTO notifications (user_id, message, type)
                SELECT id, ?, ? FROM users WHERE email = ?
            ");
            $notif_stmt->bind_param("sss", $message, $type, $request['requester_email']);
            $notif_stmt->execute();
        }

        $_SESSION['message'] = "Request has been " . ucfirst($status) . ".";
        $_SESSION['message_type'] = "success";
    }
}

// ✅ Main query to display all requests
$requests_query = "
    SELECT mr.*, r.title, r.authors, r.department, r.year_published
    FROM manuscript_requests mr
    JOIN research r ON mr.research_id = r.id
    ORDER BY 
        CASE 
            WHEN mr.status = 'pending' THEN 1
            WHEN mr.status = 'approved' THEN 2
            ELSE 3
        END,
        mr.requested_at DESC
";
$requests = $conn->query($requests_query);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Manage Manuscript Requests</h2>
        </div>
        <div class="col-auto">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Manuscript Requests</h5>
        </div>
        <div class="card-body">
            <?php if ($requests->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Research Title</th>
                                <th>Requester</th>
                                <th>Student ID</th>
                                <th>Purpose</th>
                                <th>Requested Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($request = $requests->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $request['id']; ?></td>
                                    <td>
                                        <a href="../views/view_research.php?id=<?php echo $request['research_id']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($request['title']); ?>
                                        </a>
                                        <div class="small text-muted">
                                            <?php echo htmlspecialchars($request['authors']); ?> | 
                                            <?php echo htmlspecialchars($request['department']); ?> | 
                                            <?php echo htmlspecialchars($request['year_published']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['requester_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['requester_id']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#purposeModal<?php echo $request['id']; ?>">
                                            View Purpose
                                        </button>

                                        <!-- Purpose Modal -->
                                        <div class="modal fade" id="purposeModal<?php echo $request['id']; ?>" tabindex="-1" aria-labelledby="purposeModalLabel<?php echo $request['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="purposeModalLabel<?php echo $request['id']; ?>">Request Purpose</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <?php echo nl2br(htmlspecialchars($request['purpose'])); ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($request['requested_at'])); ?></td>
                                    <td>
                                        <?php if ($request['status'] == 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php elseif ($request['status'] == 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                            <div class="small text-muted">
                                                <?php echo date('M d, Y g:i A', strtotime($request['processed_at'])); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Rejected</span>
                                            <div class="small text-muted">
                                                <?php echo date('M d, Y g:i A', strtotime($request['processed_at'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($request['status'] == 'pending'): ?>
                                            <div class="btn-group" role="group">
                                                <form method="post" class="me-1">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this request?')">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form method="post">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this request?')">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Processed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-0">
                    No manuscript requests found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
