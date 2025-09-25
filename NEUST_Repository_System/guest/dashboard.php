<?php
require_once '../includes/session.php';

// Get recent public research papers
$recent_research = $conn->query("SELECT r.id, r.title, r.abstract, r.authors, r.department, r.year_published, u.full_name 
                                FROM research r 
                                JOIN users u ON r.uploaded_by = u.id 
                                WHERE r.status = 'public' 
                                ORDER BY r.created_at DESC 
                                LIMIT 10");

// Get departments for filter
$departments = $conn->query("SELECT DISTINCT department FROM research WHERE status = 'public' ORDER BY department");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Access - NEUST Repository System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="../index.php">
                    <img src="../assets/images/neust-logo.png" alt="NEUST Logo" height="40">
                    NEUST Repository
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-book"></i> Browse
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../register.php">Register</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container mt-4">
        <div class="jumbotron bg-light p-4 rounded mb-4">
            <h1 class="display-5">Welcome to NEUST Repository System</h1>
            <p class="lead">Browse research abstracts from Nueva Ecija University of Science and Technology.</p>
            <p>To access full research papers, please <a href="../register.php">register</a> or <a href="../login.php">login</a>.</p>
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
                                <option value="Computer Science">Bachelor of Science in Business Administration</option>
                                <option value="Information Technology">Bachelor of Science in Information Technology</option>
                                <option value="Business Administration">Bachelor of Elementary Education</option>
            
                                <select class="form-select" id="department" name="department">
                                    <option value="">All Departments</option>
                                    <?php while ($dept = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo $dept['department']; ?>" 
                                                <?php echo (isset($_GET['department']) && $_GET['department'] == $dept['department']) ? 'selected' : ''; ?>>
                                            <?php echo $dept['department']; ?>
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
                        <h5 class="mb-0">Research Abstracts</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Process search if submitted
                        if (isset($_GET['keywords']) || isset($_GET['department']) || isset($_GET['year'])) {
                            $keywords = isset($_GET['keywords']) ? sanitize_input($_GET['keywords']) : '';
                            $department = isset($_GET['department']) ? sanitize_input($_GET['department']) : '';
                            $year = isset($_GET['year']) ? sanitize_input($_GET['year']) : '';
                            
                            $search_results = search_research($keywords, $department, $year, $conn);
                            
                            if ($search_results->num_rows > 0) {
                                while ($research = $search_results->fetch_assoc()) {
                                    echo '<div class="card mb-3">';
                                    echo '<div class="card-header bg-light">';
                                    echo '<h5 class="card-title">' . $research['title'] . '</h5>';
                                    echo '<h6 class="card-subtitle mb-2 text-muted">Authors: ' . $research['authors'] . ' | ' . $research['year_published'] . '</h6>';
                                    echo '</div>';
                                    echo '<div class="card-body">';
                                    echo '<p class="card-text">' . substr($research['abstract'], 0, 300) . '...</p>';
                                    echo '</div>';
                                    echo '<div class="card-footer bg-light">';
                                    echo '<small class="text-muted">Department: ' . $research['department'] . '</small>';
                                    echo '<div class="mt-2"><a href="../login.php" class="btn btn-sm btn-primary">Login to View Full Paper</a></div>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="alert alert-info">No research papers found matching your criteria.</div>';
                            }
                        } else {
                            // Display recent research
                            if ($recent_research->num_rows > 0) {
                                while ($research = $recent_research->fetch_assoc()) {
                                    echo '<div class="card mb-3">';
                                    echo '<div class="card-header bg-light">';
                                    echo '<h5 class="card-title">' . $research['title'] . '</h5>';
                                    echo '<h6 class="card-subtitle mb-2 text-muted">Authors: ' . $research['authors'] . ' | ' . $research['year_published'] . '</h6>';
                                    echo '</div>';
                                    echo '<div class="card-body">';
                                    echo '<p class="card-text">' . substr($research['abstract'], 0, 300) . '...</p>';
                                    echo '</div>';
                                    echo '<div class="card-footer bg-light">';
                                    echo '<small class="text-muted">Department: ' . $research['department'] . ' | Uploaded by: ' . $research['full_name'] . '</small>';
                                    echo '<div class="mt-2"><a href="../login.php" class="btn btn-sm btn-primary">Login to View Full Paper</a></div>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="alert alert-info">No research papers available.</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="bg-dark text-white mt-5 py-3">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> NEUST Repository System. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>