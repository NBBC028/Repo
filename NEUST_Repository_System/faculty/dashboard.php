<?php
require_once '../includes/session.php';

// Restrict access to faculty only
restrict_access(['faculty']);

// Get faculty's research papers
$faculty_id = $_SESSION['user_id'];
$faculty_research = $conn->query("
    SELECT * 
    FROM research 
    WHERE uploaded_by = $faculty_id 
    ORDER BY created_at DESC
");

// Get total research count for this faculty
$total_research = $conn->query("
    SELECT COUNT(*) as count 
    FROM research 
    WHERE uploaded_by = $faculty_id
")->fetch_assoc()['count'];
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <h1 class="mb-4">Faculty Dashboard</h1>

    <!-- Statistics Card -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">My Research Papers</h5>
                    <h2 class="display-4"><?php echo $total_research; ?></h2>
                    <p class="card-text"><a href="../views/search.php" class="text-white">View All Research</a></p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="d-grid">
                                <a href="../views/upload_research.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-upload fa-2x mb-2"></i><br>Upload Research
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-grid">
                                <a href="../views/search.php" class="btn btn-info btn-lg text-white">
                                    <i class="fas fa-search fa-2x mb-2"></i><br>Search Repository
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-grid">
                                <a href="#" class="btn btn-warning btn-lg" data-bs-toggle="modal" data-bs-target="#profileModal">
                                    <i class="fas fa-user-edit fa-2x mb-2"></i><br>Edit Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Faculty Research Table -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">My Research Papers</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Authors</th>
                            <th>Year</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Date Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($faculty_research->num_rows > 0): ?>
                            <?php while ($research = $faculty_research->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($research['title']); ?></td>
                                    <td><?php echo htmlspecialchars($research['authors']); ?></td>
                                    <td><?php echo $research['year_published']; ?></td>
                                    <td><?php echo htmlspecialchars($research['department']); ?></td>
                                    <td>
                                        <?php if ($research['status'] === 'waiting'): ?>
                                            <span class="badge bg-warning text-dark">Waiting for Approval</span>
                                        <?php elseif ($research['status'] === 'public'): ?>
                                            <span class="badge bg-success">Approved/Public</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($research['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($research['created_at'])); ?></td>
                                    <td>
                                        <?php if ($research['status'] === 'public'): ?>
                                            <a href="../<?php echo $research['file_path']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                        <!-- Removed edit button -->
                                        <a href="../views/upload_research.php?delete=<?php echo $research['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this research?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">You haven't uploaded any research papers yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-3">
                <a href="../views/upload_research.php" class="btn btn-primary">Upload New Research</a>
            </div>
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="profileModalLabel">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="../views/update_profile.php" method="post">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $_SESSION['full_name']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $_SESSION['email']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-select" id="department" name="department" required>
                            <option value="Computer Science" <?php echo ($_SESSION['department'] == 'Computer Science') ? 'selected' : ''; ?>>Bachelor of Science in Business Administration</option>
                            <option value="Information Technology" <?php echo ($_SESSION['department'] == 'Information Technology') ? 'selected' : ''; ?>>Bachelor of Science in Information Technology</option>
                            <option value="Business Administration" <?php echo ($_SESSION['department'] == 'Business Administration') ? 'selected' : ''; ?>>Bachelor of Elementary Education</option>
                            <option value="Other" <?php echo ($_SESSION['department'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
