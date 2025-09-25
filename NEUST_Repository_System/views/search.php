<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/db.php'; // Make sure $conn is defined

if (!is_logged_in()) {
    $_SESSION['message'] = "Please log in to access the search page.";
    $_SESSION['message_type'] = "warning";
    header("Location: ../login.php");
    exit;
}

// Get search parameters
$keyword = isset($_GET['keyword']) ? sanitize_input($_GET['keyword']) : '';
$department = isset($_GET['department']) ? sanitize_input($_GET['department']) : '';
$year = isset($_GET['year']) ? sanitize_input($_GET['year']) : '';
$author = isset($_GET['author']) ? sanitize_input($_GET['author']) : '';
$sort_by = isset($_GET['sort_by']) ? sanitize_input($_GET['sort_by']) : 'date_desc';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Base query
$query = "SELECT r.*, u.full_name as uploader FROM research r 
          LEFT JOIN users u ON r.uploaded_by = u.id WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM research r WHERE 1=1";

$params = [];
$types = "";

// Filters
if (!empty($keyword)) {
    $keyword_search = "%$keyword%";
    $query .= " AND (r.title LIKE ? OR r.abstract LIKE ? OR r.keywords LIKE ?)";
    $count_query .= " AND (r.title LIKE ? OR r.abstract LIKE ? OR r.keywords LIKE ?)";
    $params = array_merge($params, [$keyword_search, $keyword_search, $keyword_search]);
    $types .= "sss";
}

if (!empty($department)) {
    $query .= " AND r.department = ?";
    $count_query .= " AND r.department = ?";
    $params[] = $department;
    $types .= "s";
}

if (!empty($year)) {
    $query .= " AND r.year_published = ?";
    $count_query .= " AND r.year_published = ?";
    $params[] = $year;
    $types .= "s";
}

if (!empty($author)) {
    $author_search = "%$author%";
    $query .= " AND r.authors LIKE ?";
    $count_query .= " AND r.authors LIKE ?";
    $params[] = $author_search;
    $types .= "s";
}

// Role-based access
if ($_SESSION['role'] == 'student') {
    $query .= " AND (r.status = 'public' OR (r.status = 'restricted' AND r.department = ?))";
    $count_query .= " AND (r.status = 'public' OR (r.status = 'restricted' AND r.department = ?))";
    $params[] = $_SESSION['department'];
    $types .= "s";
} elseif ($_SESSION['role'] == 'guest') {
    $query .= " AND r.status = 'public'";
    $count_query .= " AND r.status = 'public'";
}

// Sorting
switch ($sort_by) {
    case 'title_asc': $query .= " ORDER BY r.title ASC"; break;
    case 'title_desc': $query .= " ORDER BY r.title DESC"; break;
    case 'year_asc': $query .= " ORDER BY r.year_published ASC"; break;
    case 'year_desc': $query .= " ORDER BY r.year_published DESC"; break;
    case 'date_asc': $query .= " ORDER BY r.created_at ASC"; break;
    case 'date_desc':
    default: $query .= " ORDER BY r.created_at DESC"; break;
}

// Count query execution
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_ref_params = [];
    for ($i = 0; $i < strlen($types); $i++) {
        $count_ref_params[] = &$params[$i];
    }
    if (!empty($count_ref_params)) $count_stmt->bind_param($types, ...$count_ref_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

// Main query with pagination
$query .= " LIMIT ? OFFSET ?";
$main_params = $params;
$main_params[] = $per_page;
$main_params[] = $offset;
$main_types = $types . "ii";

$stmt = $conn->prepare($query);
$ref_params = [];
for ($i = 0; $i < strlen($main_types); $i++) {
    $ref_params[] = &$main_params[$i];
}
$stmt->bind_param($main_types, ...$ref_params);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Search Research Papers</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="search.php" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="keyword" class="form-control" placeholder="Search keywords..." value="<?php echo htmlspecialchars($keyword); ?>">
                </div>
                <div class="col-md-2">
                    <input type="text" name="department" class="form-control" placeholder="Department" value="<?php echo htmlspecialchars($department); ?>">
                </div>
                <div class="col-md-2">
                    <input type="text" name="year" class="form-control" placeholder="Year" value="<?php echo htmlspecialchars($year); ?>">
                </div>
                <div class="col-md-2">
                    <input type="text" name="author" class="form-control" placeholder="Author" value="<?php echo htmlspecialchars($author); ?>">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Search Results -->
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Search Results</h5>
            <span class="badge bg-light text-dark"><?php echo $total_rows; ?> results found</span>
        </div>
        <div class="card-body">
            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Authors</th>
                                <th>Department</th>
                                <th>Year</th>
                                <th>Uploaded By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['authors']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                                    <td><?php echo htmlspecialchars($row['year_published']); ?></td>
                                    <td><?php echo htmlspecialchars($row['uploader']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info view-abstract" data-bs-toggle="modal" data-bs-target="#abstractModal" 
                                            data-title="<?php echo htmlspecialchars($row['title']); ?>" 
                                            data-abstract="<?php echo htmlspecialchars($row['abstract']); ?>"
                                            data-keywords="<?php echo htmlspecialchars($row['keywords']); ?>">
                                            <i class="fas fa-eye"></i> Abstract
                                        </button>

                                        <?php if ($_SESSION['role'] != 'guest'): ?>
                                            <a href="../<?php echo $row['file_path']; ?>" class="btn btn-sm btn-success" target="_blank">
                                                <i class="fas fa-file-pdf"></i> View PDF
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php echo generate_pagination($total_rows, $per_page, $page, 'search.php'); ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No research papers found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Abstract Modal -->
<div class="modal fade" id="abstractModal" tabindex="-1" aria-labelledby="abstractModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="abstractModalLabel">Research Abstract</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h4 id="modal-title"></h4>
                <hr>
                <h6>Abstract:</h6>
                <p id="modal-abstract"></p>
                <h6>Keywords:</h6>
                <p id="modal-keywords"></p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const abstractModal = document.getElementById('abstractModal');
    abstractModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('modal-title').textContent = button.getAttribute('data-title');
        document.getElementById('modal-abstract').textContent = button.getAttribute('data-abstract');
        document.getElementById('modal-keywords').textContent = button.getAttribute('data-keywords');
    });
});
</script>

<?php include '../includes/footer.php'; ?>
