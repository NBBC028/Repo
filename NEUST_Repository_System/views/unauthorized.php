<?php
require_once '../includes/session.php';
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-8">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Unauthorized Access</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-lock fa-5x text-danger"></i>
                    </div>
                    <h3>Access Denied</h3>
                    <p class="lead">You do not have permission to access this page.</p>
                    <p>If you believe this is an error, please contact the system administrator.</p>
                    
                    <div class="mt-4">
                        <?php if (is_logged_in()): ?>
                            <a href="../<?php echo $_SESSION['role']; ?>/dashboard.php" class="btn btn-primary">
                                <i class="fas fa-home"></i> Return to Dashboard
                            </a>
                        <?php else: ?>
                            <a href="../login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>