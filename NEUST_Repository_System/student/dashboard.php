<?php
require_once '../includes/session.php';
require_once '../includes/functions.php'; // contains sanitize_input() and other helpers

// Get all approved research papers
$recent_research = $conn->query("
    SELECT r.id, r.title, r.abstract, r.authors, r.department, r.year_published, u.full_name 
    FROM research r 
    JOIN users u ON r.uploaded_by = u.id 
    WHERE r.status = 'approved'
    ORDER BY r.created_at DESC
    LIMIT 10
");

// Get departments for filter
$departments = $conn->query("
    SELECT DISTINCT department 
    FROM research 
    WHERE status = 'approved'
    ORDER BY department
");

// Helper function for search
function search_research($keywords, $department, $year, $conn) {
    $sql = "
        SELECT r.id, r.title, r.abstract, r.authors, r.department, r.year_published, u.full_name 
        FROM research r 
        JOIN users u ON r.uploaded_by = u.id 
        WHERE r.status = 'approved'
    ";

    if (!empty($keywords)) {
        $keywords = $conn->real_escape_string($keywords);
        $sql .= " AND (r.title LIKE '%$keywords%' OR r.abstract LIKE '%$keywords%' OR r.authors LIKE '%$keywords%')";
    }
    if (!empty($department)) {
        $department = $conn->real_escape_string($department);
        $sql .= " AND r.department = '$department'";
    }
    if (!empty($year)) {
        $year = $conn->real_escape_string($year);
        $sql .= " AND r.year_published = '$year'";
    }

    $sql .= " ORDER BY r.created_at DESC";

    return $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Access - NEUST Repository System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
<header>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <img src="http://localhost/mgt%20repo/img/neust_logo.png" alt="NEUST Logo" height="40">
            <img src="https://scontent.fcrk3-4.fna.fbcdn.net/v/t1.15752-9/552581069_2011437836268230_2095169435658182307_n.png?stp=dst-png_s480x480&_nc_cat=108&ccb=1-7&_nc_sid=0024fc&_nc_ohc=RFTC0FtlFD4Q7kNvwEOgxNP&_nc_oc=AdlahWZ7H5zNFTrs9GwoA_7D_88GxfM-WBs0v3hERJ0q-vWSno9dzVN9dsXFeH3GvTo&_nc_ad=z-m&_nc_cid=0&_nc_zt=23&_nc_ht=scontent.fcrk3-4.fna&oh=03_Q7cD3gEO_gTiW1DkAop2jWFsLzInFbaqAxBWNQ1jzdDbJTTOYw&oe=6926D5B8" alt="NEUST Logo" height="40">
                    <img src="https://scontent.fcrk3-3.fna.fbcdn.net/v/t1.15752-9/552295913_801033142295719_7514254484468521732_n.png?stp=dst-png_s480x480&_nc_cat=100&ccb=1-7&_nc_sid=0024fc&_nc_ohc=KrR3CxfhIYAQ7kNvwHI-ktX&_nc_oc=AdnAbgfXcDBh8izv2co2x9Ik_5LUglcYXDbOJDtfo7VG1Iy5JoLVY5bTV18zfHtu5uQ&_nc_ad=z-m&_nc_cid=0&_nc_zt=23&_nc_ht=scontent.fcrk3-3.fna&oh=03_Q7cD3gEHtRpd0vI9UtLTkEiIXNEoseUCi477Y_3T468TUC-AaA&oe=6926CD3F" alt="NEUST Logo" height="40">
                    <img src="https://scontent.fcrk3-2.fna.fbcdn.net/v/t1.15752-9/552085111_803108978879860_1283386021329109856_n.png?stp=dst-png_s480x480&_nc_cat=101&ccb=1-7&_nc_sid=0024fc&_nc_ohc=oE3Vm4UiRD0Q7kNvwFDZ6ZE&_nc_oc=AdnU8vCmnVqdO1_Aehkk9zdRbRh9MFU9pVjnopi1GnmlayOGymFxwdKR4VUc3eyi-28&_nc_ad=z-m&_nc_cid=0&_nc_zt=23&_nc_ht=scontent.fcrk3-2.fna&oh=03_Q7cD3gGj5vv9kHPSH9kExowL_SwPSqypW-uKm6VsSwhZSTot-g&oe=6926FEB9" alt="NEUST Logo" height="40">
                </a>
                 

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-book"></i> Browse
                    </a>
                </li>

                <!-- 🔔 Notification Bell Dropdown -->
                <li class="nav-item dropdown ms-3">
                    <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notifCount">0</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="notificationDropdown" style="width: 300px;">
                        <li class="dropdown-header bg-primary text-white text-center">Notifications</li>
                        <div id="notifList" class="p-2" style="max-height: 300px; overflow-y: auto;">
                            <li class="text-center text-muted small">No new notifications</li>
                        </div>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
</header>

<main class="container mt-4">

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
            echo $_SESSION['success_message']; 
            unset($_SESSION['success_message']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_messages']) && is_array($_SESSION['error_messages'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            <?php 
                foreach ($_SESSION['error_messages'] as $error) {
                    echo '<li>' . $error . '</li>';
                }
                unset($_SESSION['error_messages']);
            ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">NEUST TALAVERA OFF-CAMPUS DIGITAL REPOSITORY OF COMPLETED RESEARCH PROJECT</h5>
    </div>
    <div class="card-body">
        <p class="lead">Browse Research Abstracts from Nueva Ecija University of Science and Technology.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Search Abstracts</h5>
            </div>
            <div class="card-body">
                <form action="dashboard.php" method="get">
                    <div class="mb-3">
                        <label for="keywords" class="form-label">Keywords</label>
                        <input type="text" class="form-control" id="keywords" name="keywords" 
                               value="<?php echo isset($_GET['keywords']) ? htmlspecialchars($_GET['keywords']) : ''; ?>" 
                               placeholder="Enter keywords...">
                    </div>
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-select" id="department" name="department">
                            <option value="">All Departments</option>
                            <?php while ($dept = $departments->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                    <?php echo (isset($_GET['department']) && $_GET['department'] == $dept['department']) ? 'selected' : ''; ?> >
                                    <?php echo htmlspecialchars($dept['department']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="year" class="form-label">Year Published</label>
                        <select class="form-select" id="year" name="year">
                            <option value="">All Years</option>
                            <?php for ($i = date('Y'); $i >= 2000; $i--): ?>
                                <option value="<?php echo $i; ?>" 
                                        <?php echo (isset($_GET['year']) && $_GET['year'] == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <img src="http://localhost/mgt%20repo/img/neust_logo.png" alt="NEUST Logo" height="40">
                    Research Abstracts
                </h5>
            </div>
            <div class="card-body">
                <?php
                if (isset($_GET['keywords']) || isset($_GET['department']) || isset($_GET['year'])) {
                    $keywords = isset($_GET['keywords']) ? sanitize_input($_GET['keywords']) : '';
                    $department = isset($_GET['department']) ? sanitize_input($_GET['department']) : '';
                    $year = isset($_GET['year']) ? sanitize_input($_GET['year']) : '';

                    $search_results = search_research($keywords, $department, $year, $conn);

                    if ($search_results && $search_results->num_rows > 0) {
                        $research_list = $search_results;
                    } else {
                        $research_list = [];
                        echo '<div class="alert alert-info">No research papers found matching your search criteria.</div>';
                    }
                } else {
                    $research_list = $recent_research;
                }

                if ($research_list && $research_list->num_rows > 0) {
                    while ($research = $research_list->fetch_assoc()) {
                        ?>
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h5 class="card-title"><?php echo htmlspecialchars($research['title']); ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">
                                    Authors: <?php echo htmlspecialchars($research['authors']); ?> | <?php echo $research['year_published']; ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($research['abstract'])); ?></p>
                            </div>
                            <div class="card-footer bg-light">
                                <small class="text-muted">Department: <?php echo htmlspecialchars($research['department']); ?></small>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="alert alert-info">No research abstracts available at this time.</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
</main>

<footer class="bg-blue text-white mt-5 py-3">
    <div class="container text-center">
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- 🔔 Notification Fetch Script -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    function loadNotifications() {
        // Replace this later with your backend PHP fetch
        const notifList = document.getElementById("notifList");
        const notifCount = document.getElementById("notifCount");

        // Example: Dummy notifications
        const dummyData = [
            { message: "Your research submission has been approved.", date: "2025-11-01 14:20" },
            { message: "Admin viewed your manuscript request.", date: "2025-10-31 10:15" }
        ];

        notifList.innerHTML = "";
        notifCount.textContent = dummyData.length;

        dummyData.forEach(n => {
            const item = document.createElement("li");
            item.classList.add("dropdown-item", "small");
            item.innerHTML = `<strong>${n.message}</strong><br><span class="text-muted">${n.date}</span>`;
            notifList.appendChild(item);
        });
    }

    loadNotifications();
});
</script>

</body>
</html>
