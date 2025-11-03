<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// ✅ Check login
if (!is_logged_in()) {
    $_SESSION['message'] = "Please log in to access the search page.";
    $_SESSION['message_type'] = "warning";
    header("Location: ../login.php");
    exit;
}

// ✅ Search filters
$keyword = isset($_GET['keyword']) ? sanitize_input($_GET['keyword']) : '';
$department = isset($_GET['department']) ? sanitize_input($_GET['department']) : '';
$year = isset($_GET['year']) ? sanitize_input($_GET['year']) : '';
$author = isset($_GET['author']) ? sanitize_input($_GET['author']) : '';
$sort_by = isset($_GET['sort_by']) ? sanitize_input($_GET['sort_by']) : 'date_desc';

// ✅ Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// ✅ Base Queries
$query = "SELECT r.*, u.full_name AS uploader 
          FROM research r 
          LEFT JOIN users u ON r.uploaded_by = u.id 
          WHERE 1=1";

$count_query = "SELECT COUNT(*) AS total 
                FROM research r 
                LEFT JOIN users u ON r.uploaded_by = u.id 
                WHERE 1=1";

$params = [];
$types = "";

// ✅ Filters
if (!empty($keyword)) {
    $keyword_search = "%$keyword%";
    $query .= " AND (r.title LIKE ? OR r.abstract LIKE ? OR r.keywords LIKE ? OR r.authors LIKE ?)";
    $count_query .= " AND (r.title LIKE ? OR r.abstract LIKE ? OR r.keywords LIKE ? OR r.authors LIKE ?)";
    array_push($params, $keyword_search, $keyword_search, $keyword_search, $keyword_search);
    $types .= "ssss";
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

// ✅ Role-based access
if ($_SESSION['role'] == 'faculty') {
    $query .= " AND r.uploaded_by = ?";
    $count_query .= " AND r.uploaded_by = ?";
    $params[] = $_SESSION['user_id'];
    $types .= "i";
} elseif ($_SESSION['role'] == 'student') {
    $query .= " AND (r.status = 'approved' OR (r.status = 'pending' AND r.department = ?))";
    $count_query .= " AND (r.status = 'approved' OR (r.status = 'pending' AND r.department = ?))";
    $params[] = $_SESSION['department'];
    $types .= "s";
} elseif ($_SESSION['role'] == 'guest') {
    $query .= " AND r.status = 'approved'";
    $count_query .= " AND r.status = 'approved'";
}

// ✅ Sorting
switch ($sort_by) {
    case 'title_asc': $query .= " ORDER BY r.title ASC"; break;
    case 'title_desc': $query .= " ORDER BY r.title DESC"; break;
    case 'year_asc': $query .= " ORDER BY r.year_published ASC"; break;
    case 'year_desc': $query .= " ORDER BY r.year_published DESC"; break;
    case 'date_asc': $query .= " ORDER BY r.uploaded_on ASC"; break;
    default: $query .= " ORDER BY r.uploaded_on DESC"; break;
}

// ✅ Count total rows safely
$total_rows = 0;
if ($count_stmt = $conn->prepare($count_query)) {
    if (!empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt->execute()) {
        $count_result = $count_stmt->get_result();
        if ($count_result instanceof mysqli_result) {
            $row = $count_result->fetch_assoc();
            $total_rows = (int)$row['total'];
        }
    }
    $count_stmt->close();
}
$total_pages = max(1, ceil($total_rows / $per_page));

// ✅ Pagination limits
$query .= " LIMIT ? OFFSET ?";
$params_with_pagination = $params;
$types_with_pagination = $types . "ii";
$params_with_pagination[] = $per_page;
$params_with_pagination[] = $offset;

// ✅ Execute Main Query safely
$result = false;
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param($types_with_pagination, ...$params_with_pagination);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
    }
}

include '../includes/header.php';
?>

<style>
.table th {
    background-color: #003366;
    color: white;
    text-align: center;
}
.table td {
    vertical-align: middle;
}
.btn-request {
    background-color: #004080;
    color: #fff;
    border-radius: 20px;
    padding: 6px 16px;
    font-size: 13px;
    transition: all 0.3s ease;
}
.btn-request:hover {
    background-color: #0066cc;
    transform: scale(1.05);
}
.btn-info, .btn-success {
    border-radius: 20px;
    padding: 5px 12px;
}
.alert-info {
    background-color: #e9f4ff;
    border-color: #b8e1ff;
    color: #004080;
}
</style>

<div class="container mt-4">
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
                <input type="text" name="year" class="form-control" placeholder="Year Published" value="<?php echo htmlspecialchars($year); ?>">
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

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Search Results</h5>
    </div>
    <div class="card-body">
        <?php if ($result instanceof mysqli_result && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Authors</th>
                            <th>Department</th>
                            <th>Year Published</th>
                            <th>Uploaded By</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <a href="view_research.php?id=<?php echo $row['id']; ?>" 
                                       class="fw-bold text-decoration-none text-primary">
                                       <?php echo htmlspecialchars($row['title']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($row['authors']); ?></td>
                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                <td><?php echo htmlspecialchars($row['year_published']); ?></td>
                                <td><?php echo htmlspecialchars($row['uploader']); ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#abstractModal"
                                        data-title="<?php echo htmlspecialchars($row['title']); ?>" 
                                        data-abstract="<?php echo htmlspecialchars($row['abstract']); ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i> No research papers found.
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- ✅ Abstract Modal -->
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
                <p id="modal-abstract"></p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('abstractModal');
    modal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('modal-title').textContent = button.getAttribute('data-title');
        document.getElementById('modal-abstract').textContent = button.getAttribute('data-abstract');
    });
});
</script>

<?php include '../includes/footer.php'; ?>
