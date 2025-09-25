<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEUST-MGT Repository Research Project System</title>
    <link rel="stylesheet" href="/NEUST_REPOSITORY_SYSTEM/assets/css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="/NEUST_REPOSITORY_SYSTEM/index.php">
                    <img src="http://localhost/mgt%20repo/img/neust_logo.png" alt="NEUST Logo" height="40">
                    NEUST-MGT Repository Complete Research Project System
                </a>
                 <img src="https://sdmntprnorthcentralus.oaiusercontent.com/files/00000000-e02c-622f-8ae1-be7511021c79/raw?se=2025-09-25T17%3A44%3A33Z&sp=r&sv=2024-08-04&sr=b&scid=7141dcf5-ee7b-50b7-9491-a2729cb3bb1c&skoid=5939c452-ea83-4420-b5b4-21182254a5d3&sktid=a48cca56-e6da-484e-a814-9c849652bcb3&skt=2025-09-24T20%3A13%3A18Z&ske=2025-09-25T20%3A13%3A18Z&sks=b&skv=2024-08-04&sig=%2BzUELAmNks03Lk0s229hYSGuVB2om5dlm40jGfbZ0FI%3D" alt="NEUST Logo" height="40">
                   <img src="https://sdmntpraustraliaeast.oaiusercontent.com/files/00000000-6990-61fa-be70-ced6f6cdd5b1/raw?se=2025-09-25T17%3A49%3A57Z&sp=r&sv=2024-08-04&sr=b&scid=a935c326-fcba-57f6-af64-ddba4a311a5f&skoid=5939c452-ea83-4420-b5b4-21182254a5d3&sktid=a48cca56-e6da-484e-a814-9c849652bcb3&skt=2025-09-25T16%3A20%3A43Z&ske=2025-09-26T16%3A20%3A43Z&sks=b&skv=2024-08-04&sig=COlqR6tW04lc0300uXJxigYx5wFDALTBDfzgtp6e5IY%3D" alt="NEUST Logo" height="40">
                 <img src="https://api.removal.ai/download/g1/preview/bc0c7c9b-b0b0-4a92-ad84-3e8d49f3b15a.png" alt="NEUST Logo" height="40">

                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/NEUST_REPOSITORY_SYSTEM/views/search.php">
                                    <i class="fas fa-search"></i> Search
                                </a>
                            </li>
                            
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="/NEUST_REPOSITORY_SYSTEM/admin/dashboard.php">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/NEUST_REPOSITORY_SYSTEM/views/reports.php">
                                        <i class="fas fa-chart-bar"></i> Reports
                                    </a>
                                </li>
                            <?php elseif ($_SESSION['role'] == 'faculty'): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="/NEUST_REPOSITORY_SYSTEM/faculty/dashboard.php">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/NEUST_REPOSITORY_SYSTEM/views/upload_research.php">
                                        <i class="fas fa-upload"></i> Upload Research
                                    </a>
                                </li>
                            <?php elseif ($_SESSION['role'] == 'student'): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="/NEUST_REPOSITORY_SYSTEM/student/dashboard.php">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="/NEUST_REPOSITORY_SYSTEM/guest/dashboard.php">
                                        <i class="fas fa-book"></i> Browse
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="/NEUST_REPOSITORY_SYSTEM/logout.php">Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/NEUST_REPOSITORY_SYSTEM/login.php">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/NEUST_REPOSITORY_SYSTEM/register.php">Register</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container mt-4">
        <?php
        // Display flash messages if any
        if (isset($_SESSION['message'])) {
            echo display_alert($_SESSION['message'], $_SESSION['message_type']);
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>