<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Restrict access to student only
restrict_access(['student']);

if (!isset($_GET['id']) || !isset($_GET['request'])) {
    set_message('error', 'Invalid request parameters.');
    header('Location: dashboard.php');
    exit;
}

$research_id = intval($_GET['id']);
$request_id = intval($_GET['request']);

// Verify the request belongs to the current student and is approved
$check_request = $conn->prepare("SELECT mr.*, r.title, r.file_path 
                                FROM manuscript_requests mr
                                JOIN research r ON mr.research_id = r.id
                                WHERE mr.id = ? AND mr.research_id = ? 
                                AND mr.student_id = ? AND mr.status = 'approved'");
$check_request->bind_param('iii', $request_id, $research_id, $_SESSION['user_id']);
$check_request->execute();
$request_result = $check_request->get_result();

if ($request_result->num_rows === 0) {
    set_message('error', 'You do not have permission to view this manuscript or the request has not been approved.');
    header('Location: dashboard.php');
    exit;
}

$request = $request_result->fetch_assoc();
$file_path = "../" . $request['file_path'];
$file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Approved Manuscript: <?php echo htmlspecialchars($request['title']); ?></h5>
        </div>
        <div class="card-body">
            <div class="alert alert-success mb-4">
                <i class="fas fa-check-circle"></i> Your request has been approved. You can now view the full manuscript.
                <p class="small mt-2 mb-0">
                    <strong>Note:</strong> This access is for educational purposes only. Do not distribute or share this document.
                </p>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <?php if ($file_ext === 'pdf'): ?>
                        <div class="embed-responsive" style="height: 700px;">
                            <embed src="<?php echo $file_path; ?>" type="application/pdf" width="100%" height="100%" />
                        </div>
                    <?php elseif ($file_ext === 'doc' || $file_ext === 'docx'): ?>
                        <iframe src="https://docs.google.com/gview?url=http://localhost/NEUST_Repository_System/<?php echo $request['file_path']; ?>&embedded=true" 
                                style="width:100%; height:700px;" frameborder="0"></iframe>
                    <?php else: ?>
                        <p class="text-danger">Preview not available. Please download the file to view.</p>
                        <a href="<?php echo $file_path; ?>" download class="btn btn-primary">
                            <i class="fas fa-download"></i> Download File
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>