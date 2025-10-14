<?php
/**
 * Helper Functions
 * Common utility functions used throughout the application
 */

// Clean input data to prevent XSS
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Display alert messages
function display_alert($message, $type = 'info') {
    return '<div class="alert alert-' . $type . '">' . $message . '</div>';
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check user role
function check_role($allowed_roles) {
    if (!is_logged_in()) {
        return false;
    }
    
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    
    return in_array($_SESSION['role'], $allowed_roles);
}

// Redirect to appropriate dashboard based on role
function redirect_by_role($role) {
    switch ($role) {
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'faculty':
            header("Location: faculty/dashboard.php");
            break;
        case 'student':
            header("Location: student/dashboard.php");
            break;
        case 'guest':
            header("Location: guest/dashboard.php");
            break;
        default:
            header("Location: index.php");
    }
    exit;
}

// Generate pagination links
function generate_pagination($total_records, $records_per_page, $current_page, $url) {
    $total_pages = ceil($total_records / $records_per_page);
    
    if ($total_pages <= 1) {
        return '';
    }
    
    $pagination = '<ul class="pagination">';
    
    // Previous button
    if ($current_page > 1) {
        $pagination .= '<li><a href="' . $url . '?page=' . ($current_page - 1) . '">&laquo; Previous</a></li>';
    } else {
        $pagination .= '<li class="disabled"><span>&laquo; Previous</span></li>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == $current_page) {
            $pagination .= '<li class="active"><span>' . $i . '</span></li>';
        } else {
            $pagination .= '<li><a href="' . $url . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $pagination .= '<li><a href="' . $url . '?page=' . ($current_page + 1) . '">Next &raquo;</a></li>';
    } else {
        $pagination .= '<li class="disabled"><span>Next &raquo;</span></li>';
    }
    
    $pagination .= '</ul>';
    
    return $pagination;
}

/**
 * Upload file with validation (PDF, DOC, DOCX allowed)
 */
function upload_research_file($file) {
    $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/NEUST_Repository_System/uploads/";
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    // Allowed file types
    $allowed_extensions = ['pdf', 'doc', 'docx'];

    if (!in_array($file_extension, $allowed_extensions)) {
        return [
            'success' => false,
            'message' => 'Only PDF, DOC, and DOCX files are allowed.'
        ];
    }

    // Check file size (limit to 20MB)
    if ($file["size"] > 20000000) {
        return [
            'success' => false,
            'message' => 'File is too large. Maximum size is 20MB.'
        ];
    }

    // Create uploads directory if not exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return [
            'success' => true,
            'file_path' => 'uploads/' . $new_filename,
            'file_type' => $file_extension
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to upload file.'
        ];
    }
}

/**
 * Generate research preview (PDF or Word)
 */
function generate_research_preview($file_path) {
    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $full_url = "http://" . $_SERVER['HTTP_HOST'] . "/" . $file_path;

    if ($extension === 'pdf') {
        // Direct preview in iframe
        return '<iframe src="' . $full_url . '" width="100%" height="600px"></iframe>';
    } elseif (in_array($extension, ['doc', 'docx'])) {
        // Use Google Docs Viewer for Word files
        return '<iframe src="https://docs.google.com/gview?url=' . urlencode($full_url) . '&embedded=true" width="100%" height="600px"></iframe>';
    } else {
        return '<p>Preview not available. Please download the file to view.</p>';
    }
}

// ---------------------------
// Search research by keywords
// ---------------------------
function search_research($conn, $keywords = '', $department = '', $year = '') {
    $query = "SELECT * FROM research WHERE 1=1";

    if (!empty($keywords)) {
        $keywords = $conn->real_escape_string($keywords);
        $query .= " AND (title LIKE '%$keywords%' OR abstract LIKE '%$keywords%' OR keywords LIKE '%$keywords%')";
    }

    if (!empty($department)) {
        $department = $conn->real_escape_string($department);
        $query .= " AND department = '$department'";
    }

    if (!empty($year)) {
        $year = $conn->real_escape_string($year);
        $query .= " AND year_published = '$year'";
    }

    if (!check_role(['admin', 'faculty'])) {
        $query .= " AND status = 'public'";
    }

    $query .= " ORDER BY uploaded_on DESC";

    return $conn->query($query);
}
?>
