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
    <link rel="stylesheet" href="/NEUST_REPOSITORY_SYSTEM/assets/css/dashboard-bg.css">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="/NEUST_REPOSITORY_SYSTEM/index.php">
                    <img src="http://localhost/mgt%20repo/img/neust_logo.png" alt="NEUST Logo" height="40">
                    NEUST Digital Repository Complete Research Project System
                    <img src="https://scontent.fcrk3-4.fna.fbcdn.net/v/t1.15752-9/552581069_2011437836268230_2095169435658182307_n.png?stp=dst-png_s480x480&_nc_cat=108&ccb=1-7&_nc_sid=0024fc&_nc_ohc=RFTC0FtlFD4Q7kNvwEOgxNP&_nc_oc=AdlahWZ7H5zNFTrs9GwoA_7D_88GxfM-WBs0v3hERJ0q-vWSno9dzVN9dsXFeH3GvTo&_nc_ad=z-m&_nc_cid=0&_nc_zt=23&_nc_ht=scontent.fcrk3-4.fna&oh=03_Q7cD3gEO_gTiW1DkAop2jWFsLzInFbaqAxBWNQ1jzdDbJTTOYw&oe=6926D5B8" alt="NEUST Logo" height="40">
                    <img src="https://scontent.fcrk3-3.fna.fbcdn.net/v/t1.15752-9/552295913_801033142295719_7514254484468521732_n.png?stp=dst-png_s480x480&_nc_cat=100&ccb=1-7&_nc_sid=0024fc&_nc_ohc=KrR3CxfhIYAQ7kNvwHI-ktX&_nc_oc=AdnAbgfXcDBh8izv2co2x9Ik_5LUglcYXDbOJDtfo7VG1Iy5JoLVY5bTV18zfHtu5uQ&_nc_ad=z-m&_nc_cid=0&_nc_zt=23&_nc_ht=scontent.fcrk3-3.fna&oh=03_Q7cD3gEHtRpd0vI9UtLTkEiIXNEoseUCi477Y_3T468TUC-AaA&oe=6926CD3F" alt="NEUST Logo" height="40">
                    <img src="https://scontent.fcrk3-2.fna.fbcdn.net/v/t1.15752-9/552085111_803108978879860_1283386021329109856_n.png?stp=dst-png_s480x480&_nc_cat=101&ccb=1-7&_nc_sid=0024fc&_nc_ohc=oE3Vm4UiRD0Q7kNvwFDZ6ZE&_nc_oc=AdnU8vCmnVqdO1_Aehkk9zdRbRh9MFU9pVjnopi1GnmlayOGymFxwdKR4VUc3eyi-28&_nc_ad=z-m&_nc_cid=0&_nc_zt=23&_nc_ht=scontent.fcrk3-2.fna&oh=03_Q7cD3gGj5vv9kHPSH9kExowL_SwPSqypW-uKm6VsSwhZSTot-g&oe=6926FEB9" alt="NEUST Logo" height="40">
                </a>
                 
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/NEUST_REPOSITORY_SYSTEM/views/search.php">
                                <i class="fas fa-search"></i> Search
                            </a>
                        </li>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
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
                                <li class="nav-item">
                                    <a class="nav-link position-relative" href="/NEUST_REPOSITORY_SYSTEM/admin/notifications.php">
                                        <i class="fas fa-bell"></i> Notifications
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            <?php 
                                            // Count unread notifications
                                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0");
                                            $stmt->execute();
                                            $result = $stmt->get_result()->fetch_assoc();
                                            echo $result['count'] > 0 ? $result['count'] : '';
                                            ?>
                                        </span>
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
                            <?php if (isset($_SESSION['verified_student'])): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="#">
                                        <i class="fas fa-user-graduate"></i> <?php echo $_SESSION['student_name']; ?> (<?php echo $_SESSION['student_section']; ?>)
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="/NEUST_REPOSITORY_SYSTEM/verify_student.php">
                                        <i class="fas fa-id-card"></i> Verify Student ID
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/NEUST_REPOSITORY_SYSTEM/login.php">
                                        <i class="fas fa-sign-in-alt"></i> Admin/Faculty Login
                                    </a>
                                </li>
                            <?php endif; ?>
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
                            
