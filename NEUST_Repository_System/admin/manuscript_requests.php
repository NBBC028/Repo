<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Restrict access to admin only
restrict_access(['admin']);

// Handle request approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'], $_POST['request_id'])) {
        $request_id = intval($_POST['request_id']);
        $action = $_POST['action'];
        $admin_notes = isset($_POST['admin_notes']) ? $conn->real_escape_string($_POST['admin_notes']) : '';
        
        if ($action === 'approve' || $action === 'reject') {
            $status = ($action === 'approve') ? 'approved' : 'rejected';
            
            $update = $conn->prepare("UPDATE manuscript_requests SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
            $update->bind_param('ssi', $status, $admin_notes, $request_id);
            
            if ($update->execute()) {
                set_message('success', 'Request has been ' . $status . ' successfully.');
            } else {
                set_message('error', 'Failed to update request status.');
            }
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get all manuscript requests with research details
$requests = $conn->query("
    SELECT mr.*, r.title as research_title, r.authors, r.department, r.year_published, r.file_path
    FROM manuscript_requests mr
    JOIN research r ON mr.research_id = r.id
    ORDER BY 
        CASE 
            WHEN mr.status = 'pending' THEN 1
            WHEN mr.status = 'approved' THEN 2
            WHEN mr.status = 'rejected' THEN 3
        END,
        mr.created_at DESC
");
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Manuscript Requests</h3>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Manage Student Manuscript Requests</h5>
        </div>
        <div class="card-body">
            <?php if ($requests && $requests->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Research Title</th>
                                <th>Student Name</th>
                                <th>Student ID</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Purpose</th>
                                <th>Date Requested</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($request = $requests->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $request['id']; ?></td>
                                    <td><?php echo htmlspecialchars($request['research_title']); ?></td>
                                    <td><?php echo htmlspecialchars($request['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['student_number']); ?></td>
                                    <td><?php echo htmlspecialchars($request['student_email']); ?></td>
                                    <td><?php echo htmlspecialchars($request['department']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($request['purpose']); ?>
                                        <?php if ($request['purpose'] === 'Other' && !empty($request['other_purpose'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($request['other_purpose']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($request['created_at'])); ?></td>
                                    <td>
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php elseif ($request['status'] === 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php elseif ($request['status'] === 'rejected'): ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $request['id']; ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $request['id']; ?>">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $request['id']; ?>">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <!-- View Modal -->
                                <div class="modal fade" id="viewModal<?php echo $request['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">Request Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6>Research Information</h6>
                                                        <p><strong>Title:</strong> <?php echo htmlspecialchars($request['research_title']); ?></p>
                                                        <p><strong>Authors:</strong> <?php echo htmlspecialchars($request['authors']); ?></p>
                                                        <p><strong>Department:</strong> <?php echo htmlspecialchars($request['department']); ?></p>
                                                        <p><strong>Year Published:</strong> <?php echo htmlspecialchars($request['year_published']); ?></p>
                                                        
                                                        <hr>
                                                        
                                                        <h6>Student Information</h6>
                                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($request['student_name']); ?></p>
                                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($request['student_email']); ?></p>
                                                        <p><strong>Student ID:</strong> <?php echo htmlspecialchars($request['student_number']); ?></p>
                                                        
                                                        <hr>
                                                        
                                                        <h6>Request Information</h6>
                                                        <p><strong>Purpose:</strong> <?php echo htmlspecialchars($request['purpose']); ?></p>
                                                        <?php if ($request['purpose'] === 'Other' && !empty($request['other_purpose'])): ?>
                                                            <p><strong>Other Purpose:</strong> <?php echo htmlspecialchars($request['other_purpose']); ?></p>
                                                        <?php endif; ?>
                                                        <p><strong>Date Requested:</strong> <?php echo date('M d, Y g:i A', strtotime($request['created_at'])); ?></p>
                                                        <p><strong>Status:</strong> 
                                                            <?php if ($request['status'] === 'pending'): ?>
                                                                <span class="badge bg-warning">Pending</span>
                                                            <?php elseif ($request['status'] === 'approved'): ?>
                                                                <span class="badge bg-success">Approved</span>
                                                            <?php elseif ($request['status'] === 'rejected'): ?>
                                                                <span class="badge bg-danger">Rejected</span>
                                                            <?php endif; ?>
                                                        </p>
                                                        
                                                        <?php if (!empty($request['admin_notes'])): ?>
                                                            <p><strong>Admin Notes:</strong> <?php echo htmlspecialchars($request['admin_notes']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6>Student ID Image</h6>
                                                        <div class="text-center">
                                                            <img src="<?php echo isset($request['id_card_path']) ? $request['id_card_path'] : '../assets/img/no-image.png'; ?>" class="img-fluid border" style="max-height: 300px;">
                                                        </div>
                                                        
                                                        <?php if ($request['status'] === 'approved'): ?>
                                                            <div class="alert alert-info mt-3">
                                                                <p><strong>Access Link:</strong></p>
                                                                <div class="input-group">
                                                                    <input type="text" class="form-control" value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/guest/view_manuscript.php?request_id=' . $request['id'] . '&verification=' . md5($request['student_number']); ?>" id="access_link<?php echo $request['id']; ?>" readonly>
                                                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('access_link<?php echo $request['id']; ?>')">
                                                                        <i class="fas fa-copy"></i>
                                                                    </button>
                                                                </div>
                                                                <small class="text-muted">This link can be shared with the student to access the manuscript.</small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Approve Modal -->
                                <div class="modal fade" id="approveModal<?php echo $request['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title">Approve Request</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                                <div class="modal-body">
                                                    <p>Are you sure you want to approve this manuscript request from <strong><?php echo htmlspecialchars($request['student_name']); ?></strong>?</p>
                                                    <p>This will grant the student access to view the full manuscript.</p>
                                                    
                                                    <div class="mb-3">
                                                        <label for="admin_notes" class="form-label">Admin Notes (Optional)</label>
                                                        <textarea name="admin_notes" id="admin_notes" class="form-control" rows="3"></textarea>
                                                    </div>
                                                    
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Approve Request</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Reject Modal -->
                                <div class="modal fade" id="rejectModal<?php echo $request['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Reject Request</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                                <div class="modal-body">
                                                    <p>Are you sure you want to reject this manuscript request from <strong><?php echo htmlspecialchars($request['student_name']); ?></strong>?</p>
                                                    
                                                    <div class="mb-3">
                                                        <label for="admin_notes" class="form-label">Reason for Rejection (Required)</label>
                                                        <textarea name="admin_notes" id="admin_notes" class="form-control" rows="3" required></textarea>
                                                    </div>
                                                    
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Reject Request</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No manuscript requests found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>