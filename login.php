<?php
require_once 'includes/session.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect_by_role($_SESSION['role']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NEUST-MGT Repository Complete Research Project System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <img src="http://localhost/mgt%20repo/img/neust_logo.png" alt="NEUST Logo" height="100">
                        <h3>NEUST-MGT Repository Complete Research Project System</h3>
                        <p></p>
                    </div>
                    <div class="card-body">
                        <?php
                        // Display error message if any
                        if (isset($_GET['error'])) {
                            $error = '';
                            switch ($_GET['error']) {
                                case 'invalid':
                                    $error = 'Invalid username or password.';
                                    break;
                                case 'empty':
                                    $error = 'Please fill in all fields.';
                                    break;
                                case 'expired':
                                    $error = 'Your session has expired. Please login again.';
                                    break;
                                default:
                                    $error = 'An error occurred. Please try again.';
                            }
                            echo display_alert($error, 'danger');
                        }
                        
                        // Display success message if any
                        if (isset($_GET['success'])) {
                            $message = '';
                            switch ($_GET['success']) {
                                case 'registered':
                                    $message = 'Registration successful. You can now login.';
                                    break;
                                case 'logout':
                                    $message = 'You have been logged out successfully.';
                                    break;
                                default:
                                    $message = 'Operation successful.';
                            }
                            echo display_alert($message, 'success');
                        }
                        ?>
                        
                        <form action="login_process.php" method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p>Don't have an account? <a href="register.php">Register here</a></p>
                        <p>Continue as <a href="guest/dashboard.php">Guest</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>