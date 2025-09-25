<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php'; // For sanitize_input and upload_research_file

// Restrict access to admin and faculty
restrict_access(['admin', 'faculty']);

// Check if editing existing research
$edit_mode = false;
$research = null;

if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_mode = true;
    $research_id = (int)$_GET['edit'];

    // Get research details
    $stmt = $conn->prepare("SELECT * FROM research WHERE id = ?");
    $stmt->bind_param("i", $research_id);
    $stmt->execute();
    $research = $stmt->get_result()->fetch_assoc();

    if (!$research || ($research['uploaded_by'] != $_SESSION['user_id'] && $_SESSION['role'] != 'admin')) {
        $_SESSION['message'] = "You don't have permission to edit this research.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../index.php");
        exit;
    }
}

// Handle delete request
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $research_id = (int)$_GET['delete'];

    $stmt = $conn->prepare("SELECT * FROM research WHERE id = ?");
    $stmt->bind_param("i", $research_id);
    $stmt->execute();
    $research = $stmt->get_result()->fetch_assoc();

    if (!$research || ($research['uploaded_by'] != $_SESSION['user_id'] && $_SESSION['role'] != 'admin')) {
        $_SESSION['message'] = "You don't have permission to delete this research.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../index.php");
        exit;
    }

    // Delete file
    $file_path = $_SERVER['DOCUMENT_ROOT'] . "/NEUST_REPOSITORY_SYSTEM/" . $research['file_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Delete from database
    $stmt = $conn->prepare("DELETE FROM research WHERE id = ?");
    $stmt->bind_param("i", $research_id);
    $stmt->execute();

    $_SESSION['message'] = "Research deleted successfully.";
    $_SESSION['message_type'] = "success";

    header("Location: " . ($_SESSION['role'] == 'admin' ? '../admin/dashboard.php' : '../faculty/dashboard.php'));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize_input($_POST['title']);
    $abstract = sanitize_input($_POST['abstract']);
    $authors = sanitize_input($_POST['authors']);
    $year_published = sanitize_input($_POST['year_published']);
    $department = sanitize_input($_POST['department']);
    $year_section = sanitize_input($_POST['year_section']); // New field
    $keywords = sanitize_input($_POST['keywords']);
    $status = $edit_mode ? sanitize_input($_POST['status']) : 'waiting';

    if (empty($title) || empty($abstract) || empty($authors) || empty($year_published) || empty($department) || empty($keywords)) {
        $_SESSION['message'] = "Please fill in all required fields.";
        $_SESSION['message_type'] = "danger";
    } else {
        if ($edit_mode) {
            $file_path = $research['file_path'];
            if (isset($_FILES['research_file']) && $_FILES['research_file']['size'] > 0) {
                $upload_result = upload_research_file($_FILES['research_file']);
                if ($upload_result['success']) {
                    $old_file_path = $_SERVER['DOCUMENT_ROOT'] . "/NEUST_REPOSITORY_SYSTEM/" . $research['file_path'];
                    if (file_exists($old_file_path)) unlink($old_file_path);
                    $file_path = $upload_result['file_path'];
                } else {
                    $_SESSION['message'] = $upload_result['message'];
                    $_SESSION['message_type'] = "danger";
                    $file_path = $research['file_path'];
                }
            }

            $stmt = $conn->prepare("UPDATE research SET title = ?, abstract = ?, authors = ?, year_published = ?, department = ?, year_section = ?, file_path = ?, status = ?, keywords = ? WHERE id = ?");
            $stmt->bind_param("sssssssssi", $title, $abstract, $authors, $year_published, $department, $year_section, $file_path, $status, $keywords, $research['id']);
            $stmt->execute();

            $_SESSION['message'] = "Research updated successfully.";
            $_SESSION['message_type'] = "success";
            header("Location: " . ($_SESSION['role'] == 'admin' ? '../admin/dashboard.php' : '../faculty/dashboard.php'));
            exit;

        } else {
            if (!isset($_FILES['research_file']) || $_FILES['research_file']['size'] == 0) {
                $_SESSION['message'] = "Please upload a PDF file.";
                $_SESSION['message_type'] = "danger";
            } else {
                $upload_result = upload_research_file($_FILES['research_file']);
                if ($upload_result['success']) {
                    $file_path = $upload_result['file_path'];
                    $user_id = $_SESSION['user_id'];

                    $stmt = $conn->prepare("INSERT INTO research (title, abstract, authors, year_published, department, year_section, file_path, uploaded_by, status, keywords) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssssssss", $title, $abstract, $authors, $year_published, $department, $year_section, $file_path, $user_id, $status, $keywords);
                    $stmt->execute();

                    $_SESSION['message'] = "Research uploaded successfully and is waiting for approval.";
                    $_SESSION['message_type'] = "success";
                    header("Location: ../faculty/dashboard.php");
                    exit;
                } else {
                    $_SESSION['message'] = $upload_result['message'];
                    $_SESSION['message_type'] = "danger";
                }
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><?php echo $edit_mode ? 'Edit Research' : 'Upload Research'; ?></h5>
        </div>
        <div class="card-body">
            <form action="upload_research.php<?php echo $edit_mode ? '?edit=' . $research['id'] : ''; ?>" method="post" enctype="multipart/form-data">
                
                <!-- Title & Authors -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required value="<?php echo $edit_mode ? htmlspecialchars($research['title']) : ''; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="authors" class="form-label">Authors <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="authors" name="authors" required value="<?php echo $edit_mode ? htmlspecialchars($research['authors']) : ''; ?>" placeholder="Separate multiple authors with commas">
                    </div>
                </div>

                <!-- Department, Year Published & Year & Section -->
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                        <select class="form-select" id="department" name="department" required>
                            <option value="">Select Department</option>
                            <option value="Computer Science" <?php echo $edit_mode && $research['department'] == 'Computer Science' ? 'selected' : ''; ?>>Bachelor of Science in Business Administration</option>
                            <option value="Information Technology" <?php echo $edit_mode && $research['department'] == 'Information Technology' ? 'selected' : ''; ?>>Bachelor of Science in Information Technology</option>
                            <option value="Business Administration" <?php echo $edit_mode && $research['department'] == 'Business Administration' ? 'selected' : ''; ?>>Bachelor of Elementary Education</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="year_published" class="form-label">Year Published <span class="text-danger">*</span></label>
                        <select class="form-select" id="year_published" name="year_published" required>
                            <option value="">Select Year</option>
                            <?php for ($i = date('Y'); $i >= 2000; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php echo $edit_mode && $research['year_published'] == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="year_section" class="form-label">Year & Section</label>
                        <input type="text" class="form-control" id="year_section" name="year_section" value="<?php echo $edit_mode ? htmlspecialchars($research['year_section']) : ''; ?>" placeholder="e.g., 3A">
                    </div>
                </div>

                <!-- Abstract -->
                <div class="mb-3">
                    <label for="abstract" class="form-label">Abstract <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="abstract" name="abstract" rows="5" required><?php echo $edit_mode ? htmlspecialchars($research['abstract']) : ''; ?></textarea>
                </div>

                <!-- Keywords -->
                <div class="mb-3">
                    <label for="keywords" class="form-label">Keywords <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="keywords" name="keywords" required value="<?php echo $edit_mode ? htmlspecialchars($research['keywords']) : ''; ?>" placeholder="Separate keywords with commas">
                </div>

                <!-- File Upload -->
                <div class="mb-3">
                    <label for="research_file" class="form-label">Research File (PDF) <?php echo $edit_mode ? '' : '<span class="text-danger">*</span>'; ?></label>
                    <input type="file" class="form-control" id="research_file" name="research_file" accept=".pdf" <?php echo $edit_mode ? '' : 'required'; ?>>
                    <?php if ($edit_mode): ?>
                        <div class="form-text">Current file: <a href="../<?php echo $research['file_path']; ?>" target="_blank"><?php echo basename($research['file_path']); ?></a></div>
                        <div class="form-text text-muted">Upload a new file only if you want to replace the current one.</div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?php echo $_SESSION['role'] == 'admin' ? '../admin/dashboard.php' : '../faculty/dashboard.php'; ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Update Research' : 'Upload Research'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
