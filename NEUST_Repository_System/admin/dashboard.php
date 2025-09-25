<?php
require_once '../includes/session.php';
require_once '../includes/db.php';

// Restrict access to admin only
restrict_access(['admin']);

// Handle approval action
if (isset($_GET['approve']) && !empty($_GET['approve'])) {
    $research_id = (int)$_GET['approve'];

    // Update status to public
    $stmt = $conn->prepare("UPDATE research SET status = 'public' WHERE id = ?");
    $stmt->bind_param("i", $research_id);
    $stmt->execute();

    $_SESSION['message'] = "Research approved successfully.";
    $_SESSION['message_type'] = "success";

    header("Location: ../admin/dashboard.php");
    exit;
}

// Get statistics
$total_research = $conn->query("SELECT COUNT(*) as count FROM research")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_faculty = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'faculty'")->fetch_assoc()['count'];
$total_students = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'")->fetch_assoc()['count'];

// Get recent uploads with year & section
$recent_uploads = $conn->query("
    SELECT r.*, u.full_name, u.year_section
    FROM research r 
    JOIN users u ON r.uploaded_by = u.id 
    ORDER BY r.created_at DESC 
    LIMIT 10
");

// Get top departments
$top_departments = $conn->query("
    SELECT department, COUNT(*) as count 
    FROM research 
    GROUP BY department 
    ORDER BY count DESC 
    LIMIT 5
");
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <h1 class="mb-4">Admin Dashboard</h1>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Research Papers</h5>
                    <h2 class="display-4"><?php echo $total_research; ?></h2>
                    <p class="card-text"><a href="../views/search.php" class="text-white">View All</a></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <h2 class="display-4"><?php echo $total_users; ?></h2>
                    <p class="card-text"><a href="../views/reports.php?report=users" class="text-white">View Details</a></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Faculty Members</h5>
                    <h2 class="display-4"><?php echo $total_faculty; ?></h2>
                    <p class="card-text"><a href="../views/reports.php?report=faculty" class="text-white">View Details</a></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Students</h5>
                    <h2 class="display-4"><?php echo $total_students; ?></h2>
                    <p class="card-text"><a href="../views/reports.php?report=students" class="text-white">View Details</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Uploads Table -->
    <div class="row">
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Recent Uploads</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author(s)</th>
                                    <th>Department</th>
                                    <th>Year Published</th>
                                    <th>Year & Section</th>
                                    <th>Uploaded By</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_uploads->num_rows > 0): ?>
                                    <?php while ($research = $recent_uploads->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($research['title']); ?></td>
                                            <td><?php echo htmlspecialchars($research['authors']); ?></td>
                                            <td><?php echo htmlspecialchars($research['department']); ?></td>
                                            <td><?php echo htmlspecialchars($research['year_published']); ?></td>
                                            <td><?php echo !empty($research['year_section']) ? htmlspecialchars($research['year_section']) : '-'; ?></td>
                                            <td style="white-space: nowrap;"><?php echo htmlspecialchars($research['full_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($research['created_at'])); ?></td>
                                            <td>
                                                <?php if ($research['status'] === 'waiting'): ?>
                                                    <span class="badge bg-warning text-dark">Waiting for Approval</span>
                                                <?php elseif ($research['status'] === 'public'): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($research['status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-nowrap gap-1">
                                                    <a href="../<?php echo $research['file_path']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="../views/upload_research.php?edit=<?php echo $research['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="../views/upload_research.php?delete=<?php echo $research['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this research?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <?php if ($research['status'] === 'waiting'): ?>
                                                        <a href="?approve=<?php echo $research['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this research?')">
                                                            <i class="fas fa-check"></i> Approve
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No research papers found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end">
                        <a href="../views/search.php" class="btn btn-primary">View All Research</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Departments and Quick Actions -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Top Departments</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php if ($top_departments->num_rows > 0): ?>
                            <?php while ($dept = $top_departments->fetch_assoc()): ?>
                                <a href="../views/search.php?department=<?php echo urlencode($dept['department']); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($dept['department']); ?>
                                    <span class="badge bg-primary rounded-pill"><?php echo $dept['count']; ?></span>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="list-group-item">No departments found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="../views/upload_research.php" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Research</a>
                        <a href="../views/reports.php" class="btn btn-success"><i class="fas fa-chart-bar"></i> Generate Reports</a>
                        <a href="../views/search.php" class="btn btn-info text-white"><i class="fas fa-search"></i> Advanced Search</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
