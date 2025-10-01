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
                 <img src="https://scontent.fcrk3-4.fna.fbcdn.net/v/t1.15752-9/552581069_2011437836268230_2095169435658182307_n.png?stp=dst-png_s480x480&_nc_cat=108&ccb=1-7&_nc_sid=0024fc&_nc_ohc=bGUBRyQBa8EQ7kNvwFtFG8M&_nc_oc=AdlwhmaA1uwpnKxtwBPFwBODlh8SQzUm8JALj33YnmDrTGNyS0qjjK5tpEJcYv0-OTI&_nc_zt=23&_nc_ht=scontent.fcrk3-4.fna&oh=03_Q7cD3QEoolV5MnS1_jDhVHAWukL5_cT3TUDyfIuV8hQR-aFamw&oe=68FDBEF8" alt="NEUST Logo" height="40">
                   <img src="https://scontent.fcrk3-3.fna.fbcdn.net/v/t1.15752-9/552295913_801033142295719_7514254484468521732_n.png?stp=dst-png_s480x480&_nc_cat=100&ccb=1-7&_nc_sid=0024fc&_nc_ohc=jgMapZiIbAQQ7kNvwFZOOOz&_nc_oc=AdkQq_p07pKhW6ot5dYlx_vehL0z-t7o9gIXJHCoIbZ3Cf2wJAa6MGlfDXcZcuZ-_vY&_nc_zt=23&_nc_ht=scontent.fcrk3-3.fna&oh=03_Q7cD3QGlAlje6f7dnw106-iO2C34pn_fOFOJkX5GPGROkFALvQ&oe=68FDEEBF" alt="NEUST Logo" height="40">
                 <img src="https://scontent.fcrk3-2.fna.fbcdn.net/v/t1.15752-9/552085111_803108978879860_1283386021329109856_n.png?stp=dst-png_s480x480&_nc_cat=101&ccb=1-7&_nc_sid=0024fc&_nc_ohc=58Abs0o90N0Q7kNvwF0nMgC&_nc_oc=AdkHwUxrzclWv83PNfNFjXwcK8RO8YDf_c6OiDeYSNx08HRDxRzqh5dDkRPxtfyUTlE&_nc_zt=23&_nc_ht=scontent.fcrk3-2.fna&oh=03_Q7cD3QEySrXOTnQmthjQeO9Y6FRAmgx3b_MXuWryY81qoS46Cg&oe=68FDE7F9" alt="NEUST Logo" height="40">

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