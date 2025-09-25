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

// Upload file with validation
function upload_research_file($file) {
    $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/NEUST_REPOSITORY_SYSTEM/uploads/";
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check if file is a PDF
    if ($file_extension != "pdf") {
        return [
            'success' => false,
            'message' => 'Only PDF files are allowed.'
        ];
    }
    
    // Check file size (limit to 10MB)
    if ($file["size"] > 10000000) {
        return [
            'success' => false,
            'message' => 'File is too large. Maximum size is 10MB.'
        ];
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return [
            'success' => true,
            'file_path' => 'uploads/' . $new_filename
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to upload file.'
        ];
    }
}

// ---------------------------
// Search research by keywords
// ---------------------------
// FIX: required $conn first, optional filters later
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

    // If not admin or faculty, only show public research
    if (!check_role(['admin', 'faculty'])) {
        $query .= " AND status = 'public'";
    }

    // Use actual upload date column, e.g., uploaded_on
    $query .= " ORDER BY uploaded_on DESC";

    $result = $conn->query($query);

    return $result;
}
?>
