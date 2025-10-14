<?php
require_once '../includes/session.php';
require_once '../includes/db.php';

// Restrict access to admin only
restrict_access(['admin']);

// Get report type
$report_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : 'research_by_department';

// Get date range if provided
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : null;
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : null;

// Prepare data arrays
$report_data = [];
$chart_labels = [];
$chart_values = [];
$chart_title = '';

switch ($report_type) {
    case 'research_by_department':
        $query = "SELECT department, COUNT(*) as count FROM research GROUP BY department ORDER BY count DESC";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
            $chart_labels[] = $row['department'];
            $chart_values[] = $row['count'];
        }
        $chart_title = "Research Papers by Department";
        break;

    case 'research_by_year':
        $query = "SELECT year_published, COUNT(*) as count FROM research GROUP BY year_published ORDER BY year_published DESC";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
            $chart_labels[] = $row['year_published'];
            $chart_values[] = $row['count'];
        }
        $chart_title = "Research Papers by Year";
        break;

    case 'research_by_faculty':
        $query = "SELECT u.full_name, COUNT(r.id) as count 
                  FROM users u 
                  LEFT JOIN research r ON u.id = r.uploaded_by 
                  WHERE u.role='faculty' 
                  GROUP BY u.id 
                  ORDER BY count DESC";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
            $chart_labels[] = $row['full_name'];
            $chart_values[] = $row['count'];
        }
        $chart_title = "Research Papers by Faculty";
        break;

    case 'uploads_by_date':
        $query = "SELECT DATE(upload_date) as date, COUNT(*) as count FROM research";
        if ($start_date && $end_date) $query .= " WHERE upload_date BETWEEN ? AND ?";
        $query .= " GROUP BY DATE(upload_date) ORDER BY date";
        $stmt = $conn->prepare($query);
        if ($start_date && $end_date) $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
            $chart_labels[] = $row['date'];
            $chart_values[] = $row['count'];
        }
        $chart_title = "Uploads by Date";
        break;

    case 'user_registrations':
        $query = "SELECT id, full_name, email, role, DATE(registration_date) as registration_date FROM users";
        if ($start_date && $end_date) $query .= " WHERE registration_date BETWEEN ? AND ?";
        $query .= " ORDER BY registration_date ASC";
        $stmt = $conn->prepare($query);
        if ($start_date && $end_date) $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        $report_data = $result->fetch_all(MYSQLI_ASSOC);

        // Chart: registrations per day
        $count_by_date = [];
        foreach ($report_data as $user) {
            $date = $user['registration_date'];
            if (!isset($count_by_date[$date])) $count_by_date[$date] = 0;
            $count_by_date[$date]++;
        }
        $chart_labels = array_keys($count_by_date);
        $chart_values = array_values($count_by_date);
        $chart_title = "User Registrations";
        break;
}

// Encode chart data for JS
$chart_labels_json = json_encode($chart_labels);
$chart_values_json = json_encode($chart_values);

include '../includes/header.php';
?>

