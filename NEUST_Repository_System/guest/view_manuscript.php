<?php
// Initialize session
session_start();

// Include database connection
require_once '../includes/db_connection.php';

// Check if request parameters are provided
if (!isset($_GET['request_id']) || !isset($_GET['verification'])) {
    $_SESSION['error_messages'] = ["Invalid request parameters"];
    header("Location: dashboard.php");
    exit;
}

$request_id = intval($_GET['request_id']);
$verification_hash = $_GET['verification'];

// Get request details
$stmt = $conn->prepare("SELECT mr.*, r.title, r.file_path, r.file_type FROM manuscript_requests mr 
                        JOIN research r ON mr.research_id = r.id 
                        WHERE mr.id = ? AND mr.status = 'approved'");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_messages'] = ["Request not found or not approved"];
    header("Location: dashboard.php");
    exit;
}

$request = $result->fetch_assoc();
$stmt->close();

// Verify student ID hash
if ($verification_hash !== md5($request['student_id'])) {
    $_SESSION['error_messages'] = ["Invalid verification code"];
    header("Location: dashboard.php");
    exit;
}

// Get file extension
$file_extension = pathinfo($request['file_path'], PATHINFO_EXTENSION);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Manuscript - NEUST Repository System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            padding-top: 20px;
            background-color: #f8f9fa;
        }
        .manuscript-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .manuscript-viewer {
            height: 800px;
            width: 100%;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .watermark {
            position: fixed;
            bottom: 10px;
            right: 10px;
            opacity: 0.5;
            font-size: 14px;
            color: #6c757d;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($request['title']); ?></h2>
                    <a href="dashboard.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
                <hr>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="manuscript-container">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> This document is provided for educational purposes only. Unauthorized distribution is prohibited.
                    </div>
                    
                    <div class="manuscript-viewer">
                        <?php
                        // Display file based on type
                        if (strtolower($file_extension) === 'pdf') {
                            // PDF Viewer
                            echo '<embed src="' . $request['file_path'] . '" type="application/pdf" width="100%" height="100%">';
                        } elseif (in_array(strtolower($file_extension), ['doc', 'docx'])) {
                            // Word Document Viewer (using Google Docs Viewer)
                            $encoded_path = urlencode('https://' . $_SERVER['HTTP_HOST'] . '/' . $request['file_path']);
                            echo '<iframe src="https://docs.google.com/viewer?url=' . $encoded_path . '&embedded=true" width="100%" height="100%" frameborder="0"></iframe>';
                        } else {
                            // Other file types - provide download link
                            echo '<div class="alert alert-info text-center p-5">';
                            echo '<i class="fas fa-file fa-3x mb-3"></i>';
                            echo '<h4>This file type cannot be previewed</h4>';
                            echo '<p>Click the button below to download the file</p>';
                            echo '<a href="' . $request['file_path'] . '" class="btn btn-primary" download><i class="fas fa-download"></i> Download File</a>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    
                    <div class="mt-3">
                        <p><strong>Access granted to:</strong> <?php echo htmlspecialchars($request['student_name']); ?> (<?php echo htmlspecialchars($request['student_id']); ?>)</p>
                        <p><strong>Department:</strong> <?php echo htmlspecialchars($request['department']); ?></p>
                        <p><strong>Purpose:</strong> <?php echo htmlspecialchars($request['purpose']); ?></p>
                        <p><strong>Approved on:</strong> <?php echo date('F j, Y, g:i a', strtotime($request['updated_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="watermark">
        Accessed by <?php echo htmlspecialchars($request['student_id']); ?> on <?php echo date('Y-m-d H:i:s'); ?>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>