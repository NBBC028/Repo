<?php
require_once 'includes/session.php';

// Redirect based on user role
if (is_logged_in()) {
    redirect_by_role($_SESSION['role']);
} else {
    // If not logged in, redirect to login page
    header("Location: login.php");
    exit;
}
?>