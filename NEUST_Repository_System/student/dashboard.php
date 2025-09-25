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
$fav_result = $conn->query("SELECT research_id FROM research_favorites WHERE user_id = {$_SESSION['user_id']}");
$favorites = [];
while ($row = $fav_result->fetch_assoc()) {
    $favorites[] = $row['research_id'];
}

// Get recent research papers with ratings
$recent_research = $conn->query("
    SELECT r.*, u.full_name, 
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
$student_department = $_SESSION['department'];
$department_research = $conn->query("
    SELECT r.*, u.full_name, 
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

<div class="container-fluid">
    <h1 class="mb-4">Student Dashboard</h1>
    
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
                                    <th>Uploaded By</th>
                                    <th>Rating</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_research->num_rows > 0): ?>
                                    <?php while ($research = $recent_research->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($research['title']); ?></td>
                                            <td><?php echo htmlspecialchars($research['authors']); ?></td>
                                            <td><?php echo htmlspecialchars($research['department']); ?></td>
                                            <td><?php echo $research['year_published']; ?></td>
                                            <td><?php echo htmlspecialchars($research['full_name']); ?></td>
                                            <td>
                                                ✰ <?php echo number_format($research['avg_rating'], 1); ?> (<?php echo $research['rating_count']; ?>)
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#researchModal<?php echo $research['id']; ?>">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Modal -->
                                        <div class="modal fade" id="researchModal<?php echo $research['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-xl">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title"><?php echo htmlspecialchars($research['title']); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <embed src="../<?php echo $research['file_path']; ?>#page=1" type="application/pdf" width="100%" height="400px" />
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p><strong>Authors:</strong> <?php echo htmlspecialchars($research['authors']); ?></p>
                                                                <p><strong>Department:</strong> <?php echo htmlspecialchars($research['department']); ?></p>
                                                                <p><strong>Year Published:</strong> <?php echo $research['year_published']; ?></p>
                                                                <p><strong>Uploaded By:</strong> <?php echo htmlspecialchars($research['full_name']); ?></p>
                                                                <hr>
                                                                <p><strong>Abstract:</strong></p>
                                                                <p><?php echo nl2br(htmlspecialchars($research['abstract'])); ?></p>

                                                                <hr>
                                                                <!-- Rating Form -->
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="research_id" value="<?php echo $research['id']; ?>">
                                                                    <label><strong>Rate this Research:</strong></label><br>
                                                                    <?php for ($i=1; $i<=5; $i++): ?>
                                                                        <button type="submit" name="rating" value="<?php echo $i; ?>" class="btn btn-sm <?php echo ($i <= round($research['avg_rating'])) ? 'btn-warning' : 'btn-outline-secondary'; ?>">
                                                                            ✰
                                                                        </button>
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
                                                        <a href="../<?php echo $research['file_path']; ?>" class="btn btn-success" target="_blank">View Full Research</a>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No research papers found.</td>
                                    </tr>
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
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Research in Your Department</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php if ($department_research->num_rows > 0): ?>
                            <?php while ($research = $department_research->fetch_assoc()): ?>
                                <a href="#" class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#deptResearchModal<?php echo $research['id']; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($research['title']); ?></h6>
                                        <small><?php echo $research['year_published']; ?></small>
                                    </div>
                                    <small>✰ <?php echo number_format($research['avg_rating'], 1); ?> (<?php echo $research['rating_count']; ?>)</small>
                                </a>

                                <!-- Department Modal -->
                                <div class="modal fade" id="deptResearchModal<?php echo $research['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-xl">
                                        <div class="modal-content">
                                            <div class="modal-header bg-info text-white">
                                                <h5 class="modal-title"><?php echo htmlspecialchars($research['title']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <embed src="../<?php echo $research['file_path']; ?>#page=1" type="application/pdf" width="100%" height="400px" />
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Authors:</strong> <?php echo htmlspecialchars($research['authors']); ?></p>
                                                        <p><strong>Department:</strong> <?php echo htmlspecialchars($research['department']); ?></p>
                                                        <p><strong>Year Published:</strong> <?php echo $research['year_published']; ?></p>
                                                        <p><strong>Uploaded By:</strong> <?php echo htmlspecialchars($research['full_name']); ?></p>
                                                        <hr>
                                                        <p><strong>Abstract:</strong></p>
                                                        <p><?php echo nl2br(htmlspecialchars($research['abstract'])); ?></p>

                                                        <hr>
                                                        <!-- Rating Form -->
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="research_id" value="<?php echo $research['id']; ?>">
                                                            <label><strong>Rate this Research:</strong></label><br>
                                                            <?php for ($i=1; $i<=5; $i++): ?>
                                                                <button type="submit" name="rating" value="<?php echo $i; ?>" class="btn btn-sm <?php echo ($i <= round($research['avg_rating'])) ? 'btn-warning' : 'btn-outline-secondary'; ?>">
                                                                    ✰
                                                                </button>
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
                                                <a href="../<?php echo $research['file_path']; ?>" class="btn btn-success" target="_blank">View Full Research</a>
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
</div>

<?php include '../includes/footer.php'; ?>