<div class="container">
    <!-- Report selection form -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white"><h5>Generate Reports</h5></div>
                <div class="card-body">
                    <form action="reports.php" method="get" class="row g-3">
                        <div class="col-md-4">
                            <label for="type" class="form-label">Report Type</label>
                            <select name="type" id="type" class="form-select" onchange="toggleDateFields(this.value)">
                                <option value="research_by_department" <?php if($report_type=='research_by_department') echo 'selected'; ?>>Research by Department</option>
                                <option value="research_by_year" <?php if($report_type=='research_by_year') echo 'selected'; ?>>Research by Year</option>
                                <option value="research_by_faculty" <?php if($report_type=='research_by_faculty') echo 'selected'; ?>>Research by Faculty</option>
                                <option value="uploads_by_date" <?php if($report_type=='uploads_by_date') echo 'selected'; ?>>Uploads by Date</option>
                                <option value="user_registrations" <?php if($report_type=='user_registrations') echo 'selected'; ?>>User Registrations</option>
                            </select>
                        </div>
                        <div class="col-md-3 date-field" style="display:none;">
                            <label for="start_date">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-3 date-field" style="display:none;">
                            <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary w-100"><i class="fas fa-chart-bar"></i> Generate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Export PDF button -->
    <div class="mt-2 mb-4">
        <button class="btn btn-danger" onclick="exportPDF()">
            <i class="fas fa-file-pdf"></i> Export to PDF
        </button>
    </div>

    <!-- Chart and Table -->
    <div class="row">
        <div class="col-md-8">
            <canvas id="reportChart" width="400" height="250"></canvas>
        </div>
        <div class="col-md-4">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <?php if($report_type=='user_registrations'): ?>
                            <tr>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Registration Date</th>
                            </tr>
                        <?php elseif($report_type=='research_by_department'): ?>
                            <tr><th>Department</th><th>Count</th></tr>
                        <?php elseif($report_type=='research_by_year'): ?>
                            <tr><th>Year</th><th>Count</th></tr>
                        <?php elseif($report_type=='research_by_faculty'): ?>
                            <tr><th>Faculty</th><th>Count</th></tr>
                        <?php else: ?>
                            <tr><th>Date</th><th>Count</th></tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php if($report_type=='user_registrations'): ?>
                            <?php foreach($report_data as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                    <td><?php echo $user['registration_date']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php foreach($report_data as $row): ?>
                                <tr>
                                    <?php if($report_type=='research_by_department'): ?>
                                        <td><?php echo $row['department']; ?></td><td><?php echo $row['count']; ?></td>
                                    <?php elseif($report_type=='research_by_year'): ?>
                                        <td><?php echo $row['year_published']; ?></td><td><?php echo $row['count']; ?></td>
                                    <?php elseif($report_type=='research_by_faculty'): ?>
                                        <td><?php echo $row['full_name']; ?></td><td><?php echo $row['count']; ?></td>
                                    <?php elseif($report_type=='uploads_by_date'): ?>
                                        <td><?php echo $row['date']; ?></td><td><?php echo $row['count']; ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function toggleDateFields(reportType){
    const fields=document.querySelectorAll('.date-field');
    if(reportType==='uploads_by_date'||reportType==='user_registrations') fields.forEach(f=>f.style.display='block');
    else fields.forEach(f=>f.style.display='none');
}

// Chart.js
document.addEventListener('DOMContentLoaded',function(){
    const ctx=document.getElementById('reportChart').getContext('2d');
    new Chart(ctx,{
        type:'<?php echo in_array($report_type,['uploads_by_date','user_registrations'])?'line':'bar'; ?>',
        data:{labels: <?php echo $chart_labels_json; ?>, datasets:[{
            label:'<?php echo $chart_title; ?>',
            data: <?php echo $chart_values_json; ?>,
            backgroundColor:'rgba(54,162,235,0.5)',
            borderColor:'rgba(54,162,235,1)',
            borderWidth:1
        }]},
        options:{responsive:true,scales:{y:{beginAtZero:true,ticks:{precision:0}}}}
    });
});

// Export PDF using browser
function exportPDF() {
    const reportContent = document.querySelector('.container').innerHTML;
    const newWin = window.open('', '', 'width=800,height=600');
    newWin.document.write('<html><head><title>Report PDF</title>');
    newWin.document.write('<link rel="stylesheet" href="../assets/css/bootstrap.min.css">');
    newWin.document.write('<style>table{width:100%;border-collapse:collapse;} th, td{border:1px solid #000;padding:5px;text-align:left;}</style>');
    newWin.document.write('</head><body>');
    newWin.document.write(reportContent);
    newWin.document.write('</body></html>');
    newWin.document.close();
    newWin.focus();
    newWin.print();
    newWin.close();
}
</script>

<?php include '../includes/footer.php'; ?>
