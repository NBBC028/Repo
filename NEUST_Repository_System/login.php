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
    <title>Login - NEUST TALAVERA OFF CAMPUS DIGITAL REPOSITORY OF COMPLETED RESEARCH MANAGEMENT SYSTEM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* Centering the login card */
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            width: 100%;
            max-width: 600px; /* slightly larger */
            border-radius: 10px;
        }

        .card-header img {
            display: block;
            margin: 0 auto 10px;
        }

        .card-header h3 {
            font-size: 1.05rem;
            line-height: 1.4;
        }

        @media (max-width: 576px) {
            .card-header h3 {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="card shadow">
        <div class="card-header bg-primary text-white text-center">
            <img src="http://localhost/mgt%20repo/img/neust_logo.png" alt="NEUST Logo" height="100">
            <h3>NEUST TALAVERA OFF-CAMPUS DIGITAL REPOSITORY OF COMPLETED RESEARCH PROJECT</h3>
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
                    case 'pending':
                        $error = 'Your account is still pending verification. Please wait for admin approval.';
                        break;
                    case 'rejected':
                        $error = 'Your account has been rejected. Contact admin for more details.';
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
                        $message = 'Registration successful. You can now login once approved by the admin.';
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

                <!-- Login button -->
                <div class="d-grid gap-2 mb-3">
                    <button type="submit" class="btn btn-primary btn-sm">Login</button>
                </div>

                <!-- Additional buttons -->
                <div class="d-flex flex-column flex-sm-row justify-content-between">
                    <a href="register.php" class="btn btn-primary btn-sm w-100 w-sm-50 mb-2 mb-sm-0 me-sm-2">
                        <i class="fas fa-user-plus"></i> Register Here
                    </a>
                    <a href="student/dashboard.php" class="btn btn-primary btn-sm w-100 w-sm-50">
                        <i class="fas fa-user-graduate"></i> Continue as Student
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
