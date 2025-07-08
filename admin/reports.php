<?php
$page_title = 'Reports';
require_once '../config/config.php';
require_once '../includes/functions.php';

requireRole('admin');

$user = getCurrentUser();

// Handle report generation
$report_type = isset($_GET['type']) ? $_GET['type'] : 'summary';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Reports & Analytics</h2>
                    <p class="text-muted">Generate detailed reports and analyze system performance</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="exportCurrentReport()">
                        <i class="fas fa-download me-2"></i>Export Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Controls -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="type" class="form-label">Report Type</label>
                    <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                        <option value="summary" <?php echo ($report_type === 'summary') ? 'selected' : ''; ?>>Summary Report</option>
                        <option value="category" <?php echo ($report_type === 'category') ? 'selected' : ''; ?>>Category Analysis</option>
                        <option value="department" <?php echo ($report_type === 'department') ? 'selected' : ''; ?>>Department Performance</option>
                        <option value="user" <?php echo ($report_type === 'user') ? 'selected' : ''; ?>>User Activity</option>
                        <option value="trend" <?php echo ($report_type === 'trend') ? 'selected' : ''; ?>>Trend Analysis</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-chart-bar me-2"></i>Generate Report
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Content -->
    <div class="row">
        <?php if ($report_type === 'summary'): ?>
            <!-- Summary Report -->
            <?php
            $summary_stats = $db->selectOne(
                "SELECT 
                    COUNT(*) as total_faults,
                    SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
                    SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
                    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority,
                    AVG(CASE WHEN status = 'resolved' THEN DATEDIFF(updated_at, created_at) ELSE NULL END) as avg_resolution_time
                FROM fault_reports 
                WHERE created_at BETWEEN ? AND ?",
                [$date_from, $date_to . ' 23:59:59']
            );
            
            $daily_stats = $db->select(
                "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
                FROM fault_reports 
                WHERE created_at BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY date",
                [$date_from, $date_to . ' 23:59:59']
            );
            ?>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Summary Statistics</h5>
                        <small class="text-muted"><?php echo formatDate($date_from); ?> to <?php echo formatDate($date_to); ?></small>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="h2 text-primary"><?php echo $summary_stats['total_faults']; ?></div>
                                <small class="text-muted">Total Faults</small>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="h2 text-success"><?php echo $summary_stats['resolved']; ?></div>
                                <small class="text-muted">Resolved</small>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="h2 text-warning"><?php echo $summary_stats['in_progress']; ?></div>
                                <small class="text-muted">In Progress</small>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="h2 text-danger"><?php echo $summary_stats['high_priority']; ?></div>
                                <small class="text-muted">High Priority</small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>Resolution Rate</span>
                                <span><?php echo $summary_stats['total_faults'] > 0 ? round(($summary_stats['resolved'] / $summary_stats['total_faults']) * 100, 1) : 0; ?>%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: <?php echo $summary_stats['total_faults'] > 0 ? round(($summary_stats['resolved'] / $summary_stats['total_faults']) * 100, 1) : 0; ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>Average Resolution Time</span>
                                <span><?php echo $summary_stats['avg_resolution_time'] ? round($summary_stats['avg_resolution_time'], 1) : 0; ?> days</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Daily Trend</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyChart" width="800" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <script>
            // Daily trend chart
            const dailyData = <?php echo json_encode($daily_stats); ?>;
            const dailyLabels = dailyData.map(item => item.date);
            const dailyTotals = dailyData.map(item => item.total);
            const dailyResolved = dailyData.map(item => item.resolved);
            
            const dailyCtx = document.getElementById('dailyChart').getContext('2d');
            new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: dailyLabels,
                    datasets: [{
                        label: 'Total Faults',
                        data: dailyTotals,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        fill: true
                    }, {
                        label: 'Resolved',
                        data: dailyResolved,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            </script>
            
        <?php elseif ($report_type === 'category'): ?>
            <!-- Category Analysis -->
            <?php
            $category_stats = $db->select(
                "SELECT 
                    category,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority,
                    AVG(CASE WHEN status = 'resolved' THEN DATEDIFF(updated_at, created_at) ELSE NULL END) as avg_resolution_time
                FROM fault_reports 
                WHERE created_at BETWEEN ? AND ?
                GROUP BY category
                ORDER BY total DESC",
                [$date_from, $date_to . ' 23:59:59']
            );
            ?>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Category Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Category Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Total</th>
                                        <th>Resolved</th>
                                        <th>Rate</th>
                                        <th>Avg Days</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($category_stats as $stat): ?>
                                        <tr>
                                            <td><?php echo getFaultCategoryName($stat['category']); ?></td>
                                            <td><?php echo $stat['total']; ?></td>
                                            <td><?php echo $stat['resolved']; ?></td>
                                            <td><?php echo round(($stat['resolved'] / $stat['total']) * 100, 1); ?>%</td>
                                            <td><?php echo $stat['avg_resolution_time'] ? round($stat['avg_resolution_time'], 1) : '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
            // Category chart
            const categoryStatsData = <?php echo json_encode($category_stats); ?>;
            const categoryLabels = categoryStatsData.map(item => {
                const categories = <?php echo json_encode(FAULT_CATEGORIES); ?>;
                return categories[item.category] || item.category;
            });
            const categoryTotals = categoryStatsData.map(item => item.total);
            
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            new Chart(categoryCtx, {
                type: 'pie',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryTotals,
                        backgroundColor: [
                            '#007bff', '#28a745', '#ffc107', '#dc3545', 
                            '#6c757d', '#17a2b8', '#fd7e14'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            </script>
            
        <?php elseif ($report_type === 'department'): ?>
            <!-- Department Performance -->
            <?php
            $dept_stats = $db->select(
                "SELECT 
                    assigned_department,
                    COUNT(*) as total_assigned,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    AVG(CASE WHEN status = 'resolved' THEN DATEDIFF(updated_at, created_at) ELSE NULL END) as avg_resolution_time
                FROM fault_reports 
                WHERE assigned_department IS NOT NULL 
                AND created_at BETWEEN ? AND ?
                GROUP BY assigned_department
                ORDER BY total_assigned DESC",
                [$date_from, $date_to . ' 23:59:59']
            );
            ?>
            
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Department Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>Total Assigned</th>
                                        <th>Resolved</th>
                                        <th>In Progress</th>
                                        <th>Resolution Rate</th>
                                        <th>Avg Resolution Time</th>
                                        <th>Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dept_stats as $dept): ?>
                                        <?php $resolution_rate = round(($dept['resolved'] / $dept['total_assigned']) * 100, 1); ?>
                                        <tr>
                                            <td><?php echo getDepartmentName($dept['assigned_department']); ?></td>
                                            <td><?php echo $dept['total_assigned']; ?></td>
                                            <td><span class="badge bg-success"><?php echo $dept['resolved']; ?></span></td>
                                            <td><span class="badge bg-warning"><?php echo $dept['in_progress']; ?></span></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress me-2" style="width: 100px;">
                                                        <div class="progress-bar" style="width: <?php echo $resolution_rate; ?>%"></div>
                                                    </div>
                                                    <span><?php echo $resolution_rate; ?>%</span>
                                                </div>
                                            </td>
                                            <td><?php echo $dept['avg_resolution_time'] ? round($dept['avg_resolution_time'], 1) . ' days' : '-'; ?></td>
                                            <td>
                                                <?php if ($resolution_rate >= 80): ?>
                                                    <span class="badge bg-success">Excellent</span>
                                                <?php elseif ($resolution_rate >= 60): ?>
                                                    <span class="badge bg-warning">Good</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Needs Improvement</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php elseif ($report_type === 'user'): ?>
            <!-- User Activity -->
            <?php
            $user_stats = $db->select(
                "SELECT 
                    CONCAT(u.first_name, ' ', u.last_name) as user_name,
                    u.email,
                    COUNT(fr.id) as total_reports,
                    SUM(CASE WHEN fr.status = 'resolved' THEN 1 ELSE 0 END) as resolved_reports
                FROM users u
                LEFT JOIN fault_reports fr ON u.id = fr.user_id AND fr.created_at BETWEEN ? AND ?
                WHERE u.role = 'resident'
                GROUP BY u.id
                HAVING total_reports > 0
                ORDER BY total_reports DESC
                LIMIT 20",
                [$date_from, $date_to . ' 23:59:59']
            );
            ?>
            
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Top Reporting Users</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Total Reports</th>
                                        <th>Resolved</th>
                                        <th>Success Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_stats as $user_stat): ?>
                                        <?php $success_rate = $user_stat['total_reports'] > 0 ? round(($user_stat['resolved_reports'] / $user_stat['total_reports']) * 100, 1) : 0; ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user_stat['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user_stat['email']); ?></td>
                                            <td><?php echo $user_stat['total_reports']; ?></td>
                                            <td><?php echo $user_stat['resolved_reports']; ?></td>
                                            <td>
                                                <div class="progress" style="width: 100px;">
                                                    <div class="progress-bar" style="width: <?php echo $success_rate; ?>%"></div>
                                                </div>
                                                <small><?php echo $success_rate; ?>%</small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php endif; ?>
    </div>
</div>

<script>
function exportCurrentReport() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'pdf');
    
    const exportUrl = `../api/export_report.php?${params.toString()}`;
    window.open(exportUrl, '_blank');
}
</script>

<?php include '../includes/footer.php'; ?>
