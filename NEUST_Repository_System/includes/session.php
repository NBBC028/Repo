<?php
/**
 * Session Management
 * Handles user sessions and authentication
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db.php';

// Include helper functions
require_once 'functions.php';

// Check if user is logged in and session is valid
function validate_session() {
    if (isset($_SESSION['user_id'])) {
        // Session timeout after 30 minutes of inactivity
        $max_lifetime = 30 * 60; // 30 minutes in seconds
        
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $max_lifetime)) {
            // Session has expired
            session_unset();
            session_destroy();
            header("Location: /NEUST_REPOSITORY_SYSTEM/login.php?msg=expired");
            exit;
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    return false;
}

// Restrict access to page based on user role
function restrict_access($allowed_roles = []) {
    if (!validate_session()) {
        header("Location: /NEUST_REPOSITORY_SYSTEM/login.php");
        exit;
    }
    
    if (!empty($allowed_roles) && !check_role($allowed_roles)) {
        header("Location: /NEUST_REPOSITORY_SYSTEM/views/unauthorized.php");
        exit;
    }
}
?>