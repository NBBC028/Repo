<?php
require_once '../includes/session.php';
require_once '../includes/db.php';

// Restrict to admin
restrict_access(['admin']);

// Validate ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../admin/dashboard.php");
    exit;
}

$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM research WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$research = $stmt->get_result()->fetch_assoc();

if (!$research) {
    echo "<div class='text-center mt-5'><h3>Research not found.</h3></div>";
    exit;
}

$file_path = "../" . htmlspecialchars($research['file_path']);
$file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($research['title']); ?> | NEUST Repository</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body style="background-color:#f5f7fa;">
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="text-primary fw-bold"><?= htmlspecialchars($research['title']); ?></h4>
            <a href="../admin/dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="card shadow-sm p-4">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>Author(s):</strong> <?= htmlspecialchars($research['authors']); ?></p>
                    <p><strong>Department:</strong> <?= htmlspecialchars($research['department']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Year Published:</strong> <?= htmlspecialchars($research['year_published']); ?></p>
                    <p><strong>Tags:</strong> <?= htmlspecialchars($research['tags']); ?></p>
                </div>
            </div>

            <hr>

            <div class="text-center mb-3">
                <h5><strong>Research File Preview</strong></h5>
            </div>

            <?php if ($file_ext === 'pdf'): ?>
                <iframe src="<?= $file_path; ?>" width="100%" height="700px" style="border:1px solid #ccc;"></iframe>
            <?php elseif (in_array($file_ext, ['doc', 'docx'])): ?>
                <iframe src="https://view.officeapps.live.com/op/embed.aspx?src=<?= urlencode('http://localhost/NEUST_REPOSITORY_SYSTEM/' . $file_path); ?>"
                        width="100%" height="700px" style="border:1px solid #ccc;"></iframe>
            <?php else: ?>
                <div class="text-center">
                    <p>Preview not available. <a href="<?= $file_path; ?>" target="_blank" class="btn btn-primary btn-sm">Download File</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
