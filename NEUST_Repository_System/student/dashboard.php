<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Restrict access to student only
restrict_access(['student']);

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'], $_POST['research_id'])) {
    $research_id = intval($_POST['research_id']);
    $rating = intval($_POST['rating']);
    $user_id = $_SESSION['user_id'];

    if ($rating >= 1 && $rating <= 5) {
        $stmt = $conn->prepare("
            INSERT INTO research_ratings (research_id, user_id, rating) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = VALUES(rating), updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->bind_param("iii", $research_id, $user_id, $rating);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle Add to Favorite
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['favorite_research_id'])) {
    $research_id = intval($_POST['favorite_research_id']);
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        INSERT INTO research_favorites (user_id, research_id) 
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE created_at = NOW()
    ");
    $stmt->bind_param("ii", $user_id, $research_id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Get favorite research IDs for current user
$fav_result = $conn->query("SELECT research_id FROM research_favorites WHERE user_id = " . intval($_SESSION['user_id']));
$favorites = [];
while ($row = $fav_result->fetch_assoc()) {
    $favorites[] = $row['research_id'];
}

// Get favorite research details (if any)
$fav_research = null;
if (!empty($favorites)) {
    $fav_ids = implode(",", array_map('intval', $favorites));
    $fav_research = $conn->query("
        SELECT r.*, u.full_name, u.year_section
        FROM research r
        JOIN users u ON r.uploaded_by = u.id
        WHERE r.id IN ($fav_ids)
        ORDER BY FIELD(r.id, $fav_ids)
    ");
}

// Get recent research papers with ratings
$recent_research = $conn->query("
    SELECT r.*, u.full_name, u.year_section,
           COALESCE(AVG(rr.rating), 0) AS avg_rating, COUNT(rr.id) AS rating_count
    FROM research r
    JOIN users u ON r.uploaded_by = u.id
    LEFT JOIN research_ratings rr ON rr.research_id = r.id
    WHERE r.status = 'public'
    GROUP BY r.id
    ORDER BY r.created_at DESC
    LIMIT 10
");

// Get research papers by department
$student_department = $conn->real_escape_string($_SESSION['department']);
$department_research = $conn->query("
    SELECT r.*, u.full_name, u.year_section,
           COALESCE(AVG(rr.rating), 0) AS avg_rating, COUNT(rr.id) AS rating_count
    FROM research r
    JOIN users u ON r.uploaded_by = u.id
    LEFT JOIN research_ratings rr ON rr.research_id = r.id
    WHERE r.department = '$student_department' 
      AND r.status = 'public'
    GROUP BY r.id
    ORDER BY r.created_at DESC
    LIMIT 5
");
?>

<?php include '../includes/header.php'; ?>

<style>
.list-group-item.bg-primary {
    color: #fff;
    background-color: var(--bs-primary) !important;
    border-color: rgba(0,0,0,0.05);
}
.list-group-item.bg-primary:hover,
.list-group-item.bg-primary:focus {
    background-color: rgba(13,110,253,0.9) !important;
    color: #fff !important;
    text-decoration: none;
}
.modal-header.bg-primary {
    background-color: var(--bs-primary) !important;
    color: #fff;
}
.btn-primary {
    background-color: var(--bs-primary) !important;
    border-color: var(--bs-primary) !important;
}
.btn-primary:hover {
    background-color: #0b5ed7 !important;
    border-color: #0a58ca !important;
}
</style>

<div class="container-fluid">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Student Dashboard</h3>
        </div>
    </div>

    <div class="row">
        <!-- Recent Research -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Recent Research Papers</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Authors</th>
                                    <th>Department</th>
                                    <th>Year</th>
                                    <th>Year & Section</th>
                                    <th>Uploaded By</th>
                                    <th>Rating</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_research && $recent_research->num_rows > 0): ?>
                                    <?php while ($research = $recent_research->fetch_assoc()): ?>
                                        <?php 
                                            $file_path = "../" . $research['file_path'];
                                            $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($research['title']); ?></td>
                                            <td><?php echo htmlspecialchars($research['authors']); ?></td>
                                            <td><?php echo htmlspecialchars($research['department']); ?></td>
                                            <td><?php echo htmlspecialchars($research['year_published']); ?></td>
                                            <td><?php echo !empty($research['year_section']) ? htmlspecialchars($research['year_section']) : '-'; ?></td>
                                            <td><?php echo htmlspecialchars($research['full_name']); ?></td>
                                            <td>✰ <?php echo number_format($research['avg_rating'], 1); ?> (<?php echo $research['rating_count']; ?>)</td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#researchModal<?php echo $research['id']; ?>">
                                                    <i class="fas fa-eye"></i> View Abstract
                                                </button>
                                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#requestModal<?php echo $research['id']; ?>">
                                                    <i class="fas fa-file-pdf"></i> Request Full Manuscript
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Research Modal (Abstract Only) -->
                                        <div class="modal fade" id="researchModal<?php echo $research['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-xl">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title"><?php echo htmlspecialchars($research['title']); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="alert alert-info">
                                                                    <i class="fas fa-info-circle"></i> Full manuscript access requires verification. Please submit a request to view the complete document.
                                                                </div>
                                                                <div class="text-center mb-3">
                                                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestModal<?php echo $research['id']; ?>">
                                                                        <i class="fas fa-file-download"></i> Request Full Manuscript
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p><strong>Authors:</strong> <?php echo htmlspecialchars($research['authors']); ?></p>
                                                                <p><strong>Department:</strong> <?php echo htmlspecialchars($research['department']); ?></p>
                                                                <p><strong>Year Published:</strong> <?php echo htmlspecialchars($research['year_published']); ?></p>
                                                                <p><strong>Year & Section:</strong> <?php echo !empty($research['year_section']) ? htmlspecialchars($research['year_section']) : '-'; ?></p>
                                                                <p><strong>Uploaded By:</strong> <?php echo htmlspecialchars($research['full_name']); ?></p>
                                                                <hr>
                                                                <p><strong>Abstract:</strong></p>
                                                                <div class="abstract-container p-3 bg-light rounded">
                                                                    <?php echo nl2br(htmlspecialchars($research['abstract'])); ?>
                                                                </div>

                                                                <hr>
                                                                <!-- Rating Form -->
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="research_id" value="<?php echo $research['id']; ?>">
                                                                    <label><strong>Rate this Research:</strong></label><br>
                                                                    <?php for ($i=1; $i<=5; $i++): ?>
                                                                        <button type="submit" name="rating" value="<?php echo $i; ?>" class="btn btn-sm <?php echo ($i <= round($research['avg_rating'])) ? 'btn-warning' : 'btn-outline-secondary'; ?>">✰</button>
                                                                    <?php endfor; ?>
                                                                </form>

                                                                <!-- Add to Favorite -->
                                                                <form method="POST" class="mt-2">
                                                                    <input type="hidden" name="favorite_research_id" value="<?php echo $research['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm <?php echo in_array($research['id'], $favorites) ? 'btn-success' : 'btn-outline-primary'; ?>">
                                                                        <?php echo in_array($research['id'], $favorites) ? '★ Favorited' : '☆ Add to Favorites'; ?>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <a href="<?php echo $file_path; ?>" class="btn btn-primary" target="_blank">
                                                            <i class="fas fa-file-pdf"></i> View Full Research
                                                        </a>
                                                        <a href="<?php echo $file_path; ?>" class="btn btn-primary" download>
                                                            <i class="fas fa-download"></i> Download
                                                        </a>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Request Modal -->
                                        <div class="modal fade" id="requestModal<?php echo $research['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title">Request Full Manuscript</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="request_manuscript.php" method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="research_id" value="<?php echo $research['id']; ?>">
                                                            <input type="hidden" name="student_id" value="<?php echo base64_encode($_SESSION['user_id']); ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label for="purpose" class="form-label">Purpose of Request</label>
                                                                <select name="purpose" id="purpose" class="form-select" required>
                                                                    <option value="">Select Purpose</option>
                                                                    <option value="Research">Research</option>
                                                                    <option value="Academic Project">Academic Project</option>
                                                                    <option value="Thesis Reference">Thesis Reference</option>
                                                                    <option value="Other">Other</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="other_purpose" class="form-label">If Other, please specify</label>
                                                                <textarea name="other_purpose" id="other_purpose" class="form-control" rows="2"></textarea>
                                                            </div>
                                                            
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-exclamation-triangle"></i> By submitting this request, you agree to:
                                                                <ul class="mb-0 mt-2">
                                                                    <li>Use the manuscript for educational purposes only</li>
                                                                    <li>Not distribute or share the manuscript with others</li>
                                                                    <li>Properly cite the work if used in your own research</li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Submit Request</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="text-center">No research papers found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-end">
                        <a href="../views/search.php" class="btn btn-primary">Advanced Search</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Research by Department -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Research in Your Department</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php if ($department_research && $department_research->num_rows > 0): ?>
                            <?php while ($r = $department_research->fetch_assoc()): ?>
                                <?php 
                                    $dept_file_path = "../" . $r['file_path'];
                                    $dept_file_ext = strtolower(pathinfo($dept_file_path, PATHINFO_EXTENSION));
                                ?>
                                <a href="#" class="list-group-item list-group-item-action bg-primary text-white mb-2" data-bs-toggle="modal" data-bs-target="#deptResearchModal<?php echo $r['id']; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 mb-0"><?php echo htmlspecialchars($r['title']); ?></h6>
                                        <small><?php echo htmlspecialchars($r['year_published']); ?></small>
                                    </div>
                                    <small>Year & Section: <?php echo !empty($r['year_section']) ? htmlspecialchars($r['year_section']) : '-'; ?> | ✰ <?php echo number_format($r['avg_rating'], 1); ?> (<?php echo $r['rating_count']; ?>)</small>
                                </a>

                                <!-- Department Modal -->
                                <div class="modal fade" id="deptResearchModal<?php echo $r['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-xl">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title"><?php echo htmlspecialchars($r['title']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <?php if ($dept_file_ext === 'pdf'): ?>
                                                            <embed src="<?php echo $dept_file_path; ?>#page=1" type="application/pdf" width="100%" height="400px" />
                                                        <?php elseif ($dept_file_ext === 'doc' || $dept_file_ext === 'docx'): ?>
                                                            <iframe src="https://docs.google.com/gview?url=http://localhost/NEUST_Repository_System/<?php echo $r['file_path']; ?>&embedded=true" 
                                                                    style="width:100%; height:400px;" frameborder="0"></iframe>
                                                        <?php else: ?>
                                                            <p class="text-danger">Preview not available. Please download the file to view.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Authors:</strong> <?php echo htmlspecialchars($r['authors']); ?></p>
                                                        <p><strong>Department:</strong> <?php echo htmlspecialchars($r['department']); ?></p>
                                                        <p><strong>Year Published:</strong> <?php echo htmlspecialchars($r['year_published']); ?></p>
                                                        <p><strong>Year & Section:</strong> <?php echo !empty($r['year_section']) ? htmlspecialchars($r['year_section']) : '-'; ?></p>
                                                        <p><strong>Uploaded By:</strong> <?php echo htmlspecialchars($r['full_name']); ?></p>
                                                        <hr>
                                                        <p><strong>Abstract:</strong></p>
                                                        <p><?php echo nl2br(htmlspecialchars($r['abstract'])); ?></p>

                                                        <hr>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="research_id" value="<?php echo $r['id']; ?>">
                                                            <label><strong>Rate this Research:</strong></label><br>
                                                            <?php for ($i=1; $i<=5; $i++): ?>
                                                                <button type="submit" name="rating" value="<?php echo $i; ?>" class="btn btn-sm <?php echo ($i <= round($r['avg_rating'])) ? 'btn-warning' : 'btn-outline-secondary'; ?>">✰</button>
                                                            <?php endfor; ?>
                                                        </form>

                                                        <form method="POST" class="mt-2">
                                                            <input type="hidden" name="favorite_research_id" value="<?php echo $r['id']; ?>">
                                                            <button type="submit" class="btn btn-sm <?php echo in_array($r['id'], $favorites) ? 'btn-success' : 'btn-outline-primary'; ?>">
                                                                <?php echo in_array($r['id'], $favorites) ? '★ Favorited' : '☆ Add to Favorites'; ?>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <a href="<?php echo $dept_file_path; ?>" class="btn btn-primary" target="_blank"><i class="fas fa-file-pdf"></i> View Full Research</a>
                                                <a href="<?php echo $dept_file_path; ?>" class="btn btn-primary" download><i class="fas fa-download"></i> Download</a>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="list-group-item">No research papers found in your department.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Favorites Section -->
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">My Favorite Research Papers</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Authors</th>
                            <th>Department</th>
                            <th>Year</th>
                            <th>Year & Section</th>
                            <th>Uploaded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($fav_research && $fav_research->num_rows > 0): ?>
                            <?php while ($fav = $fav_research->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fav['title']); ?></td>
                                    <td><?php echo htmlspecialchars($fav['authors']); ?></td>
                                    <td><?php echo htmlspecialchars($fav['department']); ?></td>
                                    <td><?php echo htmlspecialchars($fav['year_published']); ?></td>
                                    <td><?php echo !empty($fav['year_section']) ? htmlspecialchars($fav['year_section']) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($fav['full_name']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">You have not added any favorites yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

    <!-- Add Manuscript Request Modals -->
    <?php if ($recent_research && $recent_research->num_rows > 0): ?>
        <?php $recent_research->data_seek(0); ?>
        <?php while ($research = $recent_research->fetch_assoc()): ?>
            <!-- Manuscript Request Modal -->
            <div class="modal fade" id="requestModal<?php echo $research['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Request Full Manuscript</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="request_manuscript.php" method="post">
                            <div class="modal-body">
                                <input type="hidden" name="research_id" value="<?php echo $research['id']; ?>">
                                <input type="hidden" name="student_id" value="<?php echo base64_encode($_SESSION['user_id']); ?>">
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Please provide the purpose of your request to access the full manuscript.
                                </div>
                                
                                <div class="mb-3">
                                    <label for="purpose<?php echo $research['id']; ?>" class="form-label">Purpose of Request</label>
                                    <select class="form-select" id="purpose<?php echo $research['id']; ?>" name="purpose" required>
                                        <option value="">Select Purpose</option>
                                        <option value="Research">Research</option>
                                        <option value="Academic Project">Academic Project</option>
                                        <option value="Thesis Reference">Thesis Reference</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="other_purpose<?php echo $research['id']; ?>" class="form-label">If Other, please specify</label>
                                    <textarea class="form-control" id="other_purpose<?php echo $research['id']; ?>" name="other_purpose" rows="2"></textarea>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> By submitting this request, you agree to:
                                    <ul class="mb-0 mt-2">
                                        <li>Use the manuscript for educational purposes only</li>
                                        <li>Not distribute or share the manuscript with others</li>
                                        <li>Properly cite the work if used in your own research</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Submit Request</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

<?php include '../includes/footer.php'; ?>
