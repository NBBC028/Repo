<?php
/**
 * Helper Functions
 * Common utility functions used throughout the application
 */

// Clean input data to prevent XSS
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

// Display alert messages
if (!function_exists('display_alert')) {
    function display_alert($message, $type = 'info') {
        return '<div class="alert alert-' . $type . '">' . $message . '</div>';
    }
}

// Check if user is logged in
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }
}

// Check user role
if (!function_exists('check_role')) {
    function check_role($allowed_roles) {
        if (!is_logged_in()) return false;
        if (!is_array($allowed_roles)) $allowed_roles = [$allowed_roles];
        return in_array($_SESSION['role'], $allowed_roles);
    }
}

// Check if user is admin
if (!function_exists('is_admin')) {
    function is_admin() {
        return is_logged_in() && $_SESSION['role'] === 'admin';
    }
}

// Redirect to appropriate dashboard based on role
if (!function_exists('redirect_by_role')) {
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
}

// Generate pagination links
if (!function_exists('generate_pagination')) {
    function generate_pagination($total_records, $records_per_page, $current_page, $url) {
        $total_pages = ceil($total_records / $records_per_page);
        if ($total_pages <= 1) return '';

        $pagination = '<ul class="pagination">';
        // Previous
        $pagination .= $current_page > 1 ? 
            '<li><a href="' . $url . '?page=' . ($current_page - 1) . '">&laquo; Previous</a></li>' : 
            '<li class="disabled"><span>&laquo; Previous</span></li>';

        // Page numbers
        for ($i = 1; $i <= $total_pages; $i++) {
            $pagination .= $i == $current_page ? 
                '<li class="active"><span>' . $i . '</span></li>' : 
                '<li><a href="' . $url . '?page=' . $i . '">' . $i . '</a></li>';
        }

        // Next
        $pagination .= $current_page < $total_pages ? 
            '<li><a href="' . $url . '?page=' . ($current_page + 1) . '">Next &raquo;</a></li>' : 
            '<li class="disabled"><span>Next &raquo;</span></li>';

        $pagination .= '</ul>';
        return $pagination;
    }
}

// Upload research file
if (!function_exists('upload_research_file')) {
    function upload_research_file($file, $file_type = 'manuscript') {
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/NEUST_Repository_System/uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

        $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        $allowed_extensions = ['pdf'];
        if (!in_array($file_extension, $allowed_extensions)) {
            return ['success' => false, 'message' => 'Only PDF files are allowed.'];
        }
        if ($file["size"] > 20000000) {
            return ['success' => false, 'message' => 'File is too large. Maximum size is 20MB.'];
        }

        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return ['success' => true, 'file_path' => 'uploads/' . $new_filename, 'file_type' => $file_extension];
        } else {
            return ['success' => false, 'message' => 'Failed to upload file.'];
        }
    }
}

// Generate research preview
if (!function_exists('generate_research_preview')) {
    function generate_research_preview($file_path) {
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $full_url = "http://" . $_SERVER['HTTP_HOST'] . "/" . $file_path;

        if ($extension === 'pdf') {
            return '<iframe src="' . $full_url . '" width="100%" height="600px"></iframe>';
        } elseif (in_array($extension, ['doc', 'docx'])) {
            return '<iframe src="https://docs.google.com/gview?url=' . urlencode($full_url) . '&embedded=true" width="100%" height="600px"></iframe>';
        } else {
            return '<p>Preview not available. Please download the file to view.</p>';
        }
    }
}

// Search research
if (!function_exists('search_research')) {
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
}

// Notifications
if (!function_exists('create_notification')) {
    function create_notification($message, $type) {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO notifications (message, type) VALUES (?, ?)");
        $stmt->bind_param("ss", $message, $type);
        return $stmt->execute();
    }
}

if (!function_exists('get_unread_notifications')) {
    function get_unread_notifications($limit = 5) {
        global $conn;
        $result = $conn->query("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT $limit");
        $notifications = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }
        }
        return $notifications;
    }
}

if (!function_exists('mark_notification_read')) {
    function mark_notification_read($id) {
        global $conn;
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}

if (!function_exists('count_unread_notifications')) {
    function count_unread_notifications() {
        global $conn;
        $result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0");
        if ($result && $row = $result->fetch_assoc()) return $row['count'];
        return 0;
    }
}
?>
