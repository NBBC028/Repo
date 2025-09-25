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
    <title>Register - NEUST Repository System</title>
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
                        <h3>NEUST-MGT Repository Complete Research Project System</h3>
                        <p>Register for an account</p>
                    </div>
                    <div class="card-body">
                        <?php
                        // Display error message if any
                        if (isset($_GET['error'])) {
                            $error = '';
                            switch ($_GET['error']) {
                                case 'empty':
                                    $error = 'Please fill in all fields.';
                                    break;
                                case 'username':
                                    $error = 'Username already exists.';
                                    break;
                                case 'email':
                                    $error = 'Email already exists.';
                                    break;
                                case 'password':
                                    $error = 'Passwords do not match.';
                                    break;
                                default:
                                    $error = 'An error occurred. Please try again.';
                            }
                            echo display_alert($error, 'danger');
                        }
                        ?>
                        
                        <form action="register_process.php" method="post">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
                                    <select class="form-select" id="role" name="role" required onchange="toggleYearSection()">
                                        <option value="student">Student</option>
                                        <option value="faculty">Faculty</option>
                                        <option value="guest">Guest</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="department" class="form-label">Department</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                                    <select class="form-select" id="department" name="department" required>
                                        <option value="Business Administration">Bachelor of Science in Business Administration</option>
                                        <option value="Information Technology">Bachelor of Science in Information Technology</option>
                                        <option value="Elementary Education">Bachelor of Elementary Education</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Year & Section: Only for Students -->
                            <div class="mb-3" id="year_section_field">
                                <label for="year_section" class="form-label">Year & Section</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-graduation-cap"></i></span>
                                    <input type="text" class="form-control" id="year_section" name="year_section" placeholder="e.g. 3A" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
function toggleYearSection() {
    const role = document.getElementById('role').value;
    const yearSectionField = document.getElementById('year_section_field');
    const yearInput = document.getElementById('year_section');
    if (role === 'student') {
        yearSectionField.style.display = 'block';
        yearInput.required = true;
    } else {
        yearSectionField.style.display = 'none';
        yearInput.required = false;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', toggleYearSection);
</script>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
