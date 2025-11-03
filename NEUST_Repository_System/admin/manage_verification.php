<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!is_admin()) {
    header("Location: ../unauthorized.php");
    exit;
}

// Handle verification actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    
    if ($action === 'approve') {
        // Approve verification
        $stmt = $conn->prepare("UPDATE student_verification SET status = 'approved', verified_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // Get student details for notification
            $stmt = $conn->prepare("SELECT student_name, student_id FROM student_verification WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            
            // Create notification
            $message = "Student verification approved: " . $student['student_name'] . " (ID: " . $student['student_id'] . ")";
            $stmt = $conn->prepare("INSERT INTO notifications (message, type, created_at) VALUES (?, 'verification_approved', NOW())");
            $stmt->bind_param("s", $message);
            $stmt->execute();
            
            $_SESSION['message'] = "Student verification approved successfully.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Failed to approve student verification.";
            $_SESSION['message_type'] = "danger";
        }
    } elseif ($action === 'reject') {
        // Reject verification
        $stmt = $conn->prepare("UPDATE student_verification SET status = 'rejected', verified_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // Get student details for notification
            $stmt = $conn->prepare("SELECT student_name, student_id FROM student_verification WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            
            // Create notification
            $message = "Student verification rejected: " . $student['student_name'] . " (ID: " . $student['student_id'] . ")";
            $stmt = $conn->prepare("INSERT INTO notifications (message, type, created_at) VALUES (?, 'verification_rejected', NOW())");
            $stmt->bind_param("s", $message);
            $stmt->execute();
            
            $_SESSION['message'] = "Student verification rejected.";
            $_SESSION['message_type'] = "warning";
        } else {
            $_SESSION['message'] = "Failed to reject student verification.";
            $_SESSION['message_type'] = "danger";
        }
    } elseif ($action === 'delete') {
        // Delete verification and associated files
        $stmt = $conn->prepare("SELECT id_image_path, face_image_path FROM student_verification WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $verification = $result->fetch_assoc();
        
        // Delete files if they exist
        if (!empty($verification['id_image_path']) && file_exists($verification['id_image_path'])) {
            unlink($verification['id_image_path']);
        }
        if (!empty($verification['face_image_path']) && file_exists($verification['face_image_path'])) {
            unlink($verification['face_image_path']);
        }
        
        // Delete record from database
        $stmt = $conn->prepare("DELETE FROM student_verification WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Student verification deleted successfully.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Failed to delete student verification.";
            $_SESSION['message_type'] = "danger";
        }
    }
    
    header("Location: manage_verifications.php");
    exit;
}

// Get verifications with filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT * FROM student_verification WHERE 1=1";
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $search_term = "%$search%";
    $sql .= " AND (student_name LIKE ? OR student_id LIKE ? OR section LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$verifications = $result->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Manage Student Verifications</h5>
                </div>
                <div class="card-body">
                    <!-- Filters and Search -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form action="manage_verifications.php" method="GET" class="d-flex">
                                <select name="status" class="form-select me-2">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form action="manage_verifications.php" method="GET" class="d-flex">
                                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                                <input type="text" name="search" class="form-control me-2" placeholder="Search by name, ID, or section" value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-primary">Search</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Verifications Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student Name</th>
                                    <th>Student ID</th>
                                    <th>Section</th>
                                    <th>Year Level</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($verifications) > 0): ?>
                                    <?php foreach ($verifications as $verification): ?>
                                        <tr>
                                            <td><?php echo $verification['id']; ?></td>
                                            <td><?php echo htmlspecialchars($verification['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($verification['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($verification['section']); ?></td>
                                            <td><?php echo htmlspecialchars($verification['year_level']); ?></td>
                                            <td>
                                                <?php if ($verification['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php elseif ($verification['status'] === 'approved'): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php elseif ($verification['status'] === 'rejected'): ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($verification['created_at'])); ?></td>
                                            <td>
                                                <!-- View Button - Opens Modal -->
                                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $verification['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($verification['status'] === 'pending'): ?>
                                                    <!-- Approve Button -->
                                                    <a href="manage_verifications.php?action=approve&id=<?php echo $verification['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to approve this verification?');">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    
                                                    <!-- Reject Button -->
                                                    <a href="manage_verifications.php?action=reject&id=<?php echo $verification['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to reject this verification?');">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <!-- Delete Button -->
                                                <a href="manage_verifications.php?action=delete&id=<?php echo $verification['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this verification? This action cannot be undone.');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        
                                        <!-- View Modal -->
                                        <div class="modal fade" id="viewModal<?php echo $verification['id']; ?>" tabindex="-1" aria-labelledby="viewModalLabel<?php echo $verification['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="viewModalLabel<?php echo $verification['id']; ?>">Student Verification Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6>Student Information</h6>
                                                                <p><strong>Name:</strong> <?php echo htmlspecialchars($verification['student_name']); ?></p>
                                                                <p><strong>ID:</strong> <?php echo htmlspecialchars($verification['student_id']); ?></p>
                                                                <p><strong>Section:</strong> <?php echo htmlspecialchars($verification['section']); ?></p>
                                                                <p><strong>Year Level:</strong> <?php echo htmlspecialchars($verification['year_level']); ?></p>
                                                                <p><strong>Status:</strong> 
                                                                    <?php if ($verification['status'] === 'pending'): ?>
                                                                        <span class="badge bg-warning">Pending</span>
                                                                    <?php elseif ($verification['status'] === 'approved'): ?>
                                                                        <span class="badge bg-success">Approved</span>
                                                                    <?php elseif ($verification['status'] === 'rejected'): ?>
                                                                        <span class="badge bg-danger">Rejected</span>
                                                                    <?php endif; ?>
                                                                </p>
                                                                <p><strong>Submitted:</strong> <?php echo date('M d, Y h:i A', strtotime($verification['created_at'])); ?></p>
                                                                <?php if (!empty($verification['verified_at'])): ?>
                                                                    <p><strong>Verified:</strong> <?php echo date('M d, Y h:i A', strtotime($verification['verified_at'])); ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>Uploaded ID</h6>
                                                                <?php if (!empty($verification['id_image_path']) && file_exists($verification['id_image_path'])): ?>
                                                                    <img src="<?php echo $verification['id_image_path']; ?>" class="img-fluid mb-3" alt="Student ID">
                                                                <?php else: ?>
                                                                    <p class="text-danger">ID image not found.</p>
                                                                <?php endif; ?>
                                                                
                                                                <h6>Face ID</h6>
                                                                <?php if (!empty($verification['face_image_path']) && file_exists($verification['face_image_path'])): ?>
                                                                    <img src="<?php echo $verification['face_image_path']; ?>" class="img-fluid" alt="Face ID">
                                                                <?php else: ?>
                                                                    <p class="text-danger">Face image not found.</p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <?php if ($verification['status'] === 'pending'): ?>
                                                            <a href="manage_verifications.php?action=approve&id=<?php echo $verification['id']; ?>" class="btn btn-success">Approve</a>
                                                            <a href="manage_verifications.php?action=reject&id=<?php echo $verification['id']; ?>" class="btn btn-danger">Reject</a>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No verification requests found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>